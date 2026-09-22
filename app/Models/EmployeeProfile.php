<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
        'unit_id',
        'nip',
        'phone',
        'signature_path',
    ];

    protected $appends = [
        'has_signature',
    ];

    public function getHasSignatureAttribute(): bool
    {
        return ! empty($this->signature_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class, 'unit_id');
    }
}
