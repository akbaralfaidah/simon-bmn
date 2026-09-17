<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustodyAssignment extends Model
{
    protected $guarded = [];

    protected $hidden = ['evidence_path', 'return_evidence_path', 'return_evidence_checksum'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'physically_received_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CustodyEvent::class);
    }
}
