<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function record(Model $subject, string $action, array $data = [], ?int $unitId = null): void
    {
        self::create([
            'user_id' => auth()->id(), 'unit_id' => $unitId,
            'subject_type' => $subject->getMorphClass(), 'subject_id' => (string) $subject->getKey(),
            'action' => $action, 'data' => $data,
        ]);
    }
}
