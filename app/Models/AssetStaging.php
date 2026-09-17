<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetStaging extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['raw_data' => 'array'];
    }
}
