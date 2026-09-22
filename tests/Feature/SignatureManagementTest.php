<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Bast;
use App\Models\EmployeeProfile;
use App\Models\LoanRequest;
use App\Models\OrganizationUnit;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignatureManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function create1x1PngBase64(): string
    {
        // 1x1 transparent PNG base64
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    public function test_user_can_upload_digital_signature_with_password(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'password' => Hash::make('secret-pass-123'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.signature.update'), [
            'password' => 'secret-pass-123',
            'agreement' => '1',
            'signature_image' => $this->create1x1PngBase64(),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));

        $profile = EmployeeProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertNotNull($profile->signature_path);
        $this->assertTrue(Storage::disk('local')->exists($profile->signature_path));

        // Stream signature
        $sigResponse = $this->actingAs($user)->get(route('profile.signature'));
        $sigResponse->assertOk();
        $sigResponse->assertHeader('Content-Type', 'image/png');
    }

    public function test_user_cannot_upload_signature_with_wrong_password(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'password' => Hash::make('secret-pass-123'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.signature.update'), [
            'password' => 'wrong-pass',
            'agreement' => '1',
            'signature_image' => $this->create1x1PngBase64(),
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNull(EmployeeProfile::where('user_id', $user->id)->first()?->signature_path);
    }

    public function test_user_can_delete_signature_with_password(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'password' => Hash::make('secret-pass-123'),
        ]);

        $path = 'signatures/test.png';
        Storage::disk('local')->put($path, 'dummy-signature');
        EmployeeProfile::create(['user_id' => $user->id, 'signature_path' => $path]);

        $response = $this->actingAs($user)->delete(route('profile.signature.destroy'), [
            'password' => 'secret-pass-123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->profile->signature_path);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_digital_bast_signing_workflow(): void
    {
        Storage::fake('local');

        $pass = 'secure-password';

        // 1. Roles & Org Unit
        $coordRole = Role::firstOrCreate(['name' => 'Koordinator BMN', 'scope' => 'system']);
        $keeperRole = Role::firstOrCreate(['name' => 'Penanggung Jawab Ruangan', 'scope' => 'room']);
        $unit = OrganizationUnit::create(['name' => 'Subbagian Tata Usaha', 'code' => 'TU-01']);

        $room = Room::create(['name' => 'Ruang Operasional', 'code' => 'RO-01', 'unit_id' => $unit->id]);

        // 2. Users with signatures
        $peminjam = User::factory()->create(['password' => Hash::make($pass), 'name' => 'Peminjam User']);
        $pj = User::factory()->create(['password' => Hash::make($pass), 'name' => 'PJ Ruangan User']);
        $koor = User::factory()->create(['password' => Hash::make($pass), 'name' => 'Koordinator User']);

        RoleAssignment::create(['user_id' => $koor->id, 'role_id' => $coordRole->id, 'is_global' => true]);
        RoleAssignment::create(['user_id' => $pj->id, 'role_id' => $keeperRole->id, 'room_id' => $room->id]);

        $dummyPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        foreach ([$peminjam, $pj, $koor] as $idx => $u) {
            $sigPath = 'signatures/u'.$idx.'.png';
            Storage::disk('local')->put($sigPath, $dummyPng);
            EmployeeProfile::create(['user_id' => $u->id, 'signature_path' => $sigPath]);
        }

        // 3. Asset and Loan
        $asset = Asset::create([
            'name' => 'Kamera Lapangan',
            'room_id' => $room->id,
            'condition' => 'Baik',
            'nup' => '0005',
            'item_code' => '3.05.01.04.005',
        ]);

        $loan = LoanRequest::create([
            'user_id' => $peminjam->id,
            'purpose' => 'Dokumentasi patroli',
            'start_date' => today(),
            'end_date' => today()->addDays(3),
            'status' => 'approved',
        ]);
        $loan->items()->create(['asset_id' => $asset->id, 'status' => 'approved']);

        $bast = Bast::create([
            'bast_number' => 'BAST-2026-TEST01',
            'bast_type' => 'loan',
            'reference_type' => LoanRequest::class,
            'reference_id' => $loan->id,
            'status' => 'draft',
            'issued_by' => $koor->id,
            'received_by' => $peminjam->id,
            'snapshot' => ['purpose' => $loan->purpose],
        ]);

        // Peminjam signs
        $res1 = $this->actingAs($peminjam)->post(route('basts.sign', $bast), [
            'password' => $pass,
            'role' => 'peminjam',
        ]);
        $res1->assertSessionHasNoErrors();
        $this->assertSame('draft', $bast->fresh()->status);
        $this->assertNotNull(data_get($bast->fresh()->snapshot, 'signatures.peminjam'));

        // PJ signs
        $res2 = $this->actingAs($pj)->post(route('basts.sign', $bast), [
            'password' => $pass,
            'role' => 'pj',
        ]);
        $res2->assertSessionHasNoErrors();
        $this->assertSame('draft', $bast->fresh()->status);
        $this->assertNotNull(data_get($bast->fresh()->snapshot, 'signatures.pj'));

        // Koordinator signs -> all 3 signed -> auto verified!
        $res3 = $this->actingAs($koor)->post(route('basts.sign', $bast), [
            'password' => $pass,
            'role' => 'koordinator',
        ]);
        $res3->assertSessionHasNoErrors();
        $freshBast = $bast->fresh();
        $this->assertSame('verified', $freshBast->status);
        $this->assertNotNull(data_get($freshBast->snapshot, 'signatures.koordinator'));

        // Can download digitally signed completed document
        $downRes = $this->actingAs($peminjam)->get(route('basts.download', $freshBast));
        $downRes->assertOk();
    }
}
