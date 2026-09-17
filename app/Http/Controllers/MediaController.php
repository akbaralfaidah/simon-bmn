<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMedia;
use App\Models\Asset;
use App\Models\AuditEvent;
use App\Models\InventoryItem;
use App\Models\InventorySession;
use App\Models\LoanItem;
use App\Models\LoanRequest;
use App\Models\Media;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function storeEvidence(Request $request, MediaService $service): RedirectResponse
    {
        $data = $request->validate(['parent_type' => 'required|in:loan-item,inventory-item', 'parent_id' => 'required|integer', 'images' => 'required|array|min:1|max:4', 'images.*' => 'required|image|mimes:jpeg,png,webp|max:10240|dimensions:max_width=12000,max_height=12000']);
        $class = $data['parent_type'] === 'loan-item' ? LoanItem::class : InventoryItem::class;
        $parent = $class::findOrFail($data['parent_id']);
        abort_unless($service->canUpload($request->user(), $parent), 403);
        $photos = [];
        try {
            DB::transaction(function () use ($request, $parent, $service, &$photos) {
                if ($parent instanceof LoanItem) {
                    LoanRequest::whereKey($parent->loan_request_id)->lockForUpdate()->firstOrFail();
                } else {
                    InventorySession::whereKey($parent->inventory_session_id)->lockForUpdate()->firstOrFail();
                }
                $locked = $parent->newQuery()->whereKey($parent->id)->lockForUpdate()->firstOrFail();
                abort_unless($service->canUpload($request->user(), $locked), 403);
                abort_unless($locked instanceof LoanItem ? in_array($locked->status, ['approved', 'prepared', 'return_requested', 'inspected']) : $locked->session->status === 'active', 422, 'Bukti tidak dapat ditambahkan pada tahap ini.');
                $context = $locked instanceof LoanItem ? (in_array($locked->status, ['return_requested', 'inspected']) ? 'return' : 'handover') : 'inventory';
                foreach ($request->file('images') as $file) {
                    $photos[] = $service->processAndSaveImage($file, $locked, $context);
                }
                if ($locked instanceof InventoryItem) {
                    $locked->session->increment('version');
                }
                AuditEvent::record($locked, 'evidence.photos_uploaded', ['media_ids' => collect($photos)->pluck('id')->all()], $locked->asset->room?->unit_id);
            });
        } catch (\Throwable $exception) {
            $service->cleanup($photos);
            throw $exception;
        }

        return back()->with('success', 'Foto bukti diterima untuk pemeriksaan dan optimasi WebP.');
    }

    public function replace(Request $request, Media $media, MediaService $service): RedirectResponse
    {
        $parent = $media->mediable;
        abort_unless(($parent instanceof LoanItem || $parent instanceof InventoryItem) && $service->canUpload($request->user(), $parent), 404);
        $data = $request->validate(['reason' => 'required|string|min:10|max:2000', 'images' => 'required|array|size:1', 'images.*' => 'required|image|mimes:jpeg,png,webp|max:10240|dimensions:max_width=12000,max_height=12000']);
        $photos = [];
        try {
            DB::transaction(function () use ($request, $media, $parent, $service, $data, &$photos) {
                if ($parent instanceof LoanItem) {
                    LoanRequest::whereKey($parent->loan_request_id)->lockForUpdate()->firstOrFail();
                } else {
                    InventorySession::whereKey($parent->inventory_session_id)->lockForUpdate()->firstOrFail();
                }
                $lockedParent = $parent->newQuery()->whereKey($parent->id)->lockForUpdate()->firstOrFail();
                $locked = Media::whereKey($media->id)->lockForUpdate()->firstOrFail();
                $locked->setRelation('mediable', $lockedParent);
                abort_unless($service->canUpload($request->user(), $lockedParent), 404);
                abort_unless($service->canReplace($request->user(), $locked), 422, 'Foto telah diganti, masih diproses, sudah siap, atau tahap bukti telah ditutup.');
                $replacement = $service->processAndSaveImage($request->file('images')[0], $lockedParent, $locked->context, $locked);
                $photos[] = $replacement;
                $locked->update(['replacement_media_id' => $replacement->id, 'replaced_by' => $request->user()->id, 'replacement_reason' => $data['reason'], 'replaced_at' => now()]);
                if ($lockedParent instanceof InventoryItem) {
                    $lockedParent->session->increment('version');
                }
                AuditEvent::record($locked, 'evidence.replaced', ['replacement_media_id' => $replacement->id, 'reason' => $data['reason'], 'previous_status' => $locked->processing_status], $lockedParent->asset->room?->unit_id);
            });
        } catch (\Throwable $exception) {
            $service->cleanup($photos);
            throw $exception;
        }

        return back()->with('success', 'Bukti pengganti diterima. Bukti lama tetap diarsipkan; proses hanya dapat dilanjutkan setelah bukti pengganti lolos pemeriksaan.');
    }

    public function show(Request $request, Media $media): StreamedResponse
    {
        if ($media->mediable instanceof Asset) {
            $this->authorize('view', $media->mediable);
        } elseif ($media->mediable instanceof WorkRecord) {
            $record = $media->mediable;
            $scope = app(AccessScope::class);
            abort_unless($record->created_by === $request->user()->id || ($record->kind === 'incidents' && (int) ($record->data['reported_for_user_id'] ?? 0) === $request->user()->id) || ($record->asset && ($scope->coordinate($request->user(), $record->asset) || $scope->inspect($request->user(), $record->asset))), 404);
        } elseif ($media->mediable instanceof LoanItem) {
            abort_unless(app(AccessScope::class)->viewLoan($request->user(), $media->mediable->request), 404);
        } elseif ($media->mediable instanceof InventoryItem) {
            $asset = $media->mediable->asset;
            $scope = app(AccessScope::class);
            abort_unless($scope->coordinate($request->user(), $asset) || $scope->inspect($request->user(), $asset), 404);
        } else {
            abort(404);
        }
        abort_unless($media->processing_status === 'ready' && $media->scan_status === 'clean', 404);
        if ($request->boolean('original')) {
            abort_unless($media->uploaded_by === $request->user()->id || $request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 403);
            AuditEvent::record($media, 'media.original_downloaded');

            return Storage::disk('local')->download($media->source_path, 'bukti-'.$media->id.'.'.match (mime_content_type(Storage::disk('local')->path($media->source_path))) {
                'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg'
            }, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        }
        $field = $request->boolean('thumbnail') ? 'thumbnail_path' : 'file_path';
        $path = $media->getRawOriginal($field);
        if ($media->disk === 'public') {
            $path = preg_replace('#^/storage/#', '', $path);
        }
        abort_unless($path && Storage::disk($media->disk)->exists($path), 404);

        return Storage::disk($media->disk)->response($path, null, [
            'Content-Type' => $media->mime_type, 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function retry(Request $request, Media $media): RedirectResponse
    {
        abort_unless($media->uploaded_by === $request->user()->id, 403);
        $parent = $media->mediable;
        abort_unless($parent && app(MediaService::class)->canUpload($request->user(), $parent), 403);
        DB::transaction(function () use ($media) {
            $locked = Media::whereKey($media->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->processing_status === 'failed' && ! $locked->replacement_media_id, 422);
            $locked->update(['processing_status' => 'queued', 'scan_status' => 'pending', 'error_code' => null]);
            ProcessMedia::dispatch($locked->id)->onQueue('media')->afterCommit();
        });

        return back()->with('success', 'Foto masuk antrean pemeriksaan ulang.');
    }
}
