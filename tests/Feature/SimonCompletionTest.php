<?php

namespace Tests\Feature;

use App\Jobs\ProcessMedia;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetOccupancy;
use App\Models\AssetStaging;
use App\Models\Bast;
use App\Models\CustodyAssignment;
use App\Models\EmployeeProfile;
use App\Models\InventorySession;
use App\Models\LoanRequest;
use App\Models\MaintenanceLog;
use App\Models\OrganizationUnit;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Room;
use App\Models\SpipRecord;
use App\Models\User;
use App\Models\WorkRecord;
use App\Services\DocumentService;
use App\Services\MediaService;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SimonCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_revision_checks_version_and_preserves_previous_values_in_audit(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Keperluan lama', 'start_date' => today(), 'end_date' => today()->addDay(), 'status' => 'draft']);
        $loan->items()->create(['asset_id' => $asset->id, 'status' => 'pending']);
        $data = ['purpose' => 'Keperluan baru', 'start_date' => today()->toDateString(), 'end_date' => today()->addDays(2)->toDateString(), 'asset_ids' => [$asset->id], 'version' => 1, 'draft' => false];

        $this->authenticatedAs($employee)->post(route('loans.update', $loan), $data)->assertRedirect(route('loans.show', $loan));
        $this->assertDatabaseHas('loan_requests', ['id' => $loan->id, 'purpose' => 'Keperluan baru', 'status' => 'pending_approval', 'version' => 2]);
        $this->assertDatabaseHas('audit_events', ['action' => 'loan.draft_revised']);
        $this->post(route('loans.update', $loan), $data)->assertUnprocessable();
    }

    public function test_same_submission_key_with_different_content_is_rejected(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $data = ['submission_key' => (string) Str::uuid(), 'purpose' => 'Lapangan', 'start_date' => today()->toDateString(), 'end_date' => today()->addDay()->toDateString(), 'asset_ids' => [$asset->id]];
        $this->authenticatedAs($employee)->post(route('loans.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('loans.store'), [...$data, 'purpose' => 'Berbeda'])->assertUnprocessable();
        $this->assertDatabaseCount('loan_requests', 1);
    }

    public function test_xlsx_preserves_text_identifiers_and_commits_after_preview(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([['name', 'category_id', 'room_id', 'item_code', 'nup', 'satker_code', 'condition'], ['Impor Excel', $asset->category_id, $asset->room_id, '001', '00001', '001234', 'Baik']]);
        $book->getActiveSheet()->getCell('E2')->setValueExplicit('00001', DataType::TYPE_STRING);
        $book->getActiveSheet()->getCell('D2')->setValueExplicit('001', DataType::TYPE_STRING);
        $book->getActiveSheet()->getCell('F2')->setValueExplicit('00123456789012345678', DataType::TYPE_STRING);
        ob_start();
        (new Xlsx($book))->save('php://output');
        $bytes = ob_get_clean();

        $this->authenticatedAs($coordinator)->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('assets.xlsx', $bytes)])->assertSessionHasNoErrors();
        $batch = WorkRecord::where('kind', 'import')->firstOrFail();
        Storage::disk('local')->assertExists($batch->data['source_path']);
        $this->get(route('imports.index', ['batch' => $batch->id]))->assertInertia(fn ($page) => $page->component('Operations/Import')->missing('batch.data')->missing('batches.data.0.data'));
        $this->post(route('imports.commit', $batch))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['name' => 'Impor Excel', 'nup' => '00001', 'item_code' => '001', 'satker_code' => '00123456789012345678']);
        $book->disconnectWorksheets();
    }

    public function test_xlsx_formula_is_rejected_without_creating_a_batch(): void
    {
        [$coordinator] = $this->fixtures();
        $book = new Spreadsheet;
        $book->getActiveSheet()->setCellValue('A1', '=1+1');
        ob_start();
        (new Xlsx($book))->save('php://output');
        $bytes = ob_get_clean();
        $this->authenticatedAs($coordinator)->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('formula.xlsx', $bytes)])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('work_records', 0);
        $book->disconnectWorksheets();
    }

    public function test_scanner_unavailable_prevents_upload_becoming_ready(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        config(['simon.scanner' => 'unavailable']);
        $this->authenticatedAs($employee)->post(route('workspace.store', 'incidents'), ['asset_id' => $asset->id, 'title' => 'Kondisi', 'category' => 'damage', 'notes' => 'Bukti', 'images' => [UploadedFile::fake()->image('photo.jpg')]])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseCount('work_records', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_media_job_retry_is_idempotent_and_original_remains_private(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Queue::fake([ProcessMedia::class]);
        config(['simon.media_async' => true]);
        $this->authenticatedAs($coordinator);
        $media = app(MediaService::class)->processAndSaveImage(UploadedFile::fake()->image('photo.png'), $asset);
        $this->assertSame('queued', $media->processing_status);
        Queue::assertPushed(ProcessMedia::class);
        app(MediaService::class)->processQueued($media->id);
        $checksum = $media->fresh()->variant_checksum;
        app(MediaService::class)->processQueued($media->id);
        $this->assertDatabaseCount('media', 1);
        $this->assertSame($checksum, $media->fresh()->variant_checksum);
        $this->assertCount(3, Storage::disk('local')->allFiles());
        $this->get(route('media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_spip_requires_evidence_and_independent_review(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $record = SpipRecord::create(['period' => '2026-09', 'asset_id' => $asset->id, 'risk_description' => 'Risiko', 'control_action' => 'Kontrol', 'assessor_id' => $coordinator->id, 'status' => 'open']);
        $this->authenticatedAs($coordinator)->post(route('spip.action', [$record, 'resolve']), ['version' => 1, 'notes' => 'Tanpa bukti'])->assertUnprocessable();
        $this->authenticatedAs($keeper)->post(route('spip.action', [$record, 'submit']), ['version' => 1, 'notes' => 'Dilaksanakan', 'document' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame('review', $record->fresh()->status);
        $this->authenticatedAs($coordinator)->post(route('spip.action', [$record, 'resolve']), ['version' => 2, 'notes' => 'Kontrol efektif berdasarkan pemeriksaan'])->assertSessionHasNoErrors();
        $this->assertSame('resolved', $record->fresh()->status);
    }

    public function test_document_replacement_keeps_verified_original_and_snapshot(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $assignment = CustodyAssignment::create(['asset_id' => $asset->id, 'user_id' => $employee->id, 'start_date' => today(), 'status' => 'pending']);
        $document = app(DocumentService::class)->create($assignment, $keeper, 'bast_personal');
        $this->authenticatedAs($keeper)->post(route('documents.action', [$document, 'upload']), ['document' => UploadedFile::fake()->create('bast.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->post(route('documents.action', [$document, 'verify']))->assertForbidden();
        $this->authenticatedAs($coordinator)->post(route('documents.action', [$document, 'verify']))->assertSessionHasNoErrors();
        $original = $document->fresh()->signed_pdf_path;
        $this->post(route('documents.action', [$document, 'replace']), ['reason' => 'Perubahan administratif'])->assertSessionHasNoErrors();
        $this->assertSame('verified', $document->fresh()->status);
        $this->assertSame($original, $document->fresh()->signed_pdf_path);
        $this->assertDatabaseHas('basts', ['supersedes_id' => $document->id, 'version' => 2, 'status' => 'draft']);
        Storage::disk('local')->assertExists($original);
    }

    public function test_reminders_are_deduplicated_and_stop_after_physical_receipt(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today()->subDays(2), 'end_date' => today(), 'status' => 'active']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'active']);
        $this->artisan('simon:reminders')->assertSuccessful();
        $this->artisan('simon:reminders')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);
        $item->update(['status' => 'inspected', 'physically_received_at' => now()]);
        $this->travel(1)->days();
        $this->artisan('simon:reminders')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_new_incident_blocks_handover_after_loan_approval(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today()->addDay(), 'status' => 'approved']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'prepared']);
        WorkRecord::create(['kind' => 'incidents', 'asset_id' => $asset->id, 'created_by' => $employee->id, 'title' => 'Kerusakan baru', 'status' => 'submitted', 'data' => []]);

        $this->authenticatedAs($keeper)->post(route('loans.action', [$loan, 'handover']), ['item_ids' => [$item->id]])->assertSessionHasErrors('workflow');
        $this->assertSame('prepared', $item->fresh()->status);
        $this->assertDatabaseCount('asset_occupancies', 0);
    }

    public function test_unscanned_pdf_cannot_be_verified_or_downloaded(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Storage::disk('local')->put('documents/pending.pdf', 'not-yet-scanned');
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'approved']);
        $loan->items()->create(['asset_id' => $asset->id, 'status' => 'approved']);
        $document = app(WorkflowService::class)->document($loan, $coordinator, 'loan');
        $document->update(['status' => 'uploaded', 'scan_status' => 'pending', 'signed_pdf_path' => 'documents/pending.pdf']);

        $this->authenticatedAs($coordinator)->post(route('basts.action', [$document, 'verify']))->assertUnprocessable();
        $this->get(route('basts.download', $document))->assertNotFound();
        $this->get(route('documents.show', ['document' => $document->id, 'signed' => 1]))->assertNotFound();
        $this->assertSame('uploaded', $document->fresh()->status);
    }

    public function test_replacing_return_document_preserves_item_coverage_and_parties(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'returning']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'inspected']);
        $document = app(WorkflowService::class)->document($loan, $keeper, 'return', [$item->id]);
        $document->update(['status' => 'rejected']);

        $this->authenticatedAs($coordinator)->post(route('documents.action', [$document, 'replace']), ['reason' => 'Unggah ulang hasil pindai yang jelas'])->assertSessionHasNoErrors();
        $replacement = Bast::where('supersedes_id', $document->id)->firstOrFail();
        $this->assertSame($document->snapshot, $replacement->snapshot);
        $this->assertSame($employee->name, $replacement->snapshot['issuer']);
        $this->assertSame($keeper->name, $replacement->snapshot['receiver']);
        $this->assertSame($employee->id, $replacement->received_by);
        $this->assertSame('draft', $replacement->status);
    }

    public function test_media_worker_cancels_when_uploader_access_is_revoked(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Queue::fake([ProcessMedia::class]);
        config(['simon.media_async' => true]);
        $this->authenticatedAs($coordinator);
        $media = app(MediaService::class)->processAndSaveImage(UploadedFile::fake()->image('photo.jpg'), $asset);
        $coordinator->update(['status' => 'suspended']);

        app(MediaService::class)->processQueued($media->id);
        $this->assertSame('cancelled', $media->fresh()->processing_status);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        Queue::assertPushed(ProcessMedia::class, fn ($job) => $job->mediaId === $media->id);
        $this->authenticatedAs($employee)->get(route('media.show', $media))->assertNotFound();
    }

    public function test_session_revocation_requires_password_and_does_not_touch_other_users(): void
    {
        [$coordinator, $keeper, $employee] = $this->fixtures();
        config(['session.driver' => 'database']);
        $this->authenticatedAs($employee);
        foreach ([$employee, $keeper] as $user) {
            DB::table('sessions')->insert(['id' => 'other-device-'.$user->id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Test device', 'payload' => base64_encode(serialize([])), 'last_activity' => now()->timestamp]);
        }
        $this->post(route('sessions.revoke'), ['current_password' => 'wrong-password'])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('sessions', ['id' => 'other-device-'.$employee->id]);

        $this->post(route('sessions.revoke'), ['current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-'.$employee->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'other-device-'.$keeper->id]);
        $this->assertDatabaseHas('audit_events', ['subject_id' => $employee->id, 'action' => 'security.sessions_revoked']);
    }

    public function test_spip_revision_archives_old_evidence_and_scopes_downloads(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $record = SpipRecord::create(['period' => '2026-09', 'asset_id' => $asset->id, 'risk_description' => 'Risiko', 'control_action' => 'Kontrol', 'assessor_id' => $coordinator->id, 'status' => 'open']);
        $this->authenticatedAs($keeper)->post(route('spip.action', [$record, 'submit']), ['version' => 1, 'notes' => 'Bukti awal', 'document' => UploadedFile::fake()->create('awal.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $original = Bast::whereMorphedTo('reference', $record)->firstOrFail();
        $this->authenticatedAs($coordinator)->get(route('documents.show', ['document' => $original->id, 'signed' => 1]))->assertDownload();
        $this->post(route('spip.action', [$record, 'revise']), ['version' => 2, 'notes' => 'Lengkapi bukti'])->assertSessionHasNoErrors();

        $this->authenticatedAs($keeper)->post(route('spip.action', [$record, 'submit']), ['version' => 3, 'notes' => 'Bukti lengkap', 'document' => UploadedFile::fake()->create('revisi.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $replacement = Bast::where('supersedes_id', $original->id)->firstOrFail();
        $this->assertSame(2, $replacement->version);
        $this->assertSame('rejected', $original->fresh()->status);
        Storage::disk('local')->assertExists([$original->signed_pdf_path, $replacement->signed_pdf_path]);
        $this->authenticatedAs($employee)->get(route('documents.show', ['document' => $original->id, 'signed' => 1]))->assertNotFound();
        $this->get(route('documents.index', ['spip' => $record->id]))->assertInertia(fn ($page) => $page->component('Operations/Documents')->has('documents.data', 0));
    }

    public function test_return_followup_is_visible_to_borrower_but_not_other_employees(): void
    {
        [$coordinator, $keeper, $employee, $asset, $loan, $item, $incident] = $this->damagedReturn();
        $this->authenticatedAs($employee)->get(route('workspace.index', 'incidents'))->assertInertia(fn ($page) => $page->has('records.data', 1)->where('records.data.0.id', $incident->id)->where('records.data.0.canReview', false));
        $this->get(route('loans.show', $loan))->assertInertia(fn ($page) => $page->where('returnFollowups.0.item_id', $item->id)->where('returnFollowups.0.status', 'submitted'));
        $outsider = User::factory()->create();
        EmployeeProfile::create(['user_id' => $outsider->id, 'unit_id' => $asset->room->unit_id]);
        RoleAssignment::create(['user_id' => $outsider->id, 'role_id' => Role::where('name', 'Pegawai')->firstOrFail()->id, 'unit_id' => $asset->room->unit_id]);

        $this->authenticatedAs($outsider)->get(route('workspace.index', 'incidents'))->assertInertia(fn ($page) => $page->has('records.data', 0));
        $this->get(route('loans.show', $loan))->assertForbidden();
        $this->assertDatabaseCount('work_records', 1);
    }

    public function test_good_return_does_not_create_damage_followup(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'returning']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'condition_before' => 'Baik', 'status' => 'return_requested']);

        $this->authenticatedAs($keeper)->post(route('loans.action', [$loan, 'inspect']), ['item_ids' => [$item->id], 'condition' => 'Baik', 'completeness' => 'complete', 'notes' => 'Lengkap'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('work_records', 0);
        $this->assertDatabaseHas('loan_items', ['id' => $item->id, 'status' => 'inspected', 'condition_after' => 'Baik']);
    }

    public function test_return_repair_rejects_unapproved_unreceived_and_unrelated_items(): void
    {
        [$coordinator, $keeper, $employee, $asset, $loan, $item, $incident] = $this->damagedReturn();
        $repair = ['asset_id' => $asset->id, 'incident_id' => $incident->id, 'description' => 'Perbaikan', 'start_date' => today()->toDateString()];
        $this->authenticatedAs($coordinator)->post(route('maintenance.store'), $repair)->assertUnprocessable();
        $incident->update(['status' => 'approved']);
        $item->update(['status' => 'active', 'physically_received_at' => null]);
        $this->post(route('maintenance.store'), $repair)->assertUnprocessable();
        $item->update(['status' => 'inspected', 'physically_received_at' => now()]);
        $other = Asset::create(['name' => 'Barang berbeda', 'category_id' => $asset->category_id, 'room_id' => $asset->room_id, 'condition' => 'Baik', 'status' => 'active']);
        $this->post(route('maintenance.store'), [...$repair, 'asset_id' => $other->id])->assertNotFound();
        $this->assertDatabaseCount('maintenance_logs', 0);
        $this->assertDatabaseCount('asset_occupancies', 1);
        $this->assertDatabaseMissing('audit_events', ['action' => 'maintenance.started']);
    }

    public function test_borrower_with_operational_roles_cannot_process_own_return_followup(): void
    {
        [$coordinator, $keeper, $employee, $asset, $loan, $item, $incident] = $this->damagedReturn();
        foreach (['Koordinator', 'Penanggung Jawab Ruangan'] as $role) {
            RoleAssignment::create(['user_id' => $employee->id, 'role_id' => Role::where('name', $role)->firstOrFail()->id, 'unit_id' => $asset->room->unit_id]);
        }
        $this->authenticatedAs($employee)->post(route('records.action', [$incident, 'approve']), ['version' => 1, 'notes' => 'Pinjaman sendiri'])->assertForbidden();
        $incident->update(['status' => 'approved']);
        $this->post(route('records.action', [$incident, 'inspect-resolution']), ['version' => 1, 'notes' => 'Pemeriksaan sendiri', 'condition' => 'Baik'])->assertForbidden();
        $this->post(route('records.action', [$incident, 'resolve']), ['version' => 1, 'notes' => 'Penutupan sendiri'])->assertForbidden();
        $repair = ['asset_id' => $asset->id, 'incident_id' => $incident->id, 'description' => 'Perawatan', 'start_date' => today()->toDateString()];
        $this->post(route('maintenance.store'), $repair)->assertForbidden();
        $this->assertDatabaseCount('maintenance_logs', 0);
        $this->authenticatedAs($coordinator)->post(route('maintenance.store'), $repair)->assertSessionHasNoErrors();
        $maintenance = MaintenanceLog::firstOrFail();

        $this->authenticatedAs($employee)->post(route('maintenance.complete', $maintenance), ['condition' => 'Baik', 'notes' => 'Hasil sendiri'])->assertForbidden();
        $this->assertDatabaseHas('maintenance_logs', ['id' => $maintenance->id, 'status' => 'in_progress', 'inspected_by' => null]);
        $this->assertDatabaseHas('work_records', ['id' => $incident->id, 'status' => 'approved']);
        $this->assertSame('Rusak Ringan', $asset->fresh()->condition);
    }

    public function test_inventory_revision_preserves_evidence_and_rejects_stale_or_skipped_review(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $session = InventorySession::create(['name' => 'Sesi uji', 'start_date' => today(), 'status' => 'sub_review', 'created_by' => $coordinator->id]);
        $session->items()->create(['asset_id' => $asset->id, 'status' => 'found', 'checked_by' => $keeper->id, 'snapshot' => $asset->only(['name', 'condition'])]);
        $proof = ['version' => 1, 'notes' => 'Review', 'officer_name' => 'Pejabat uji', 'reference_number' => 'REF/1', 'signed_date' => today()->toDateString()];
        $this->authenticatedAs($coordinator)->post(route('inventory.review', [$session, 'sub-review']), $proof)->assertSessionHasErrors('document');
        $this->post(route('inventory.review', [$session, 'authorize']), [...$proof, 'document' => UploadedFile::fake()->create('skip.pdf', 10, 'application/pdf')])->assertUnprocessable();
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->post(route('inventory.review', [$session, 'sub-review']), [...$proof, 'document' => UploadedFile::fake()->create('review.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->post(route('inventory.review', [$session, 'revise']), ['version' => 1, 'notes' => 'Versi lama'])->assertUnprocessable();
        $this->post(route('inventory.review', [$session, 'revise']), ['version' => 2, 'notes' => 'Periksa ulang identitas'])->assertSessionHasNoErrors();
        $this->assertSame('active', $session->fresh()->status);
        $this->assertCount(2, $session->fresh()->reviews);
        Storage::disk('local')->assertExists($session->fresh()->reviews[0]['path']);
        $this->authenticatedAs($employee)->post(route('inventory.review', [$session, 'submit']), ['version' => 3, 'notes' => 'Bukan PJ'])->assertForbidden();
    }

    public function test_inventory_checker_cannot_review_own_work_with_second_role(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        RoleAssignment::create(['user_id' => $keeper->id, 'role_id' => Role::where('name', 'Koordinator')->firstOrFail()->id, 'unit_id' => $asset->room->unit_id]);
        $session = InventorySession::create(['name' => 'Pemeriksaan', 'start_date' => today(), 'status' => 'coordinator_review', 'created_by' => $coordinator->id]);
        $session->items()->create(['asset_id' => $asset->id, 'status' => 'found', 'checked_by' => $keeper->id]);

        $this->authenticatedAs($keeper)->post(route('inventory.review', [$session, 'coordinate-review']), ['version' => 1, 'notes' => 'Review sendiri'])->assertForbidden();
        $this->assertDatabaseHas('inventory_sessions', ['id' => $session->id, 'status' => 'coordinator_review', 'version' => 1]);
    }

    public function test_import_row_correction_is_scoped_versioned_and_revalidated_before_commit(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $batch = WorkRecord::create(['kind' => 'import', 'title' => 'Koreksi', 'status' => 'preview', 'created_by' => $coordinator->id, 'data' => []]);
        $raw = ['name' => 'Impor koreksi', 'category_id' => $asset->category_id, 'room_id' => 99999, 'item_code' => '0001', 'nup' => '0002', 'satker_code' => '001', 'condition' => 'Baik'];
        $row = AssetStaging::create(['import_batch_id' => (string) $batch->id, 'raw_data' => $raw, 'status' => 'error', 'validation_errors' => 'Ruangan tidak valid']);
        $data = [...$raw, 'room_id' => $asset->room_id, 'reason' => 'Sesuaikan lokasi sumber', 'row_checksum' => hash('sha256', json_encode($row->raw_data))];
        $this->authenticatedAs($employee)->post(route('imports.correct', [$batch, $row]), $data)->assertForbidden();
        $this->authenticatedAs($coordinator)->post(route('imports.correct', [$batch, $row]), $data)->assertSessionHasNoErrors();
        $this->assertSame('validated', $row->fresh()->status);
        $this->assertDatabaseCount('assets', 1);
        $this->assertDatabaseHas('audit_events', ['subject_id' => $row->id, 'action' => 'import.row_corrected']);
        $this->post(route('imports.correct', [$batch, $row]), $data)->assertUnprocessable();
        $this->post(route('imports.commit', $batch))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['name' => 'Impor koreksi', 'nup' => '0002', 'room_id' => $asset->room_id]);
        $this->post(route('imports.correct', [$batch, $row]), [...$data, 'row_checksum' => hash('sha256', json_encode($row->fresh()->raw_data))])->assertUnprocessable();
    }

    public function test_availability_reports_conflicts_without_exposing_borrower_or_other_units(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $query = ['asset_ids' => [$asset->id], 'start_date' => today()->toDateString(), 'end_date' => today()->addDays(2)->toDateString()];
        $this->authenticatedAs($employee)->getJson(route('loans.availability', $query))->assertOk()->assertJsonPath('assets.'.$asset->id.'.available', true);
        Reservation::create(['asset_id' => $asset->id, 'start_date' => today()->addDay(), 'end_date' => today()->addDays(3), 'status' => 'active']);
        $this->getJson(route('loans.availability', $query))->assertOk()->assertJsonPath('assets.'.$asset->id.'.available', false)->assertJsonCount(1, 'assets.'.$asset->id.'.reservations')->assertJsonMissingPath('assets.'.$asset->id.'.reservations.0.loan_item_id');
        $foreign = Asset::create(['name' => 'Barang luar cakupan', 'condition' => 'Baik', 'is_loanable' => true]);
        $this->getJson(route('loans.availability', [...$query, 'asset_ids' => [$foreign->id]]))->assertForbidden();
        $this->getJson(route('loans.availability', [...$query, 'end_date' => today()->addDays(93)->toDateString()]))->assertUnprocessable();
        $this->assertDatabaseCount('loan_requests', 0);
    }

    public function test_revocation_preserves_assignment_history_but_removes_access_and_sessions(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $coordinator->roleAssignments()->update(['can_administer' => true]);
        $assignment = $keeper->roleAssignments()->firstOrFail();
        $token = $keeper->remember_token;
        DB::table('sessions')->insert(['id' => 'keeper-device', 'user_id' => $keeper->id, 'payload' => '', 'last_activity' => now()->timestamp]);
        $this->authenticatedAs($coordinator)->post(route('administration.assignment.revoke', $assignment), ['reason' => 'Pergantian petugas', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertNull($assignment->fresh()->ends_at);
        $this->post(route('administration.assignment.revoke', $assignment), ['reason' => 'Pergantian petugas', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertModelExists($assignment);
        $this->assertNotNull($assignment->fresh()->ends_at);
        $this->assertFalse($keeper->fresh()->hasRole('Penanggung Jawab Ruangan'));
        $this->assertNotSame($token, $keeper->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'keeper-device']);
        $this->assertDatabaseHas('audit_events', ['action' => 'assignment.revoked', 'subject_id' => $assignment->id]);
        $this->post(route('administration.user', $keeper), ['status' => 'active', 'role' => 'Penanggung Jawab Ruangan', 'unit_id' => $asset->room->unit_id, 'reason' => 'Mandat baru'])->assertSessionHasNoErrors();
        $this->assertSame(2, $keeper->roleAssignments()->count());
        $this->assertNotNull($assignment->fresh()->ends_at);
    }

    public function test_coordinator_assignment_requires_global_admin_and_password_confirmation(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $coordinator->roleAssignments()->update(['can_administer' => true]);
        $data = ['status' => 'active', 'role' => 'Koordinator', 'unit_id' => $asset->room->unit_id, 'reason' => 'Penugasan baru'];
        $this->authenticatedAs($coordinator)->post(route('administration.user', $employee), $data)->assertForbidden();
        $coordinator->roleAssignments()->update(['is_global' => true]);
        $this->post(route('administration.user', $employee), $data)->assertSessionHasErrors('current_password');
        $this->assertFalse($employee->fresh()->hasRole('Koordinator'));
        $this->post(route('administration.user', $employee), [...$data, 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertTrue($employee->fresh()->hasRole('Koordinator'));
        $this->post(route('administration.assignment.revoke', $coordinator->roleAssignments()->firstOrFail()), ['reason' => 'Mandat sendiri', 'current_password' => 'password'])->assertForbidden();
    }

    public function test_return_photo_is_private_webp_and_pending_processing_blocks_closure(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Queue::fake([ProcessMedia::class]);
        config(['simon.media_async' => true]);
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'returning']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'inspected', 'condition_after' => 'Baik', 'physically_received_at' => now()]);
        $document = app(WorkflowService::class)->document($loan, $keeper, 'return', [$item->id]);
        $document->update(['status' => 'verified']);
        $payload = ['parent_type' => 'loan-item', 'parent_id' => $item->id, 'images' => [UploadedFile::fake()->image('bukti.jpg')]];
        $this->authenticatedAs($employee)->post(route('media.evidence'), $payload)->assertForbidden();
        $this->authenticatedAs($keeper)->post(route('media.evidence'), $payload)->assertSessionHasNoErrors();
        $photo = $item->media()->firstOrFail();
        Queue::assertPushed(ProcessMedia::class, fn ($job) => $job->mediaId === $photo->id);
        $this->authenticatedAs($coordinator)->post(route('loans.action', [$loan, 'close']), ['item_ids' => [$item->id]])->assertSessionHasErrors('workflow');
        app(MediaService::class)->processQueued($photo->id);
        $this->authenticatedAs($employee)->get(route('media.show', $photo))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->authenticatedAs(User::factory()->create())->get(route('media.show', $photo))->assertNotFound();
        $this->authenticatedAs($coordinator)->post(route('loans.action', [$loan, 'close']), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->assertSame('completed', $loan->fresh()->status);
        $this->authenticatedAs($keeper)->post(route('media.evidence'), ['parent_type' => 'loan-item', 'parent_id' => $item->id, 'images' => [UploadedFile::fake()->image('baru.jpg')]])->assertUnprocessable();
        $this->assertDatabaseCount('media', 1);
    }

    public function test_incomplete_good_return_still_creates_followup(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'returning']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'return_requested', 'checklist' => ['notes' => 'Adaptor dan tas lengkap']]);
        $data = ['item_ids' => [$item->id], 'condition' => 'Baik', 'notes' => 'Adaptor belum dikembalikan'];
        $this->authenticatedAs($keeper)->post(route('loans.action', [$loan, 'inspect']), $data)->assertSessionHasErrors('completeness');
        $this->post(route('loans.action', [$loan, 'inspect']), [...$data, 'completeness' => 'incomplete'])->assertSessionHasNoErrors();
        $this->assertSame('incomplete', $item->fresh()->checklist['return_completeness']);
        $this->assertSame('Adaptor dan tas lengkap', $item->fresh()->checklist['notes']);
        $this->assertDatabaseHas('work_records', ['kind' => 'incidents', 'asset_id' => $asset->id, 'status' => 'submitted']);
        $this->assertSame('Baik', $asset->fresh()->condition);
    }

    public function test_inventory_photos_require_keeper_and_finished_processing_before_submission(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Queue::fake([ProcessMedia::class]);
        config(['simon.media_async' => true]);
        $session = InventorySession::create(['name' => 'Foto inventarisasi', 'start_date' => today(), 'status' => 'active', 'created_by' => $coordinator->id]);
        $item = $session->items()->create(['asset_id' => $asset->id, 'status' => 'found', 'checked_by' => $keeper->id]);
        $data = ['parent_type' => 'inventory-item', 'parent_id' => $item->id, 'images' => [UploadedFile::fake()->image('inventaris.jpg')]];
        $this->authenticatedAs($employee)->post(route('media.evidence'), $data)->assertForbidden();
        $this->authenticatedAs($keeper)->post(route('media.evidence'), $data)->assertSessionHasNoErrors();
        Queue::assertPushed(ProcessMedia::class);
        $proof = ['version' => $session->fresh()->version, 'notes' => 'Kertas kerja', 'reference_number' => 'KK/1', 'signed_date' => today()->toDateString(), 'document' => UploadedFile::fake()->create('kk.pdf', 10, 'application/pdf')];
        $this->post(route('inventory.review', [$session, 'submit']), $proof)->assertUnprocessable();
        $this->assertSame('active', $session->fresh()->status);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        app(MediaService::class)->processQueued($item->media()->firstOrFail()->id);
        $this->post(route('inventory.review', [$session, 'submit']), $proof)->assertSessionHasNoErrors();
        $this->assertSame('sub_review', $session->fresh()->status);
    }

    public function test_report_snapshot_exports_frozen_identifiers_as_text_and_checks_current_scope(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $asset->update(['name' => '=1+1', 'nup' => '0007']);
        $this->authenticatedAs($coordinator)->post(route('reports.store'), ['title' => 'Laporan uji', 'period' => today()->format('Y-m'), 'room_id' => $asset->room_id])->assertSessionHasNoErrors();
        $report = WorkRecord::where('kind', 'report_snapshot')->firstOrFail();
        $asset->update(['name' => 'Master baru', 'nup' => '99']);
        $data = $report->data;
        $data['snapshot'][0] = array_reverse($data['snapshot'][0], true);
        $report->update(['data' => $data]);
        $response = $this->get(route('reports.download', [$report, 'xlsx']))->assertDownload();
        $file = UploadedFile::fake()->createWithContent('laporan.xlsx', $response->streamedContent());
        $book = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx)->load($file->getRealPath());
        $this->assertSame('=1+1', $book->getActiveSheet()->getCell('B6')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $book->getActiveSheet()->getCell('B6')->getDataType());
        $this->assertSame('0007', $book->getActiveSheet()->getCell('E6')->getValue());
        $book->disconnectWorksheets();
        $this->get(route('reports.download', [$report, 'pdf']))->assertDownload();
        $this->authenticatedAs($employee)->get(route('reports.download', [$report, 'xlsx']))->assertNotFound();
        $asset->update(['room_id' => null]);
        $this->authenticatedAs($coordinator)->get(route('reports.download', [$report, 'xlsx']))->assertForbidden();
    }

    public function test_successor_can_replace_cancelled_return_evidence_without_bypassing_scan_or_erasing_history(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Queue::fake([ProcessMedia::class]);
        config(['simon.media_async' => true]);
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'returning']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'inspected', 'condition_after' => 'Baik', 'physically_received_at' => now()]);
        app(WorkflowService::class)->document($loan, $keeper, 'return', [$item->id])->update(['status' => 'verified']);
        $this->authenticatedAs($keeper)->post(route('media.evidence'), ['parent_type' => 'loan-item', 'parent_id' => $item->id, 'images' => [UploadedFile::fake()->image('lama.jpg')]])->assertSessionHasNoErrors();
        $old = $item->media()->firstOrFail();
        $keeper->roleAssignments()->update(['ends_at' => now()->subSecond()]);
        app(MediaService::class)->processQueued($old->id);
        RoleAssignment::create(['user_id' => $coordinator->id, 'role_id' => Role::where('name', 'Penanggung Jawab Ruangan')->firstOrFail()->id, 'unit_id' => $asset->room->unit_id]);
        $payload = ['reason' => 'Penggantian petugas dan pengambilan ulang bukti', 'images' => [UploadedFile::fake()->image('pengganti.png')]];
        $this->authenticatedAs($employee)->post(route('media.replace', $old), $payload)->assertNotFound();
        $this->authenticatedAs($keeper)->post(route('media.replace', $old), $payload)->assertNotFound();

        $this->authenticatedAs($coordinator)->post(route('media.replace', $old), $payload)->assertSessionHasNoErrors();

        $replacement = $item->media()->whereKey($old->fresh()->replacement_media_id)->firstOrFail();
        $this->assertDatabaseHas('media', ['id' => $old->id, 'processing_status' => 'cancelled', 'replaced_by' => $coordinator->id, 'replacement_reason' => $payload['reason']]);
        $this->assertDatabaseHas('audit_events', ['action' => 'evidence.replaced']);
        Storage::disk('local')->assertExists($old->source_path);
        Queue::assertPushed(ProcessMedia::class, fn ($job) => $job->mediaId === $replacement->id);
        $this->post(route('loans.action', [$loan, 'close']), ['item_ids' => [$item->id]])->assertSessionHasErrors('workflow');
        $this->post(route('media.replace', $old), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('media', 2);
        app(MediaService::class)->processQueued($replacement->id);
        $this->get(route('media.show', $old))->assertNotFound();
        $this->get(route('media.show', $replacement))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->post(route('loans.action', [$loan, 'close']), ['item_ids' => [$item->id]])->assertSessionHasNoErrors();
        $this->assertSame('completed', $loan->fresh()->status);
        $this->assertDatabaseCount('media', 2);
    }

    public function test_failed_inventory_evidence_replacement_increments_version_and_remains_a_submission_gate(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        Queue::fake([ProcessMedia::class]);
        config(['simon.media_async' => true]);
        $session = InventorySession::create(['name' => 'Bukti pengganti', 'start_date' => today(), 'status' => 'active', 'created_by' => $coordinator->id]);
        $item = $session->items()->create(['asset_id' => $asset->id, 'status' => 'found', 'checked_by' => $keeper->id]);
        $this->authenticatedAs($keeper);
        $old = app(MediaService::class)->processAndSaveImage(UploadedFile::fake()->image('lama.jpg'), $item, 'inventory');
        $old->update(['processing_status' => 'failed', 'scan_status' => 'failed']);
        $payload = ['reason' => 'Foto lama gagal diproses, diambil ulang', 'images' => [UploadedFile::fake()->image('baru.jpg')]];

        $this->post(route('media.replace', $old), $payload)->assertSessionHasNoErrors();

        $this->assertSame(2, $session->fresh()->version);
        $replacement = $item->media()->whereKey($old->fresh()->replacement_media_id)->firstOrFail();
        $this->assertFalse($old->fresh()->can_retry);
        $this->post(route('media.retry', $old))->assertUnprocessable();
        app(MediaService::class)->processQueued($old->id);
        $this->assertSame('failed', $old->fresh()->processing_status);
        $proof = ['version' => 2, 'notes' => 'Kertas kerja', 'reference_number' => 'KK/1', 'signed_date' => today()->toDateString(), 'document' => UploadedFile::fake()->create('kk.pdf', 10, 'application/pdf')];
        $this->post(route('inventory.review', [$session, 'submit']), $proof)->assertUnprocessable();
        app(MediaService::class)->processQueued($replacement->id);
        $this->post(route('inventory.review', [$session, 'submit']), $proof)->assertSessionHasNoErrors();
        $this->assertSame('sub_review', $session->fresh()->status);
        $replacement->update(['processing_status' => 'failed']);
        $this->post(route('media.replace', $replacement), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('media', 2);
        Queue::assertPushed(ProcessMedia::class, fn ($job) => $job->mediaId === $replacement->id);
    }

    public function test_replacement_rejects_guests_invalid_payloads_and_non_failed_photos_without_new_files(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        config(['simon.media_async' => false]);
        $session = InventorySession::create(['name' => 'Validasi bukti', 'start_date' => today(), 'status' => 'active', 'created_by' => $coordinator->id]);
        $item = $session->items()->create(['asset_id' => $asset->id, 'status' => 'found', 'checked_by' => $keeper->id]);
        $this->authenticatedAs($keeper);
        $photo = app(MediaService::class)->processAndSaveImage(UploadedFile::fake()->image('bukti.jpg'), $item, 'inventory');
        $files = Storage::disk('local')->allFiles();
        auth()->logout();
        $this->post(route('media.replace', $photo))->assertRedirect(route('login'));
        $this->authenticatedAs(User::factory()->create())->post(route('media.replace', $photo))->assertNotFound();
        $this->authenticatedAs($coordinator)->post(route('media.replace', $photo))->assertNotFound();
        $this->authenticatedAs($keeper)->post(route('media.replace', $photo), [])->assertSessionHasErrors(['reason', 'images']);
        $payload = ['reason' => 'Mengganti bukti yang tidak bermasalah', 'images' => [UploadedFile::fake()->image('baru.jpg')]];
        $this->post(route('media.replace', $photo), $payload)->assertUnprocessable();
        $photo->update(['processing_status' => 'queued']);
        $this->post(route('media.replace', $photo), $payload)->assertUnprocessable();
        $photo->update(['processing_status' => 'failed']);
        $this->post(route('media.replace', $photo), ['reason' => 'Pendek', 'images' => [UploadedFile::fake()->create('virus.txt', 1, 'text/plain')]])->assertSessionHasErrors(['reason', 'images.0']);
        $this->assertDatabaseCount('media', 1);
        $this->assertDatabaseMissing('audit_events', ['action' => 'evidence.replaced']);
        $this->assertSame($files, Storage::disk('local')->allFiles());
    }

    public function test_verified_identity_transition_preserves_asset_id_transactions_and_old_alias(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $asset->update(['satker_code' => '001', 'item_code' => '02', 'nup' => '0007']);
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Riwayat', 'start_date' => today(), 'end_date' => today(), 'status' => 'draft']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'status' => 'pending']);
        $record = $this->submitIdentity($keeper, $asset);
        $this->assertSame('0007', $asset->fresh()->nup);
        $this->authenticatedAs($employee)->get(route('identities.evidence', $record))->assertNotFound();
        $this->authenticatedAs($coordinator)->get(route('identities.evidence', $record))->assertDownload();
        $this->get(route('workspace.index', 'registers'))->assertInertia(fn ($page) => $page->missing('records.data.0.data.document_path')->missing('records.data.0.data.document_checksum'));
        $this->post(route('records.action', [$record, 'approve']), ['version' => 1, 'notes' => 'Cocok'])->assertSessionHasErrors('mapping_verified');
        $this->post(route('records.action', [$record, 'approve']), ['version' => 1, 'notes' => 'Bukti dan fisik cocok', 'mapping_verified' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('0007', $asset->fresh()->nup);
        $this->post(route('records.action', [$record, 'resolve']), ['version' => 2, 'notes' => 'Lewati penerapan'])->assertUnprocessable();

        $this->post(route('identities.apply', $record), ['version' => 2, 'notes' => 'Pemetaan terverifikasi diterapkan'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'satker_code' => '002', 'item_code' => '03', 'nup' => '0009', 'version' => 2]);
        $this->assertSame($asset->id, $item->fresh()->asset_id);
        $alias = $asset->identifiers()->firstOrFail();
        $this->assertSame(['satker_code' => '001', 'item_code' => '02', 'nup' => '0007'], $alias->identity_snapshot);
        $this->assertNull($alias->assigned_date);
        $this->assertSame($record->id, $alias->work_record_id);
        $this->assertSame('applied', $record->fresh()->data['mapping_status']);
        $this->assertDatabaseHas('audit_events', ['action' => 'asset.identity_transitioned', 'subject_id' => $asset->id]);
        $this->get(route('assets.show', $asset))->assertInertia(fn ($page) => $page->where('asset.identifiers.0.identity_snapshot.nup', '0007'));
        $this->post(route('identities.apply', $record), ['version' => 2, 'notes' => 'Ulang'])->assertUnprocessable();
        $this->assertDatabaseCount('asset_identifiers', 1);
    }

    public function test_identity_transition_rejects_self_approval_and_foreign_scope(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $record = $this->submitIdentity($coordinator, $asset);
        $this->post(route('records.action', [$record, 'approve']), ['version' => 1, 'notes' => 'Usulan sendiri', 'mapping_verified' => '1'])->assertForbidden();
        $this->post(route('identities.apply', $record), ['version' => 1, 'notes' => 'Terapkan sendiri'])->assertForbidden();
        $this->authenticatedAs($keeper)->post(route('identities.apply', $record), ['version' => 1, 'notes' => 'Tanpa hak'])->assertNotFound();
        $this->authenticatedAs(User::factory()->create())->get(route('identities.evidence', $record))->assertNotFound();
        $this->post(route('identities.store'), ['asset_id' => $asset->id])->assertNotFound();
        $this->assertDatabaseCount('asset_identifiers', 0);
        $this->assertSame('submitted', $record->fresh()->status);
        $this->assertNull($asset->fresh()->identity_verified_at);
    }

    public function test_identity_transition_refuses_stale_master_and_duplicate_target_without_partial_changes(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $record = $this->submitIdentity($keeper, $asset);
        $this->authenticatedAs($coordinator)->post(route('records.action', [$record, 'approve']), ['version' => 1, 'notes' => 'Cocok', 'mapping_verified' => '1'])->assertSessionHasNoErrors();
        $duplicate = Asset::create(['name' => 'Identitas telah dipakai', 'category_id' => $asset->category_id, 'room_id' => $asset->room_id, 'condition' => 'Baik', 'status' => 'active', 'satker_code' => '002', 'item_code' => '03', 'nup' => '0009']);
        $this->post(route('identities.apply', $record), ['version' => 2, 'notes' => 'Bentrok'])->assertSessionHasErrors('nup');
        $this->assertDatabaseCount('asset_identifiers', 0);
        $duplicate->update(['nup' => '0010']);
        $asset->increment('version');
        $this->post(route('identities.apply', $record), ['version' => 2, 'notes' => 'Data kedaluwarsa'])->assertUnprocessable();
        $this->assertSame('approved', $record->fresh()->status);
        $this->assertNull($asset->fresh()->nup);
        $this->assertDatabaseCount('asset_identifiers', 0);
    }

    public function test_asset_edit_cannot_bypass_identity_history_and_duplicate_creation_is_rejected(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $asset->update(['satker_code' => '001', 'item_code' => '02', 'nup' => '0007']);
        $data = ['name' => 'Aset duplikat', 'category_id' => $asset->category_id, 'room_id' => $asset->room_id, 'condition' => 'Baik', 'satker_code' => '001', 'item_code' => '02', 'nup' => '0007'];
        $this->authenticatedAs($coordinator)->post(route('assets.store'), $data)->assertSessionHasErrors('nup');
        $this->put(route('assets.update', $asset), [...$data, 'nup' => '0008', 'version' => 1])->assertSessionHasErrors('nup');
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'nup' => '0007', 'version' => 1]);
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_identity_transition_requires_complete_evidence_and_rejects_tampered_archive(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $this->authenticatedAs($keeper)->post(route('identities.store'), ['asset_id' => $asset->id])->assertSessionHasErrors(['document', 'source_status', 'satker_code', 'nup']);
        $this->assertDatabaseCount('work_records', 0);
        $record = $this->submitIdentity($keeper, $asset);
        $this->authenticatedAs($coordinator)->post(route('records.action', [$record, 'approve']), ['version' => 1, 'notes' => 'Cocok', 'mapping_verified' => '1'])->assertSessionHasNoErrors();
        Storage::disk('local')->put($record->data['document_path'], 'changed');
        $this->post(route('identities.apply', $record), ['version' => 2, 'notes' => 'Bukti berubah'])->assertUnprocessable();
        $this->get(route('identities.evidence', $record))->assertUnprocessable();
        $this->assertDatabaseCount('asset_identifiers', 0);
        $this->assertNull($asset->fresh()->identity_verified_at);
    }

    public function test_damaged_custody_return_requires_evidence_followup_and_independent_closure(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $assignment = CustodyAssignment::create(['asset_id' => $asset->id, 'user_id' => $employee->id, 'assigned_by' => $coordinator->id, 'start_date' => today(), 'status' => 'return_requested', 'condition_before' => 'Baik']);
        AssetOccupancy::create(['asset_id' => $asset->id, 'user_id' => $employee->id, 'occupancy_type' => 'custody', 'starts_at' => now(), 'is_active' => true]);
        $payload = ['condition' => 'Rusak Ringan', 'completeness' => 'incomplete', 'notes' => 'Layar bergaris dan adaptor kurang', 'document' => UploadedFile::fake()->create('kembali.pdf', 10, 'application/pdf')];
        $this->authenticatedAs($keeper)->post(route('custody.action', [$assignment, 'receive']), $payload)->assertSessionHasNoErrors();
        $incident = WorkRecord::where('kind', 'incidents')->firstOrFail();
        $this->assertSame($assignment->id, $incident->data['custody_assignment_id']);
        $this->authenticatedAs($employee)->get(route('custody.evidence', [$assignment, 'return']))->assertDownload();
        $this->get(route('workspace.index', 'custody'))->assertInertia(fn ($page) => $page->missing('records.data.0.return_evidence_path')->missing('records.data.0.return_evidence_checksum'));
        $this->authenticatedAs(User::factory()->create())->get(route('custody.evidence', [$assignment, 'return']))->assertNotFound();
        $this->authenticatedAs($coordinator)->post(route('custody.revoke', $assignment), ['notes' => 'Tutup terlalu awal'])->assertUnprocessable();
        $this->post(route('records.action', [$incident, 'reject']), ['version' => 1, 'notes' => 'Tidak boleh meniadakan temuan'])->assertUnprocessable();
        $this->post(route('records.action', [$incident, 'approve']), ['version' => 1, 'notes' => 'Perbaikan disetujui'])->assertSessionHasNoErrors();
        $this->post(route('maintenance.store'), ['asset_id' => $asset->id, 'incident_id' => $incident->id, 'description' => 'Perbaikan dan penggantian adaptor', 'start_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $maintenance = MaintenanceLog::firstOrFail();
        $this->assertSame($assignment->id, $maintenance->custody_assignment_id);
        $this->assertDatabaseCount('asset_occupancies', 1);
        $this->authenticatedAs($keeper)->post(route('maintenance.complete', $maintenance), ['condition' => 'Baik', 'notes' => 'Diperbaiki dan adaptor lengkap'])->assertSessionHasNoErrors();
        $this->authenticatedAs($coordinator)->post(route('custody.revoke', $assignment), ['notes' => 'Belum review hasil'])->assertUnprocessable();
        $this->post(route('records.action', [$incident, 'resolve']), ['version' => $incident->fresh()->version, 'notes' => 'Hasil PJ diverifikasi'])->assertSessionHasNoErrors();
        $this->post(route('custody.revoke', $assignment), ['notes' => 'BAST dan tindak lanjut sesuai'])->assertSessionHasNoErrors();
        $this->assertSame('revoked', $assignment->fresh()->status);
        $this->assertDatabaseMissing('asset_occupancies', ['asset_id' => $asset->id, 'is_active' => true]);
        Storage::disk('local')->assertExists($assignment->fresh()->return_evidence_path);
    }

    public function test_custody_holder_cannot_handle_own_return_even_with_operational_roles(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        foreach (['Koordinator', 'Penanggung Jawab Ruangan'] as $role) {
            RoleAssignment::create(['user_id' => $employee->id, 'role_id' => Role::where('name', $role)->firstOrFail()->id, 'unit_id' => $asset->room->unit_id]);
        }
        $assignment = CustodyAssignment::create(['asset_id' => $asset->id, 'user_id' => $employee->id, 'assigned_by' => $coordinator->id, 'start_date' => today(), 'status' => 'return_requested']);
        $payload = ['condition' => 'Baik', 'completeness' => 'incomplete', 'notes' => 'Adaptor kurang', 'document' => UploadedFile::fake()->create('kembali.pdf', 10, 'application/pdf')];
        $this->authenticatedAs($employee)->post(route('custody.action', [$assignment, 'receive']), $payload)->assertForbidden();
        $this->authenticatedAs($keeper)->post(route('custody.action', [$assignment, 'receive']), $payload)->assertSessionHasNoErrors();
        $incident = WorkRecord::where('kind', 'incidents')->firstOrFail();
        $this->authenticatedAs($coordinator)->post(route('records.action', [$incident, 'approve']), ['version' => 1, 'notes' => 'Lengkapi adaptor'])->assertSessionHasNoErrors();
        $this->authenticatedAs($employee)->post(route('maintenance.store'), ['asset_id' => $asset->id, 'incident_id' => $incident->id, 'description' => 'Urus sendiri', 'start_date' => today()->toDateString()])->assertForbidden();
        $this->post(route('records.action', [$incident, 'inspect-resolution']), ['version' => 2, 'condition' => 'Baik', 'notes' => 'Nilai sendiri'])->assertForbidden();
        $this->post(route('custody.revoke', $assignment), ['notes' => 'Tutup sendiri'])->assertForbidden();
        $this->assertDatabaseCount('maintenance_logs', 0);
        $this->assertSame('approved', $incident->fresh()->status);
        $this->assertSame('Baik', $asset->fresh()->condition);
    }

    public function test_custody_return_validation_and_evidence_integrity_prevent_unsafe_closure(): void
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        Storage::fake('local');
        $assignment = CustodyAssignment::create(['asset_id' => $asset->id, 'user_id' => $employee->id, 'assigned_by' => $coordinator->id, 'start_date' => today(), 'status' => 'return_requested']);
        $this->authenticatedAs($keeper)->post(route('custody.action', [$assignment, 'receive']), ['condition' => 'Baik', 'notes' => 'Lengkap'])->assertSessionHasErrors('document');
        $this->assertSame('return_requested', $assignment->fresh()->status);
        $payload = ['condition' => 'Baik', 'notes' => 'Lengkap', 'document' => UploadedFile::fake()->create('kembali.pdf', 10, 'application/pdf')];
        $this->post(route('custody.action', [$assignment, 'receive']), $payload)->assertSessionHasErrors('completeness');
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->post(route('custody.action', [$assignment, 'receive']), [...$payload, 'completeness' => 'complete'])->assertSessionHasNoErrors();
        RoleAssignment::create(['user_id' => $keeper->id, 'role_id' => Role::where('name', 'Koordinator')->firstOrFail()->id, 'unit_id' => $asset->room->unit_id]);
        $this->post(route('custody.revoke', $assignment), ['notes' => 'Pemeriksa menutup sendiri'])->assertForbidden();
        Storage::disk('local')->put($assignment->fresh()->return_evidence_path, 'tampered');
        $this->authenticatedAs($coordinator)->post(route('custody.revoke', $assignment), ['notes' => 'Bukti berubah'])->assertUnprocessable();
        $this->get(route('custody.evidence', [$assignment, 'return']))->assertUnprocessable();
        $this->assertSame('received', $assignment->fresh()->status);
        $this->assertDatabaseCount('work_records', 0);
    }

    private function submitIdentity(User $actor, Asset $asset): WorkRecord
    {
        $this->authenticatedAs($actor)->post(route('identities.store'), ['asset_id' => $asset->id, 'title' => 'Transisi uji', 'notes' => 'Rekonsiliasi identitas aset yang sama', 'register_type' => 'ASP', 'reference_number' => 'ASP/01', 'reference_date' => today()->toDateString(), 'source_status' => 'Selesai menurut dokumen sumber', 'bast_number' => 'BAST/01', 'satker_code' => '002', 'item_code' => '03', 'nup' => '0009', 'document' => UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();

        return WorkRecord::where('kind', 'registers')->latest('id')->firstOrFail();
    }

    private function damagedReturn(): array
    {
        [$coordinator, $keeper, $employee, $asset] = $this->fixtures();
        $loan = LoanRequest::create(['user_id' => $employee->id, 'purpose' => 'Tugas', 'start_date' => today(), 'end_date' => today(), 'status' => 'returning']);
        $item = $loan->items()->create(['asset_id' => $asset->id, 'condition_before' => 'Baik', 'status' => 'return_requested']);
        AssetOccupancy::create(['asset_id' => $asset->id, 'user_id' => $employee->id, 'occupancy_type' => 'loan', 'starts_at' => now(), 'is_active' => true]);
        $this->authenticatedAs($keeper)->post(route('loans.action', [$loan, 'inspect']), ['item_ids' => [$item->id], 'condition' => 'Rusak Ringan', 'completeness' => 'complete', 'notes' => 'Goresan baru'])->assertSessionHasNoErrors();
        $incident = WorkRecord::where('kind', 'incidents')->firstOrFail();

        return [$coordinator, $keeper, $employee, $asset, $loan, $item, $incident];
    }

    private function fixtures(): array
    {
        $this->freezeTime();
        $unit = OrganizationUnit::create(['name' => 'Unit pengujian', 'code' => 'TEST']);
        $room = Room::create(['name' => 'Ruangan pengujian', 'unit_id' => $unit->id]);
        $category = AssetCategory::create(['name' => 'Peralatan', 'code' => 'TEST']);
        $users = [];
        foreach (['Koordinator', 'Penanggung Jawab Ruangan', 'Pegawai'] as $name) {
            $user = User::factory()->create();
            app(EnableTwoFactorAuthentication::class)($user);
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
            EmployeeProfile::create(['user_id' => $user->id, 'unit_id' => $unit->id]);
            RoleAssignment::create(['user_id' => $user->id, 'role_id' => Role::firstOrCreate(['name' => $name])->id, 'unit_id' => $unit->id]);
            $users[] = $user;
        }
        $asset = Asset::create(['name' => 'Laptop pengujian', 'category_id' => $category->id, 'room_id' => $room->id, 'condition' => 'Baik', 'status' => 'active', 'is_loanable' => true]);

        return [...$users, $asset];
    }

    private function authenticatedAs(User $user): static
    {
        $this->actingAs($user)->withSession(['mfa.user_id' => $user->id, 'mfa.secret_hash' => hash('sha256', (string) $user->two_factor_secret)]);

        return $this;
    }
}
