<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class TwoFactorController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $recent = $request->session()->get('mfa.confirmed_password_at', 0) > now()->subMinutes(10)->timestamp;
        Inertia::encryptHistory();

        return Inertia::render('Auth/TwoFactor', [
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'required' => EnsureTwoFactorAuthenticated::required($user),
            'verified' => EnsureTwoFactorAuthenticated::verified($request),
            'qrUrl' => ! $user->two_factor_confirmed_at && $user->two_factor_secret && $recent ? $user->twoFactorQrCodeUrl() : null,
            'recoveryCodes' => $user->hasEnabledTwoFactorAuthentication() && EnsureTwoFactorAuthenticated::verified($request) && $recent ? $user->recoveryCodes() : [],
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password']);
        abort_if($request->user()->hasEnabledTwoFactorAuthentication(), 422, 'MFA sudah aktif. Gunakan verifikasi kode.');
        DB::transaction(function () use ($request, $enable) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $enable($user);
            AuditEvent::record($user, 'mfa.setup_started');
        });
        $request->session()->put('mfa.confirmed_password_at', now()->timestamp);

        return back();
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $request->validate(['code' => ['required', 'regex:/^[0-9]{6}$/']]);
        abort_unless($request->session()->get('mfa.confirmed_password_at', 0) > now()->subMinutes(10)->timestamp, 403);
        DB::transaction(function () use ($request, $confirm) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_if($user->hasEnabledTwoFactorAuthentication(), 422, 'MFA sudah dikonfirmasi.');
            try {
                $confirm($user, $request->input('code'));
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages(['code' => 'Kode autentikator tidak valid atau sudah digunakan.']);
            }
            $this->markVerified($request, $user);
            AuditEvent::record($user, 'mfa.enabled');
        });

        return back()->with('success', 'MFA aktif. Simpan kode pemulihan di tempat aman sebelum melanjutkan.');
    }

    public function challenge(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $data = $request->validate(['code' => ['nullable', 'required_without:recovery_code', 'regex:/^[0-9]{6}$/'], 'recovery_code' => 'nullable|required_without:code|string|max:100']);
        DB::transaction(function () use ($request, $provider, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->hasEnabledTwoFactorAuthentication(), 422, 'Aktifkan MFA terlebih dahulu.');
            $valid = false;
            if (! empty($data['recovery_code'])) {
                foreach ($user->recoveryCodes() as $code) {
                    if (hash_equals($code, $data['recovery_code'])) {
                        $user->replaceRecoveryCode($code);
                        $valid = true;
                        break;
                    }
                }
            } else {
                $valid = $provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $data['code']);
            }
            if (! $valid) {
                throw ValidationException::withMessages(['code' => 'Kode tidak valid, kedaluwarsa, atau sudah digunakan.']);
            }
            $this->markVerified($request, $user);
            AuditEvent::record($user, 'mfa.verified', ['recovery_used' => ! empty($data['recovery_code'])]);
        });

        return to_route('dashboard');
    }

    public function recovery(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        abort_unless(EnsureTwoFactorAuthenticated::verified($request), 403);
        $request->validate(['password' => 'required|current_password']);
        DB::transaction(function () use ($request, $generate) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $generate($user);
            AuditEvent::record($user, 'mfa.recovery_rotated');
        });
        $request->session()->put('mfa.confirmed_password_at', now()->timestamp);

        return back()->with('success', 'Kode pemulihan baru dibuat. Kode lama tidak berlaku.');
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        abort_if(EnsureTwoFactorAuthenticated::required($request->user()), 403, 'MFA wajib bagi PJ dan Koordinator.');
        abort_unless(EnsureTwoFactorAuthenticated::verified($request), 403);
        $request->validate(['password' => 'required|current_password']);
        DB::transaction(function () use ($request, $disable) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $disable($user);
            AuditEvent::record($user, 'mfa.disabled');
        });
        $request->session()->forget('mfa');

        return to_route('profile.edit')->with('success', 'MFA dinonaktifkan.');
    }

    private function markVerified(Request $request, User $user): void
    {
        $request->session()->regenerate();
        $request->session()->put(['mfa.user_id' => $user->id, 'mfa.secret_hash' => hash('sha256', $user->two_factor_secret)]);
    }
}
