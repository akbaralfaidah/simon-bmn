<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_privileged_user_can_access_operational_pages_without_mandatory_mfa(): void
    {
        $user = $this->coordinator();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->get(route('assets.index'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_setup_requires_password_and_valid_authenticator_confirmation(): void
    {
        $user = $this->coordinator();
        $this->actingAs($user)->post(route('two-factor.enable'), ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertNull($user->fresh()->two_factor_secret);
        $this->post(route('two-factor.enable'), ['password' => 'password'])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->post(route('two-factor.confirm'), ['code' => 'invalid'])->assertSessionHasErrors('code');
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post(route('two-factor.confirm'), ['code' => $code])->assertSessionHasNoErrors();
        $this->assertNotNull($user->refresh()->two_factor_confirmed_at);
        $this->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page->missing('auth.user.two_factor_secret')->missing('auth.user.two_factor_recovery_codes'));
    }

    public function test_recovery_code_is_consumed_and_cannot_be_reused(): void
    {
        $user = $this->coordinator();
        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $recovery = $user->recoveryCodes()[0];
        $this->actingAs($user)->post(route('two-factor.challenge'), ['recovery_code' => $recovery])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
        $this->assertNotContains($recovery, $user->fresh()->recoveryCodes());
        $this->withSession(['mfa.user_id' => null, 'mfa.secret_hash' => null]);
        $this->post(route('two-factor.challenge'), ['recovery_code' => $recovery])->assertSessionHasErrors('code');
    }

    public function test_authenticator_code_cannot_be_replayed_in_another_session(): void
    {
        $user = $this->coordinator();
        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $code = app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
        $this->actingAs($user)->post(route('two-factor.challenge'), ['code' => $code])->assertSessionHasNoErrors();
        $this->withSession(['mfa.user_id' => null, 'mfa.secret_hash' => null]);
        $this->post(route('two-factor.challenge'), ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_regular_employee_without_mfa_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    private function coordinator(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Koordinator']);
        RoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id]);

        return $user;
    }
}
