<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AuditEvent;
use App\Models\CustodyAssignment;
use App\Models\Disposal;
use App\Models\EmployeeProfile;
use App\Models\MaintenanceLog;
use App\Models\Media;
use App\Models\OrganizationUnit;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

class SimonOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private User $keeper;

    private User $employee;

    private Asset $asset;

    private Room $room;

    public function test_custody_keeps_asset_occupied_until_physical_return_and_coordinator_closure(): void
    {
        $this->fixtures();
        Storage::fake('local');
        $this->authenticatedAs($this->coordinator)->post(route('custody.assign', $this->asset), ['user_id' => $this->employee->id, 'start_date' => today()->toDateString(), 'notes' => 'Penetapan tugas'])->assertSessionHasNoErrors();
        $assignment = CustodyAssignment::firstOrFail();
        $this->assertSame('pending', $assignment->status);
        $this->assertDatabaseCount('asset_occupancies', 0);
        $this->authenticatedAs($this->keeper)->post(route('custody.action', [$assignment, 'handover']), ['document' => UploadedFile::fake()->create('bast.pdf', 10, 'application/pdf'), 'notes' => 'Lengkap dan baik'])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->employee)->post(route('custody.action', [$assignment, 'accept']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('custody_assignments', ['id' => $assignment->id, 'status' => 'active']);
        $this->authenticatedAs($this->coordinator)->post(route('custody.revoke', $assignment), ['notes' => 'Belum diterima'])->assertUnprocessable();
        $this->assertDatabaseHas('asset_occupancies', ['asset_id' => $this->asset->id, 'is_active' => true]);
        $this->authenticatedAs($this->employee)->post(route('custody.action', [$assignment, 'request-return']))->assertSessionHasNoErrors();
        $this->authenticatedAs($this->keeper)->post(route('custody.action', [$assignment, 'receive']), ['condition' => 'Baik', 'completeness' => 'complete', 'notes' => 'Diterima lengkap', 'document' => UploadedFile::fake()->create('kembali.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->coordinator)->post(route('custody.revoke', $assignment), ['notes' => 'Penugasan selesai'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('custody_assignments', ['id' => $assignment->id, 'status' => 'revoked']);
        $this->assertDatabaseMissing('asset_occupancies', ['asset_id' => $this->asset->id, 'is_active' => true]);
    }

    public function test_maintenance_does_not_automatically_mark_asset_good(): void
    {
        $this->fixtures();
        $this->asset->update(['condition' => 'Rusak Berat']);
        $this->authenticatedAs($this->coordinator)->post(route('maintenance.store'), ['asset_id' => $this->asset->id, 'description' => 'Servis', 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $record = MaintenanceLog::firstOrFail();
        $this->assertSame('in_progress', $record->status);
        $this->assertSame('Rusak Berat', $this->asset->fresh()->condition);
        $this->authenticatedAs($this->keeper)->post(route('maintenance.complete', $record), ['condition' => 'Rusak Ringan', 'notes' => 'Masih ada goresan'])->assertSessionHasNoErrors();
        $this->assertSame('Rusak Ringan', $this->asset->fresh()->condition);
        $this->assertSame('completed', $record->fresh()->status);
    }

    public function test_disposal_requires_independent_approval_and_decision_evidence(): void
    {
        $this->fixtures();
        Storage::fake('local');
        $this->authenticatedAs($this->employee)->post(route('disposal.propose', $this->asset), ['reason' => 'Rusak'])->assertForbidden();
        $this->authenticatedAs($this->keeper)->post(route('disposal.propose', $this->asset), ['reason' => 'Tidak dapat diperbaiki'])->assertSessionHasNoErrors();
        $record = Disposal::firstOrFail();
        $this->authenticatedAs($this->coordinator)->post(route('disposal.approve', $record), ['sk_number' => 'SK/1'])->assertSessionHasErrors(['document', 'sk_date', 'sk_issuer']);
        $this->assertSame('active', $this->asset->fresh()->status);
        $this->post(route('disposal.approve', $record), ['sk_number' => 'SK/1', 'sk_date' => today()->toDateString(), 'sk_issuer' => 'Pejabat penguji', 'document' => UploadedFile::fake()->create('sk.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['id' => $this->asset->id, 'status' => 'disposed']);
        Storage::disk('local')->assertExists($record->fresh()->evidence_path);
    }

    public function test_csv_import_previews_then_commits_only_once(): void
    {
        $this->fixtures();
        $csv = "name,category_id,room_id,item_code,nup,satker_code,condition\nBarang impor,{$this->asset->category_id},{$this->room->id},KODE,001,SATKER,Baik\n";
        $this->authenticatedAs($this->coordinator)->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('assets.csv', $csv)])->assertSessionHasNoErrors();
        $batch = WorkRecord::where('kind', 'import')->firstOrFail();
        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseHas('asset_stagings', ['import_batch_id' => (string) $batch->id, 'status' => 'validated']);
        $this->post(route('imports.commit', $batch))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assets', 2);
        $this->assertDatabaseHas('assets', ['name' => 'Barang impor', 'is_loanable' => false]);
        $this->post(route('imports.commit', $batch))->assertUnprocessable();
        $this->assertDatabaseCount('assets', 2);
    }

    public function test_invalid_import_row_prevents_partial_commit(): void
    {
        $this->fixtures();
        $csv = "name,category_id,room_id,item_code,nup,satker_code,condition\nValid,{$this->asset->category_id},{$this->room->id},KODE,001,SATKER,Baik\nInvalid,{$this->asset->category_id},999999,KODE,002,SATKER,Baik\n";
        $this->authenticatedAs($this->coordinator)->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('assets.csv', $csv)])->assertSessionHasNoErrors();
        $batch = WorkRecord::where('kind', 'import')->firstOrFail();
        $this->post(route('imports.commit', $batch))->assertUnprocessable();
        $this->assertDatabaseCount('assets', 1);
        $this->assertSame('preview', $batch->fresh()->status);
    }

    public function test_activation_requires_administrative_mandate_and_verified_email(): void
    {
        $this->fixtures();
        $this->employee->forceFill(['status' => 'pending', 'email_verified_at' => null])->save();
        $this->authenticatedAs($this->coordinator)->post(route('administration.user', $this->employee), ['status' => 'active', 'reason' => 'Verifikasi'])->assertForbidden();
        $this->coordinator->roleAssignments()->update(['can_administer' => true]);
        $this->post(route('administration.user', $this->employee), ['status' => 'active', 'reason' => 'Verifikasi'])->assertUnprocessable();
        $this->employee->forceFill(['email_verified_at' => now()])->save();
        $this->post(route('administration.user', $this->employee), ['status' => 'active', 'reason' => 'Email dan identitas cocok'])->assertSessionHasNoErrors();
        $this->assertSame('active', $this->employee->fresh()->status);
    }

    public function test_room_keeper_cannot_read_other_rooms_incident(): void
    {
        $this->fixtures();
        $otherRoom = Room::create(['name' => 'Ruangan lain', 'unit_id' => $this->room->unit_id]);
        $otherAsset = Asset::create(['name' => 'Barang lain', 'room_id' => $otherRoom->id, 'condition' => 'Baik']);
        $this->keeper->roleAssignments()->update(['room_id' => $this->room->id]);
        WorkRecord::create(['kind' => 'incidents', 'title' => 'Laporan rahasia ruangan lain', 'asset_id' => $otherAsset->id, 'unit_id' => $this->room->unit_id, 'created_by' => $this->coordinator->id, 'data' => []]);
        $this->authenticatedAs($this->keeper)->get(route('workspace.index', 'incidents'))->assertInertia(fn ($page) => $page->has('records.data', 0));
    }

    public function test_room_keeper_audit_excludes_other_rooms_events_in_the_same_unit(): void
    {
        $this->fixtures();
        $this->keeper->roleAssignments()->update(['room_id' => $this->room->id]);
        $otherRoom = Room::create(['name' => 'Ruangan lain', 'unit_id' => $this->room->unit_id]);
        $otherAsset = Asset::create(['name' => 'Barang lain', 'room_id' => $otherRoom->id, 'condition' => 'Baik']);
        AuditEvent::create(['user_id' => $this->coordinator->id, 'unit_id' => $this->room->unit_id, 'subject_type' => Asset::class, 'subject_id' => $otherAsset->id, 'action' => 'asset.updated', 'data' => []]);
        $visible = AuditEvent::create(['user_id' => $this->coordinator->id, 'unit_id' => $this->room->unit_id, 'subject_type' => Asset::class, 'subject_id' => $this->asset->id, 'action' => 'asset.updated', 'data' => []]);
        $this->authenticatedAs($this->keeper)->get(route('workspace.index', 'audit'))->assertInertia(fn ($page) => $page->has('records.data', 1)->where('records.data.0.id', $visible->id));
    }

    public function test_unit_admin_cannot_modify_global_accounts_or_extend_its_administrative_scope(): void
    {
        $this->fixtures();
        $this->coordinator->roleAssignments()->update(['can_administer' => true]);
        $this->keeper->roleAssignments()->update(['is_global' => true]);
        $this->authenticatedAs($this->coordinator)->post(route('administration.user', $this->keeper), ['status' => 'suspended', 'reason' => 'Percobaan perubahan'])->assertForbidden();
        $this->assertSame('active', $this->keeper->fresh()->status);
        $otherUnit = OrganizationUnit::create(['name' => 'Unit lain', 'code' => 'OTHER']);
        RoleAssignment::create(['user_id' => $this->coordinator->id, 'role_id' => Role::where('name', 'Koordinator')->firstOrFail()->id, 'unit_id' => $otherUnit->id, 'can_administer' => false]);
        $this->employee->profile->update(['unit_id' => $otherUnit->id]);
        $this->post(route('administration.user', $this->employee), ['status' => 'suspended', 'reason' => 'Bukan mandat admin'])->assertForbidden();
        $this->assertSame('active', $this->employee->fresh()->status);
    }

    public function test_suspension_revokes_remember_me_credentials(): void
    {
        $this->fixtures();
        $this->coordinator->roleAssignments()->update(['can_administer' => true]);
        $oldToken = $this->employee->remember_token;
        $this->authenticatedAs($this->coordinator)->post(route('administration.user', $this->employee), ['status' => 'suspended', 'reason' => 'Penugasan berakhir'])->assertSessionHasNoErrors();
        $this->assertSame('suspended', $this->employee->fresh()->status);
        $this->assertNotSame($oldToken, $this->employee->fresh()->remember_token);
    }

    public function test_incident_photos_are_converted_to_webp_and_hidden_from_unrelated_users(): void
    {
        $this->fixtures();
        Storage::fake('local');
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('bukti.jpg', 400, 300);
        $this->authenticatedAs($this->employee)->post(route('workspace.store', 'incidents'), ['asset_id' => $this->asset->id, 'title' => 'Kondisi layar', 'notes' => 'Goresan pada layar', 'category' => 'damage', 'images' => [$photo]])->assertSessionHasNoErrors();
        $media = Media::firstOrFail();
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->source_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->get(route('workspace.index', 'incidents'))->assertInertia(fn ($page) => $page->has('records.data.0.media', 1)->where('records.data.0.media.0.file_path', route('media.show', $media))->missing('records.data.0.media.0.source_path'));
        $this->get(route('media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->get(route('media.show', $media))->assertNotFound();
    }

    private function fixtures(): void
    {
        $this->freezeTime();
        $unit = OrganizationUnit::create(['name' => 'Unit tes', 'code' => 'TEST']);
        $this->room = Room::create(['name' => 'Ruangan tes', 'unit_id' => $unit->id]);
        $category = AssetCategory::create(['name' => 'Peralatan', 'code' => 'TEST']);
        foreach (['coordinator' => 'Koordinator', 'keeper' => 'Penanggung Jawab Ruangan', 'employee' => 'Pegawai'] as $property => $name) {
            $user = User::factory()->create();
            app(EnableTwoFactorAuthentication::class)($user);
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
            EmployeeProfile::create(['user_id' => $user->id, 'unit_id' => $unit->id]);
            $role = Role::firstOrCreate(['name' => $name]);
            RoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id, 'unit_id' => $unit->id]);
            $this->{$property} = $user;
        }
        $this->asset = Asset::create(['name' => 'Aset tes', 'category_id' => $category->id, 'room_id' => $this->room->id, 'condition' => 'Baik', 'status' => 'active', 'is_loanable' => true]);
    }

    private function authenticatedAs(User $user): static
    {
        $this->actingAs($user);
        $this->withSession(['mfa.user_id' => $user->id, 'mfa.secret_hash' => hash('sha256', (string) $user->two_factor_secret)]);

        return $this;
    }
}
