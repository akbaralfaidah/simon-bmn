<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupSimonTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_requires_explicit_mandate_and_preserves_password_and_assets(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Koordinator BMN']);
        RoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id]);
        $asset = Asset::create(['name' => 'Aset lama', 'condition' => 'Baik']);
        $hash = $user->password;
        $this->artisan('simon:setup', ['user' => $user->id])->assertFailed();
        $this->assertDatabaseCount('organization_units', 0);
        $this->artisan('simon:setup', ['user' => $user->id, '--global-admin' => true, '--units' => true])->assertSuccessful();
        $this->assertDatabaseCount('organization_units', 3);
        $this->assertDatabaseHas('role_assignments', ['user_id' => $user->id, 'is_global' => true, 'can_administer' => true]);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertNull($asset->fresh()->room_id);
        $this->assertDatabaseHas('audit_events', ['action' => 'admin.bootstrap_granted']);
        $this->artisan('simon:setup', ['user' => $user->id, '--global-admin' => true, '--units' => true])->assertSuccessful();
        $this->assertDatabaseCount('organization_units', 3);
        $this->assertDatabaseCount('role_assignments', 1);
    }
}
