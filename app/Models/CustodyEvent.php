<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustodyEvent extends Model
{
    protected $guarded = [];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CustodyAssignment::class, 'custody_assignment_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
