<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\OrganizationUnit;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', ['units' => OrganizationUnit::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('unit_kerja') && ! $request->filled('unit_id')) {
            $request->merge(['unit_id' => $request->input('unit_kerja')]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'unit_id' => 'required|exists:organization_units,id',
            'nip' => [
                'nullable',
                'string',
                'max:30',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value !== null && (str_contains($value, '@') || $value === $request->input('email'))) {
                        $fail('Format NIP tidak valid. NIP tidak boleh berupa alamat email.');
                    }
                },
            ],
            'phone' => 'nullable|string|max:30',
        ], [
            'name.required' => 'Nama lengkap pegawai wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'email.required' => 'Alamat email dinas/instansi wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Akun dengan email ini sudah terdaftar. Silakan login atau gunakan fitur lupa password jika Anda lupa kata sandi.',
            'unit_id.required' => 'Unit kerja penugasan wajib dipilih.',
            'unit_id.exists' => 'Pilihan unit kerja penugasan tidak valid.',
            'nip.max' => 'NIP maksimal 30 karakter.',
            'phone.max' => 'Nomor HP/WhatsApp maksimal 30 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Password belum sesuai dengan ketentuan: minimal 8 karakter.',
            'password.letters' => 'Password belum sesuai dengan ketentuan: harus mengandung huruf.',
            'password.mixed' => 'Password belum sesuai dengan ketentuan: harus mengandung kombinasi huruf besar dan kecil.',
            'password.numbers' => 'Password belum sesuai dengan ketentuan: harus mengandung setidaknya 1 angka.',
            'password.symbols' => 'Password belum sesuai dengan ketentuan: harus mengandung setidaknya 1 simbol.',
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'status' => 'pending']);
            $rawNip = isset($data['nip']) ? trim((string) $data['nip']) : null;
            $cleanNip = (! empty($rawNip) && ! str_contains($rawNip, '@')) ? $rawNip : null;
            EmployeeProfile::create(['user_id' => $user->id, 'unit_id' => $data['unit_id'], 'nip' => $cleanNip, 'phone' => $data['phone'] ?? null]);
            $role = Role::firstOrCreate(['name' => 'Pegawai']);
            RoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id, 'unit_id' => $data['unit_id'], 'starts_at' => now()]);

            return $user;
        });
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('verification.notice');
    }
}
