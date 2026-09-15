<?php
$models = [
    'CustodyAssignment' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\nuse Illuminate\Database\Eloquent\Relations\HasMany;\n\nclass CustodyAssignment extends Model\n{\n    protected \$guarded = [];\n\n    protected \$casts = [\n        'start_date' => 'date',\n        'end_date' => 'date',\n    ];\n\n    public function asset(): BelongsTo\n    {\n        return \$this->belongsTo(Asset::class);\n    }\n\n    public function user(): BelongsTo\n    {\n        return \$this->belongsTo(User::class);\n    }\n\n    public function events(): HasMany\n    {\n        return \$this->hasMany(CustodyEvent::class);\n    }\n}\n",
    'CustodyEvent' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\n\nclass CustodyEvent extends Model\n{\n    protected \$guarded = [];\n\n    public function assignment(): BelongsTo\n    {\n        return \$this->belongsTo(CustodyAssignment::class, 'custody_assignment_id');\n    }\n\n    public function performer(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'performed_by');\n    }\n}\n"
];

foreach ($models as $name => $content) {
    file_put_contents("c:\\laragon\\www\\bmn-gakkum-jambi\\app\\Models\\$name.php", $content);
}
echo "Done";
