<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\OrganizationUnit;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

class AssetPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_global_administrator_can_place_unclassified_asset_without_overwriting_identity(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Unit', 'code' => 'U']);
        $room = Room::create(['name' => 'Ruangan', 'unit_id' => $unit->id]);
        $asset = Asset::create(['name' => 'Aset lama', 'condition' => 'Baik', 'nup' => '001']);
        $employee = User::factory()->create();
        $this->actingAs($employee)->post(route('placement.store', $asset), ['room_id' => $room->id, 'version' => 1, 'reason' => 'Pemeriksaan'])->assertForbidden();
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'Koordinator']);
        RoleAssignment::create(['user_id' => $admin->id, 'role_id' => $role->id, 'is_global' => true, 'can_administer' => true]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin)->withSession(['mfa.user_id' => $admin->id, 'mfa.secret_hash' => hash('sha256', $admin->two_factor_secret)]);
        $this->post(route('placement.store', $asset), ['room_id' => $room->id, 'version' => 1, 'reason' => 'Lokasi dikonfirmasi melalui pemeriksaan fisik', 'name' => 'Tidak boleh berubah'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'name' => 'Aset lama', 'nup' => '001', 'room_id' => $room->id, 'version' => 2]);
        $this->assertDatabaseHas('asset_location_histories', ['asset_id' => $asset->id, 'from_room_id' => null, 'to_room_id' => $room->id]);
        $this->post(route('placement.store', $asset), ['room_id' => $room->id, 'version' => 1, 'reason' => 'Ulang'])->assertUnprocessable();
    }
}
