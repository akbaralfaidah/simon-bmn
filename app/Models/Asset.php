<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(AssetIdentifier::class, 'asset_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(AssetComponent::class, 'asset_id');
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(AssetOccupancy::class, 'asset_id');
    }
}
