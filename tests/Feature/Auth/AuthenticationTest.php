<?php

namespace Tests\Feature\Auth;

use App\Models\EmployeeProfile;
use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => __('auth.password_mismatch'),
        ]);
    }

    public function test_login_fails_with_descriptive_error_when_account_does_not_exist(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.test',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => __('auth.not_found'),
        ]);
    }

    public function test_login_fails_with_descriptive_error_when_account_is_inactive(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => __('auth.inactive'),
        ]);
    }

    public function test_users_can_authenticate_using_nip(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationUnit::firstOrCreate(
            ['code' => 'UNIT-TEST'],
            ['name' => 'Unit Uji Coba']
        );
        EmployeeProfile::create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'nip' => '199001012020011001',
        ]);

        $response = $this->post('/login', [
            'email' => '199001012020011001',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
