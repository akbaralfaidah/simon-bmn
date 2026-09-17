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
            'password' => 'frasa-sandi-aman-123',
            'password_confirmation' => 'frasa-sandi-aman-123',
            'unit_id' => $unit->id,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'status' => 'pending']);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertDatabaseHas('employee_profiles', ['user_id' => $user->id, 'unit_id' => $unit->id]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
