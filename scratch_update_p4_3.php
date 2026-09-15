<?php
$dir = 'c:\laragon\www\bmn-gakkum-jambi\database\migrations';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, 'create_maintenance_logs_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->text('description');\n            \$table->decimal('cost', 15, 2)->nullable();\n            \$table->string('status')->default('pending'); // pending, in_progress, completed\n            \$table->date('start_date');\n            \$table->date('end_date')->nullable();\n            \$table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_disposals_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->text('reason');\n            \$table->string('sk_number')->nullable();\n            \$table->string('status')->default('proposed'); // proposed, approved, rejected\n            \$table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();\n            \$table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_spip_records_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->string('period');\n            \$table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();\n            \$table->text('risk_description');\n            \$table->text('control_action')->nullable();\n            \$table->string('status')->default('open'); // open, resolved\n            \$table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
}

$models = [
    'MaintenanceLog' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\n\nclass MaintenanceLog extends Model\n{\n    protected \$guarded = [];\n\n    protected \$casts = [\n        'start_date' => 'date',\n        'end_date' => 'date',\n    ];\n\n    public function asset(): BelongsTo\n    {\n        return \$this->belongsTo(Asset::class);\n    }\n\n    public function reporter(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'reported_by');\n    }\n}\n",
    'Disposal' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\n\nclass Disposal extends Model\n{\n    protected \$guarded = [];\n\n    public function asset(): BelongsTo\n    {\n        return \$this->belongsTo(Asset::class);\n    }\n\n    public function proposer(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'proposed_by');\n    }\n\n    public function approver(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'approved_by');\n    }\n}\n",
    'SpipRecord' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\n\nclass SpipRecord extends Model\n{\n    protected \$guarded = [];\n\n    public function asset(): BelongsTo\n    {\n        return \$this->belongsTo(Asset::class);\n    }\n\n    public function assessor(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'assessor_id');\n    }\n}\n"
];

foreach ($models as $name => $content) {
    file_put_contents("c:\\laragon\\www\\bmn-gakkum-jambi\\app\\Models\\$name.php", $content);
}
echo "Done";
