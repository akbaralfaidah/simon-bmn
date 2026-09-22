<?php

namespace Tests\Feature\Auth;

use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();
        $unit = OrganizationUnit::create(['name' => 'Unit tes', 'code' => 'TEST']);
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Frasa-Sandi-123!',
            'password_confirmation' => 'Frasa-Sandi-123!',
            'unit_id' => $unit->id,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'status' => 'pending']);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertDatabaseHas('employee_profiles', ['user_id' => $user->id, 'unit_id' => $unit->id]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_fails_if_password_does_not_meet_complexity(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Unit tes 2', 'code' => 'TEST2']);
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'weak@example.com',
            'password' => 'hanyahurufkecil',
            'password_confirmation' => 'hanyahurufkecil',
            'unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_fails_if_email_is_already_registered(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Unit tes 3', 'code' => 'TEST3']);
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->post('/register', [
            'name' => 'Test Duplicate',
            'email' => 'duplicate@example.com',
            'password' => 'Frasa-Sandi-123!',
            'password_confirmation' => 'Frasa-Sandi-123!',
            'unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Akun dengan email ini sudah terdaftar. Silakan login atau gunakan fitur lupa password jika Anda lupa kata sandi.',
        ]);
        $this->assertGuest();
    }

    public function test_registration_saves_clean_nip(): void
    {
        Notification::fake();
        $unit = OrganizationUnit::create(['name' => 'Unit NIP', 'code' => 'NIP1']);
        $response = $this->post('/register', [
            'name' => 'Pegawai Ber-NIP',
            'email' => 'pegawai.nip@example.com',
            'nip' => '199501012022031001',
            'password' => 'Frasa-Sandi-123!',
            'password_confirmation' => 'Frasa-Sandi-123!',
            'unit_id' => $unit->id,
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'pegawai.nip@example.com')->firstOrFail();
        $this->assertDatabaseHas('employee_profiles', [
            'user_id' => $user->id,
            'nip' => '199501012022031001',
        ]);
    }

    public function test_registration_rejects_email_in_nip(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Unit NIP 2', 'code' => 'NIP2']);
        $response = $this->post('/register', [
            'name' => 'Pegawai Salah NIP',
            'email' => 'salah.nip@example.com',
            'nip' => 'salah.nip@example.com',
            'password' => 'Frasa-Sandi-123!',
            'password_confirmation' => 'Frasa-Sandi-123!',
            'unit_id' => $unit->id,
        ]);

        $response->assertSessionHasErrors('nip');
        $this->assertGuest();
    }
}
