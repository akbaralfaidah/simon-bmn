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
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'unit_id' => 'required|exists:organization_units,id', 'nip' => 'nullable|string|max:30', 'phone' => 'nullable|string|max:30',
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'status' => 'pending']);
            EmployeeProfile::create(['user_id' => $user->id, 'unit_id' => $data['unit_id'], 'nip' => $data['nip'] ?? null, 'phone' => $data['phone'] ?? null]);
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
