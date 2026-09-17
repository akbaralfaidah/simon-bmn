<?php

namespace App\Models;

use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $guarded = [];

    protected $hidden = ['source_path', 'checksum', 'disk', 'original_name', 'uploaded_by'];

    protected $appends = ['can_retry', 'can_replace'];

    public function getCanReplaceAttribute(): bool
    {
        $user = auth()->user();

        return $user && app(MediaService::class)->canReplace($user, $this);
    }

    public function getCanRetryAttribute(): bool
    {
        $user = auth()->user();
        if (! $user || $this->replacement_media_id || $this->processing_status !== 'failed' || $this->uploaded_by !== $user->id) {
            return false;
        }
        $parent = $this->mediable;

        return $parent && app(MediaService::class)->canUpload($user, $parent);
    }

    public function getFilePathAttribute(string $value): string
    {
        return route('media.show', $this->id);
    }

    public function getThumbnailPathAttribute(?string $value): string
    {
        return route('media.show', ['media' => $this->id, 'thumbnail' => 1]);
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
