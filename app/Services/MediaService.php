<?php

namespace App\Services;

use App\Jobs\ProcessMedia;
use App\Models\Asset;
use App\Models\InventoryItem;
use App\Models\LoanItem;
use App\Models\Media;
use App\Models\User;
use App\Models\WorkRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class MediaService
{
    public function canUpload(User $actor, Model $parent): bool
    {
        if ($actor->status !== 'active') {
            return false;
        }
        $scope = app(AccessScope::class);

        return match (true) {
            $parent instanceof Asset => $actor->can('update', $parent),
            $parent instanceof WorkRecord => $parent->created_by === $actor->id && $scope->assets($actor)->whereKey($parent->asset_id)->exists(),
            $parent instanceof LoanItem => $parent->request->user_id !== $actor->id && $scope->inspect($actor, $parent->asset),
            $parent instanceof InventoryItem => $scope->inspect($actor, $parent->asset),
            default => false,
        };
    }

    public function canReplace(User $actor, Media $media): bool
    {
        $parent = $media->mediable;
        if ($media->replacement_media_id || ! in_array($media->processing_status, ['failed', 'cancelled']) || ! $parent || ! $this->canUpload($actor, $parent)) {
            return false;
        }

        return match (true) {
            $parent instanceof LoanItem => in_array($parent->status, $media->context === 'return' ? ['return_requested', 'inspected'] : ['approved', 'prepared']),
            $parent instanceof InventoryItem => $parent->session->status === 'active',
            default => false,
        };
    }

    public function processAndSaveImage(UploadedFile $file, Model $mediable, string $context = 'photo', ?Media $replacing = null): Media
    {
        $this->validateImage($file->getRealPath(), $file->getSize());
        if ($replacing) {
            abort_unless($replacing->mediable_type === $mediable->getMorphClass() && (string) $replacing->mediable_id === (string) $mediable->getKey() && $this->canReplace(auth()->user(), $replacing), 422);
        }
        if ($mediable->media()->whereNull('replacement_media_id')->when($replacing, fn ($query) => $query->where('id', '!=', $replacing->id))->count() >= 20) {
            throw ValidationException::withMessages(['images' => 'Satu catatan maksimal 20 foto.']);
        }
        if ($mediable->media()->count() >= 100) {
            throw ValidationException::withMessages(['images' => 'Batas riwayat 100 foto tercapai. Hubungi pengelola untuk pemeriksaan.']);
        }
        $source = $file->store('quarantine/photos', 'local');
        if (! $source) {
            throw ValidationException::withMessages(['images' => 'Penyimpanan foto tidak tersedia.']);
        }
        try {
            $media = $mediable->media()->create([
                'file_path' => '', 'thumbnail_path' => '', 'source_path' => $source,
                'mime_type' => 'image/webp', 'size' => 0, 'source_size' => $file->getSize(),
                'context' => $context, 'disk' => 'local', 'processing_status' => 'queued', 'scan_status' => 'pending',
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'uploaded_by' => auth()->id(),
            ]);
            if (config('simon.media_async')) {
                DB::afterCommit(function () use ($media) {
                    try {
                        ProcessMedia::dispatch($media->id)->onQueue('media');
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                });
            } else {
                $this->processQueued($media->id);
            }

            return $media->fresh();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($source);
            throw $exception;
        }
    }

    public function processQueued(int $id): void
    {
        $paths = [];
        try {
            DB::transaction(function () use ($id, &$paths) {
                $media = Media::whereKey($id)->lockForUpdate()->first();
                if (! $media || $media->replacement_media_id || in_array($media->processing_status, ['ready', 'cancelled'])) {
                    return;
                }
                $actor = User::find($media->uploaded_by);
                $parent = $media->mediable;
                $allowed = $actor && $parent && $this->canUpload($actor, $parent);
                if (! $allowed) {
                    $media->update(['processing_status' => 'cancelled', 'error_code' => 'access_revoked']);

                    return;
                }
                $disk = Storage::disk('local');
                $sourcePath = $disk->path($media->source_path);
                if (! $disk->exists($media->source_path) || ! hash_equals($media->checksum, hash_file('sha256', $sourcePath))) {
                    throw new \RuntimeException('Source checksum mismatch');
                }
                $media->update(['processing_status' => 'processing', 'processing_started_at' => now(), 'attempts' => $media->attempts + 1]);
                $this->validateImage($sourcePath, $media->source_size);
                app(UploadScanner::class)->assertClean($sourcePath);
                $image = (new ImageManager(new Driver, strip: true, autoOrientation: true))->decodePath($sourcePath);
                if ($image->isAnimated()) {
                    throw ValidationException::withMessages(['images' => 'Foto animasi tidak didukung. Gunakan foto statis.']);
                }
                $limit = $media->context === 'photo' ? 1920 : 3000;
                $main = (clone $image)->scaleDown(width: $limit, height: $limit);
                $thumb = (clone $image)->scaleDown(width: 480, height: 480);
                $encoded = (string) $main->encodeUsingFormat(Format::WEBP, quality: $media->context === 'photo' ? 82 : 90);
                $thumbnail = (string) $thumb->encodeUsingFormat(Format::WEBP, quality: 80);
                $base = 'media/'.$media->id.'-'.substr($media->checksum, 0, 16).'-v'.$media->profile_version;
                $paths = [$base.'.webp', $base.'-thumb.webp'];
                if (! $disk->put($paths[0], $encoded) || ! $disk->put($paths[1], $thumbnail)) {
                    throw new \RuntimeException('Media storage unavailable');
                }
                $output = getimagesizefromstring($encoded);
                if (! $output || $output['mime'] !== 'image/webp') {
                    throw new \RuntimeException('Invalid output');
                }
                $media->update(['file_path' => $paths[0], 'thumbnail_path' => $paths[1], 'size' => strlen($encoded),
                    'width' => $main->width(), 'height' => $main->height(), 'processing_status' => 'ready', 'scan_status' => 'clean',
                    'ready_at' => now(), 'error_code' => null, 'variant_checksum' => hash('sha256', $encoded)]);
            }, 3);
        } catch (\Throwable $exception) {
            if ($paths) {
                Storage::disk('local')->delete($paths);
            }
            Media::whereKey($id)->whereNotIn('processing_status', ['ready', 'cancelled'])->update(['processing_status' => 'failed', 'scan_status' => 'failed', 'error_code' => 'processing_failed', 'attempts' => DB::raw('attempts + 1')]);
            throw $exception;
        }
    }

    private function validateImage(string $path, int $size): void
    {
        $dimensions = @getimagesize($path);
        if (! $dimensions || ! in_array($dimensions['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
            || $size > 10 * 1024 * 1024 || $dimensions[0] * $dimensions[1] > 24000000 || max($dimensions[0], $dimensions[1]) > 12000) {
            throw ValidationException::withMessages(['images' => 'Foto harus JPG, PNG, atau WebP statis, maksimal 10 MB dan 24 megapiksel.']);
        }
    }

    public function cleanup(array $media): void
    {
        foreach ($media as $item) {
            Storage::disk('local')->delete(array_filter([$item->getRawOriginal('file_path'), $item->getRawOriginal('thumbnail_path'), $item->source_path]));
        }
    }
}
