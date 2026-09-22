<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditEvent;
use App\Models\EmployeeProfile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'hasSignature' => ! empty($request->user()->profile?->signature_path),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->has('nip') || $request->has('phone')) {
            $rawNip = trim((string) $request->input('nip'));
            $cleanNip = (! empty($rawNip) && ! str_contains($rawNip, '@')) ? $rawNip : null;
            $rawPhone = trim((string) $request->input('phone'));
            $cleanPhone = ! empty($rawPhone) ? $rawPhone : null;

            EmployeeProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['nip' => $cleanNip, 'phone' => $cleanPhone]
            );
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->forceFill(['status' => 'suspended', 'remember_token' => null])->save();
        AuditEvent::record($user, 'account.deactivated');
        Auth::logout();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Store or update the user's digital signature.
     */
    public function updateSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'signature_image' => ['required', 'string'],
            'agreement' => ['required', 'accepted'],
        ], [
            'agreement.accepted' => 'Anda harus menyetujui syarat & ketentuan kerahasiaan tanda tangan.',
            'signature_image.required' => 'Gambar tanda tangan belum diproses.',
        ]);

        $dataUrl = $request->input('signature_image');
        if (! preg_match('/^data:image\/png;base64,/', $dataUrl)) {
            return back()->withErrors(['signature_image' => 'Format gambar tanda tangan harus berupa format PNG dengan latar transparan.']);
        }

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $decoded = base64_decode($base64, true);
        if (! $decoded || strlen($decoded) > 5 * 1024 * 1024) {
            return back()->withErrors(['signature_image' => 'Ukuran berkas tanda tangan tidak valid atau melebihi batas 5 MB.']);
        }

        $user = $request->user();
        $fileName = 'signatures/user_'.$user->id.'_'.time().'.png';

        $oldProfile = $user->profile;
        if ($oldProfile?->signature_path && Storage::disk('local')->exists($oldProfile->signature_path)) {
            Storage::disk('local')->delete($oldProfile->signature_path);
        }

        Storage::disk('local')->put($fileName, $decoded);

        EmployeeProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['signature_path' => $fileName]
        );

        AuditEvent::record($user, 'profile.signature_updated');

        return Redirect::route('profile.edit')->with('status', 'signature-updated');
    }

    /**
     * Stream the user's saved digital signature.
     */
    public function signature(Request $request): mixed
    {
        $user = $request->user();
        $profile = $user->profile()->first();

        abort_unless($profile?->signature_path && Storage::disk('local')->exists($profile->signature_path), 404);

        return Storage::disk('local')->response($profile->signature_path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Remove the user's digital signature.
     */
    public function destroySignature(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if ($profile?->signature_path) {
            if (Storage::disk('local')->exists($profile->signature_path)) {
                Storage::disk('local')->delete($profile->signature_path);
            }
            $profile->update(['signature_path' => null]);
        }

        AuditEvent::record($user, 'profile.signature_deleted');

        return Redirect::route('profile.edit')->with('status', 'signature-deleted');
    }
}
