<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Bast;
use App\Models\EmployeeProfile;
use App\Models\InventorySession;
use App\Models\LoanRequest;
use App\Models\MaintenanceLog;
use App\Models\Media;
use App\Models\OrganizationUnit;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkRecord;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

class SimonWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationUnit $unit;

    private Room $room;

    private AssetCategory $category;

    private User $employee;

    private User $coordinator;

    private User $keeper;

    private Asset $asset;

    private function fixtures(): void
    {
        $this->freezeTime();
        $this->unit = OrganizationUnit::create(['name' => 'Unit pengujian', 'code' => 'TEST']);
        $this->room = Room::create(['name' => 'Ruang A', 'code' => 'A', 'unit_id' => $this->unit->id]);
        $this->category = AssetCategory::create(['name' => 'Peralatan', 'code' => 'TEST']);
        $this->employee = $this->user();
        $this->coordinator = $this->user('Koordinator');
        $this->keeper = $this->user('Penanggung Jawab Ruangan');
        $this->asset = Asset::create(['name' => 'Laptop uji', 'category_id' => $this->category->id, 'room_id' => $this->room->id, 'condition' => 'Baik', 'is_loanable' => true, 'status' => 'active']);
    }

    public function test_employee_cannot_create_edit_or_delete_asset_but_can_view_cross_region(): void
    {
        $this->fixtures();
        $foreignUnit = OrganizationUnit::create(['name' => 'Unit lain', 'code' => 'OTHER']);
        $foreignRoom = Room::create(['name' => 'Ruang lain', 'unit_id' => $foreignUnit->id]);
        $foreign = Asset::create(['name' => 'Rahasia', 'room_id' => $foreignRoom->id, 'condition' => 'Baik']);
        $this->authenticatedAs($this->employee)->get(route('assets.create'))->assertForbidden();
        $this->get(route('assets.edit', $this->asset))->assertForbidden();
        $this->get(route('assets.show', $foreign))->assertOk();
        $this->get(route('assets.qrcode', $foreign))->assertOk();
        $this->delete(route('assets.destroy', $this->asset))->assertForbidden();
        $this->assertDatabaseHas('assets', ['id' => $this->asset->id]);
        $this->get(route('assets.index', ['search' => 'Rahasia']))->assertInertia(fn (Assert $page) => $page->component('Assets/Index')->has('assets.data', 1));
    }

    public function test_room_keeper_can_view_approvals_queue_and_approve_loan_for_their_room(): void
    {
        $this->fixtures();
        $this->authenticatedAs($this->employee)->post(route('loans.store'), $this->loanData())->assertSessionHasNoErrors();
        $loan = LoanRequest::firstOrFail();

        // Keeper can access loans.approvals and see the loan
        $this->authenticatedAs($this->keeper)
            ->get(route('loans.approvals'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Loans/Index')->has('loans.data', 1));

        // Keeper can approve the loan
        $this->post(route('loans.approve', $loan))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_requests', ['id' => $loan->id, 'status' => 'approved']);
        $this->assertDatabaseHas('reservations', ['asset_id' => $this->asset->id, 'status' => 'active']);
    }

    public function test_expired_role_does_not_grant_write_permission(): void
    {
        $this->fixtures();
        $this->coordinator->roleAssignments()->update(['ends_at' => now()->subMinute()]);
        $this->authenticatedAs($this->coordinator)->get(route('assets.create'))->assertForbidden();
        $this->post(route('assets.store'), $this->assetData())->assertForbidden();
    }

    public function test_unverified_and_pending_users_cannot_access_operational_pages(): void
    {
        $this->fixtures();
        $this->employee->forceFill(['email_verified_at' => null])->save();
        $this->authenticatedAs($this->employee)->get(route('assets.index'))->assertRedirect(route('verification.notice'));
        $this->employee->update(['status' => 'pending']);
        $this->get(route('assets.index'))->assertRedirect(route('pending.notice'));
    }

    public function test_coordinator_can_create_asset_with_private_webp_photo(): void
    {
        $this->fixtures();
        Storage::fake('local');
        Storage::fake('public');
        $this->authenticatedAs($this->coordinator)->post(route('assets.store'), [...$this->assetData(), 'images' => [UploadedFile::fake()->image('photo.jpg', 500, 250)]])
            ->assertSessionHasNoErrors()->assertRedirect(route('assets.index'));
        $media = Media::firstOrFail();
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->getRawOriginal('file_path'));
        Storage::disk('local')->assertExists($media->source_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertStringStartsWith(route('media.show', $media), $media->file_path);
        $this->authenticatedAs($this->employee)->get(route('media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_stale_asset_update_is_rejected_without_changing_data(): void
    {
        $this->fixtures();
        $this->asset->update(['version' => 2]);
        $this->authenticatedAs($this->coordinator)->put(route('assets.update', $this->asset), [...$this->assetData(), 'version' => 1])
            ->assertSessionHasErrors('version');
        $this->assertDatabaseHas('assets', ['id' => $this->asset->id, 'name' => 'Laptop uji', 'version' => 2]);
    }

    public function test_valid_asset_update_persists_fields_and_increments_version(): void
    {
        $this->fixtures();
        $this->authenticatedAs($this->coordinator)->put(route('assets.update', $this->asset), [...$this->assetData(), 'version' => 1])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['id' => $this->asset->id, 'name' => 'Aset baru', 'version' => 2, 'is_loanable' => true]);
    }

    public function test_duplicate_loan_items_are_rejected(): void
    {
        $this->fixtures();
        $this->authenticatedAs($this->employee)->post(route('loans.store'), [...$this->loanData(), 'asset_ids' => [$this->asset->id, $this->asset->id]])
            ->assertSessionHasErrors('asset_ids.0');
        $this->assertDatabaseCount('loan_requests', 0);
    }

    public function test_loan_submission_does_not_reserve_and_cannot_be_approved_by_borrower(): void
    {
        $this->fixtures();
        $this->authenticatedAs($this->employee)->post(route('loans.store'), $this->loanData())->assertSessionHasNoErrors();
        $loan = LoanRequest::firstOrFail();
        $this->assertDatabaseCount('reservations', 0);
        $role = Role::where('name', 'Koordinator')->firstOrFail();
        RoleAssignment::create(['user_id' => $this->employee->id, 'role_id' => $role->id, 'unit_id' => $this->unit->id]);
        $this->post(route('loans.approve', $loan))->assertForbidden();
        $this->assertDatabaseHas('loan_requests', ['id' => $loan->id, 'status' => 'pending_approval']);
    }

    public function test_conflicting_approval_is_atomic_and_keeps_request_pending(): void
    {
        $this->fixtures();
        $loan = $this->loan();
        $second = Asset::create([...$this->assetData(), 'name' => 'Aset kedua']);
        $loan->items()->create(['asset_id' => $second->id, 'status' => 'pending']);
        Reservation::create(['asset_id' => $second->id, 'start_date' => today(), 'end_date' => today()->addDays(2), 'status' => 'active']);
        $this->authenticatedAs($this->coordinator)->post(route('loans.approve', $loan))->assertSessionHasErrors('workflow');
        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseCount('basts', 0);
        $this->assertDatabaseHas('loan_requests', ['id' => $loan->id, 'status' => 'pending_approval']);
        $this->assertSame(2, $loan->items()->where('status', 'pending')->count());
    }

    public function test_full_loan_handover_and_return_requires_separate_roles_and_inspection(): void
    {
        $this->fixtures();
        Storage::fake('local');
        $loan = $this->loan();
        $item = $loan->items()->firstOrFail();
        $this->authenticatedAs($this->coordinator)->post(route('loans.approve', $loan))->assertSessionHasNoErrors();
        $this->post(route('loans.approve', $loan))->assertSessionHasErrors('workflow');
        $this->assertDatabaseCount('reservations', 1);
        $bast = Bast::firstOrFail();
        $this->authenticatedAs($this->keeper)->post($this->action($loan, 'prepare'), ['item_ids' => [$item->id], 'notes' => 'Adaptor lengkap'])->assertSessionHasNoErrors();
        $this->post($this->action($loan, 'handover'), ['item_ids' => [$item->id]])->assertSessionHasErrors('workflow');
        $this->authenticatedAs($this->employee)->post(route('basts.action', ['bast' => $bast->id, 'action' => 'upload']), ['document' => UploadedFile::fake()->create('signed.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->coordinator)->post(route('basts.action', ['bast' => $bast->id, 'action' => 'verify']))->assertSessionHasNoErrors();
        $this->authenticatedAs($this->keeper)->post($this->action($loan, 'handover'), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('asset_occupancies', ['asset_id' => $this->asset->id, 'is_active' => true]);
        $this->authenticatedAs($this->employee)->post($this->action($loan, 'accept'), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->post($this->action($loan, 'request-return'), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', ['loan_item_id' => $item->id, 'status' => 'active']);
        $this->authenticatedAs($this->coordinator)->post($this->action($loan, 'close'), ['item_ids' => [$item->id]])->assertSessionHasErrors('workflow');
        $this->authenticatedAs($this->keeper)->post($this->action($loan, 'inspect'), ['item_ids' => [$item->id], 'condition' => 'Rusak Ringan', 'completeness' => 'complete', 'notes' => 'Gores pada casing'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('basts', ['reference_id' => $loan->id, 'bast_type' => 'return']);
        $this->assertSame('needs_repair', $item->fresh()->status);
        $this->authenticatedAs($this->coordinator)->post($this->action($loan, 'close'), ['item_ids' => [$item->id]])->assertSessionHasErrors('workflow');
        $this->assertDatabaseHas('asset_occupancies', ['asset_id' => $this->asset->id, 'is_active' => true]);
        $incident = WorkRecord::where('kind', 'incidents')->firstOrFail();
        $this->assertSame($item->id, $incident->data['loan_item_id']);
        $this->assertNotNull($item->fresh()->inspected_at);
        $this->post(route('records.action', [$incident, 'reject']), ['version' => 1, 'notes' => 'Lewati tindak lanjut'])->assertUnprocessable();
        $this->post(route('records.action', [$incident, 'approve']), ['version' => 1, 'notes' => 'Perlu perawatan'])->assertSessionHasNoErrors();
        $repair = ['asset_id' => $this->asset->id, 'incident_id' => $incident->id, 'description' => 'Perbaikan casing', 'start_date' => today()->toDateString()];
        $this->post(route('maintenance.store'), $repair)->assertSessionHasNoErrors();
        $maintenance = MaintenanceLog::firstOrFail();
        $this->assertDatabaseCount('asset_occupancies', 1);
        $this->post(route('maintenance.store'), $repair)->assertUnprocessable();
        $this->assertDatabaseCount('maintenance_logs', 1);
        $this->post(route('records.action', [$incident, 'resolve']), ['version' => $incident->fresh()->version, 'notes' => 'Belum selesai'])->assertUnprocessable();
        $this->authenticatedAs($this->keeper)->post(route('records.action', [$incident, 'inspect-resolution']), ['version' => $incident->fresh()->version, 'condition' => 'Baik', 'notes' => 'Lewati perawatan'])->assertUnprocessable();
        $this->post(route('maintenance.complete', $maintenance), ['condition' => 'Baik', 'notes' => 'Sudah diperbaiki, kondisi baik'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('maintenance_logs', ['id' => $maintenance->id, 'loan_item_id' => $item->id, 'incident_id' => $incident->id, 'inspected_by' => $this->keeper->id, 'status' => 'completed']);
        $this->assertDatabaseHas('asset_occupancies', ['asset_id' => $this->asset->id, 'is_active' => true]);
        $this->authenticatedAs($this->coordinator)->post(route('records.action', [$incident, 'resolve']), ['version' => $incident->fresh()->version, 'notes' => 'Kondisi aktual disetujui'])->assertSessionHasNoErrors();

        $this->authenticatedAs($this->employee)->post($this->action($loan, 'request-return'), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->keeper)->post($this->action($loan, 'inspect'), ['item_ids' => [$item->id], 'condition' => 'Baik', 'completeness' => 'complete', 'notes' => 'Pemeriksaan ulang setelah perbaikan'])->assertSessionHasNoErrors();
        $returnDocument = Bast::whereMorphedTo('reference', $loan)->where('bast_type', 'return')->firstOrFail();
        $this->authenticatedAs($this->employee)->post(route('basts.action', [$returnDocument, 'upload']), ['document' => UploadedFile::fake()->create('pengembalian.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->coordinator)->post(route('basts.action', [$returnDocument, 'verify']))->assertSessionHasNoErrors();
        $this->authenticatedAs($this->coordinator)->post($this->action($loan, 'close'), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_requests', ['id' => $loan->id, 'status' => 'completed']);
        $this->assertDatabaseHas('assets', ['id' => $this->asset->id, 'condition' => 'Baik']);
        $this->assertDatabaseMissing('asset_occupancies', ['asset_id' => $this->asset->id, 'is_active' => true]);
        $this->assertDatabaseHas('basts', ['reference_id' => $loan->id, 'bast_type' => 'return', 'status' => 'verified']);
        $this->assertDatabaseHas('audit_events', ['action' => 'loan.close']);
    }

    public function test_return_cannot_include_another_requests_item(): void
    {
        $this->fixtures();
        $loan = $this->loan();
        $loan->update(['status' => 'active']);
        $foreign = $this->loan();
        $foreignItem = $foreign->items()->firstOrFail();
        $this->authenticatedAs($this->employee)->post(route('loans.return', $loan), ['item_ids' => [$foreignItem->id]])->assertSessionHasErrors('workflow');
        $this->assertDatabaseHas('loan_items', ['id' => $foreignItem->id, 'status' => 'pending']);
    }

    public function test_inventory_cannot_close_with_unchecked_items_and_employee_cannot_check(): void
    {
        $this->fixtures();
        Storage::fake('local');
        $this->authenticatedAs($this->coordinator)->post(route('inventory.store'), ['name' => 'Stock opname', 'start_date' => today()->toDateString(), 'asset_ids' => [$this->asset->id]])->assertSessionHasNoErrors();
        $session = InventorySession::firstOrFail();
        $item = $session->items()->firstOrFail();
        $proof = ['notes' => 'Dokumen sesuai pemeriksaan', 'officer_name' => 'Pejabat uji', 'reference_number' => 'UJI/2026', 'signed_date' => today()->toDateString()];
        $this->post(route('inventory.review', [$session, 'start']), [...$proof, 'version' => $session->fresh()->version, 'document' => UploadedFile::fake()->create('disposisi.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->employee)->post(route('inventory.check', $item), ['status' => 'found'])->assertForbidden();
        $this->authenticatedAs($this->coordinator)->post(route('inventory.close', $session))->assertSessionHasErrors('workflow');
        $this->authenticatedAs($this->keeper)->post(route('inventory.check', $item), ['status' => 'found'])->assertSessionHasNoErrors();
        $this->post(route('inventory.review', [$session, 'submit']), [...$proof, 'version' => $session->fresh()->version, 'document' => UploadedFile::fake()->create('kertas-kerja.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->authenticatedAs($this->coordinator)->post(route('inventory.close', $session))->assertSessionHasErrors('workflow');
        foreach (['sub-review', 'coordinate-review', 'authorize'] as $action) {
            $this->post(route('inventory.review', [$session, $action]), [...$proof, 'version' => $session->fresh()->version, 'document' => UploadedFile::fake()->create('review.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        }
        $this->authenticatedAs($this->coordinator)->post(route('inventory.close', $session))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory_sessions', ['id' => $session->id, 'status' => 'closed']);
        $snapshot = $session->fresh()->final_snapshot;
        $this->asset->update(['name' => 'Nama master baru', 'condition' => 'Rusak Berat']);
        $document = app(DocumentService::class)->create($session->fresh(), $this->coordinator, 'dbr');
        $this->assertSame($snapshot['items'], $document->snapshot['items']);
        $this->assertSame('Baik', $document->snapshot['items'][0]['condition']);
        $this->get(route('inventory.show', $session))->assertInertia(fn ($page) => $page->missing('session.reviews.0.path')->missing('session.reviews.0.checksum'));
        $this->get(route('workspace.index', 'inventory'))->assertInertia(fn ($page) => $page->missing('records.data.0.reviews'));
        $this->get(route('inventory.evidence', [$session, 0]))->assertDownload();
        $this->authenticatedAs($this->employee)->get(route('inventory.evidence', [$session, 0]))->assertForbidden();
        $this->authenticatedAs($this->keeper)->post(route('inventory.check', $item), ['status' => 'found'])->assertUnprocessable();
    }

    public function test_operational_module_pages_render_with_real_queries(): void
    {
        $this->fixtures();
        $this->authenticatedAs($this->coordinator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard')->has('stats.account_approvals'));
        foreach (['custody', 'inventory', 'maintenance', 'disposals', 'spip', 'transfers', 'incidents', 'registers', 'documents', 'reports', 'audit', 'notifications'] as $module) {
            $this->get(route('workspace.index', $module))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Operations/Index')->where('module', $module));
        }
        $this->get(route('imports.index'))->assertOk();
        $this->get(route('administration.index'))->assertForbidden();
    }

    public function test_employee_cannot_open_admin_or_operational_reports(): void
    {
        $this->fixtures();
        $this->authenticatedAs($this->employee)->get(route('administration.index'))->assertForbidden();
        $this->get(route('workspace.index', 'reports'))->assertForbidden();
        $this->get(route('reports.export'))->assertForbidden();
        $this->get(route('loans.approvals'))->assertForbidden();
    }

    public function test_reset_request_does_not_disclose_registered_addresses(): void
    {
        $this->fixtures();
        Notification::fake();
        $this->post(route('password.email'), ['email' => $this->employee->email])->assertSessionHasNoErrors()->assertSessionHas('status', 'Jika email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.');
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertSessionHasNoErrors()->assertSessionHas('status', 'Jika email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.');
    }

    public function test_draft_submission_can_be_retried_without_duplicate_loans(): void
    {
        $this->fixtures();
        $payload = [...$this->loanData(), 'draft' => true, 'submission_key' => (string) Str::uuid()];
        $this->authenticatedAs($this->employee)->post(route('loans.store'), $payload)->assertSessionHasNoErrors();
        $loan = LoanRequest::firstOrFail();
        $this->assertSame('draft', $loan->status);
        $this->post(route('loans.store'), $payload)->assertRedirect(route('loans.show', $loan));
        $this->assertDatabaseCount('loan_requests', 1);
        $this->assertDatabaseCount('loan_items', 1);
        $this->post($this->action($loan, 'submit'))->assertSessionHasNoErrors();
        $this->assertSame('pending_approval', $loan->fresh()->status);
    }

    public function test_expired_draft_cannot_enter_the_approval_queue(): void
    {
        $this->fixtures();
        $loan = $this->loan();
        $loan->update(['status' => 'draft', 'start_date' => today()->subDay()->toDateString()]);
        $this->authenticatedAs($this->employee)->post($this->action($loan, 'submit'))->assertUnprocessable();
        $this->assertSame('draft', $loan->fresh()->status);
    }

    private function user(string $role = 'Pegawai'): User
    {
        $user = User::factory()->create();
        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        EmployeeProfile::create(['user_id' => $user->id, 'unit_id' => $this->unit->id]);
        $role = Role::firstOrCreate(['name' => $role]);
        RoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id, 'unit_id' => $this->unit->id, 'starts_at' => now()->subDay()]);

        return $user;
    }

    private function assetData(): array
    {
        return ['name' => 'Aset baru', 'category_id' => $this->category->id, 'room_id' => $this->room->id, 'condition' => 'Baik', 'is_loanable' => true];
    }

    private function loanData(): array
    {
        return ['purpose' => 'Penugasan lapangan', 'start_date' => today()->toDateString(), 'end_date' => today()->addDays(2)->toDateString(), 'asset_ids' => [$this->asset->id]];
    }

    private function loan(): LoanRequest
    {
        $loan = LoanRequest::create(['user_id' => $this->employee->id, 'purpose' => 'Penugasan lapangan', 'start_date' => today()->toDateString(), 'end_date' => today()->addDays(2)->toDateString(), 'status' => 'pending_approval']);
        $loan->items()->create(['asset_id' => $this->asset->id, 'status' => 'pending']);

        return $loan;
    }

    private function action(LoanRequest $loan, string $action): string
    {
        return route('loans.action', ['loan' => $loan->id, 'action' => $action]);
    }

    private function authenticatedAs(User $user): static
    {
        $this->actingAs($user);
        $this->withSession(['mfa.user_id' => $user->id, 'mfa.secret_hash' => hash('sha256', (string) $user->two_factor_secret)]);

        return $this;
    }
}
