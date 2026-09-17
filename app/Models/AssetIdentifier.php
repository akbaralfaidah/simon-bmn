<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetIdentifier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['identity_snapshot' => 'array', 'assigned_date' => 'date', 'valid_until' => 'datetime'];
    }
}
