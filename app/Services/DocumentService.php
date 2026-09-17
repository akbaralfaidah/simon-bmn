<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\CustodyAssignment;
use App\Models\InventorySession;
use App\Models\LoanRequest;
use App\Models\SpipRecord;
use App\Models\User;
use App\Models\WorkRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public const TEMPLATES = ['loan' => 'Formulir Peminjaman BMN', 'return' => 'Formulir Pengembalian BMN', 'bast_personal' => 'BAST Penanggung Jawab BMN', 'bast_collective' => 'BAST Kolektif / Distribusi', 'appointment' => 'Penunjukan Pemegang BMN', 'inventory' => 'Kertas Kerja Inventarisasi', 'dbr' => 'Daftar Barang Ruangan', 'disposal' => 'Usulan Penghapusan BMN', 'report' => 'Laporan Barang Milik Negara'];

    public function __construct(private AccessScope $scope) {}

    public function assets(Model $parent): Collection
    {
        return match (true) {
            $parent instanceof LoanRequest, $parent instanceof InventorySession => $parent->items()->with('asset')->get()->pluck('asset')->filter(),
            $parent instanceof CustodyAssignment, $parent instanceof SpipRecord => collect([$parent->asset])->filter(),
            $parent instanceof WorkRecord && $parent->asset_id !== null => collect([$parent->asset])->filter(),
            $parent instanceof WorkRecord => Asset::whereIn('id', $parent->data['asset_ids'] ?? [])->get(),
            default => collect(),
        };
    }

    public function canView(User $user, ?Model $parent): bool
    {
        if (! $parent) {
            return false;
        }
        if ($parent instanceof LoanRequest) {
            return $this->scope->viewLoan($user, $parent);
        }
        if ($parent instanceof CustodyAssignment && $parent->user_id === $user->id) {
            return true;
        }
        if ($parent instanceof WorkRecord && $parent->created_by === $user->id) {
            return true;
        }
        $assets = $this->assets($parent);

        return $assets->isNotEmpty() && $assets->every(fn ($asset) => $this->scope->coordinate($user, $asset) || $this->scope->inspect($user, $asset));
    }

    public function canVerify(User $user, Bast $document): bool
    {
        if ($document->reference instanceof SpipRecord) {
            return false;
        }
        if ($document->reference instanceof LoanRequest) {
            return $this->scope->decideLoan($user, $document->reference);
        }
        $assets = $document->reference ? $this->assets($document->reference) : collect();

        return $document->issued_by !== $user->id && $document->received_by !== $user->id && $assets->isNotEmpty() && $assets->every(fn ($asset) => $this->scope->coordinate($user, $asset));
    }

    public function create(Model $parent, User $actor, string $template, ?Bast $supersedes = null, ?string $reason = null): Bast
    {
        abort_unless(isset(self::TEMPLATES[$template]) && $this->canView($actor, $parent), 403);

        return DB::transaction(function () use ($parent, $actor, $template, $supersedes, $reason) {
            if ($parent instanceof LoanRequest) {
                abort_unless($supersedes && in_array($template, ['loan', 'return'], true), 422);
                $document = app(WorkflowService::class)->document($parent, $actor, $template, $supersedes->snapshot['item_ids'] ?? []);
                $document->update(['version' => $supersedes->version + 1, 'supersedes_id' => $supersedes->id, 'review_notes' => $reason,
                    'issued_by' => $supersedes->issued_by, 'received_by' => $supersedes->received_by, 'snapshot' => $supersedes->snapshot]);
                AuditEvent::record($document, 'document.created', ['template' => $template, 'supersedes' => $supersedes->id, 'reason' => $reason]);

                return $document;
            }
            $assets = $this->assets($parent);
            $unit = $assets->first()?->room?->unit_id ?? 0;
            $key = strtoupper($template).'-'.$unit.'-'.now()->year;
            DB::table('document_sequences')->insertOrIgnore(['key' => $key, 'value' => 0]);
            $sequence = DB::table('document_sequences')->where('key', $key)->lockForUpdate()->first();
            DB::table('document_sequences')->where('key', $key)->increment('value');
            $items = $parent instanceof InventorySession ? ($parent->final_snapshot['items'] ?? $parent->items()->get()->map(fn ($item) => [...($item->snapshot ?? []), 'inspection_status' => $item->status, 'notes' => $item->notes])->all()) : ($parent instanceof WorkRecord && isset($parent->data['snapshot']) ? $parent->data['snapshot'] : $assets->map(fn ($asset) => [...$asset->only(['id', 'name', 'item_code', 'nup', 'condition', 'value']), 'room' => $asset->room?->name])->all());
            $document = Bast::create(['bast_number' => $key.'-'.str_pad((string) ($sequence->value + 1), 6, '0', STR_PAD_LEFT), 'bast_type' => $template, 'reference_type' => $parent::class, 'reference_id' => $parent->getKey(), 'status' => 'draft', 'issued_by' => $actor->id, 'received_by' => $parent instanceof CustodyAssignment ? $parent->user_id : null, 'version' => ($supersedes?->version ?? 0) + 1, 'supersedes_id' => $supersedes?->id, 'review_notes' => $reason, 'snapshot' => ['title' => self::TEMPLATES[$template], 'template_version' => 1, 'template_approved' => config('simon.templates_approved'), 'header' => config('simon.document_header'), 'issuer' => $actor->name, 'receiver' => $parent instanceof CustodyAssignment ? $parent->user->name : '', 'purpose' => $parent->title ?? $parent->name ?? $parent->notes ?? '', 'items' => $items]]);
            AuditEvent::record($document, 'document.created', ['template' => $template, 'supersedes' => $supersedes?->id, 'reason' => $reason], $unit ?: null);

            return $document;
        }, 3);
    }
}
