<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LoanItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['checklist' => 'array', 'handed_at' => 'datetime', 'accepted_at' => 'datetime', 'return_requested_at' => 'datetime', 'physically_received_at' => 'datetime', 'inspected_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(LoanRequest::class, 'loan_request_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
