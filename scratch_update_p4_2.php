<?php
$dir = 'c:\laragon\www\bmn-gakkum-jambi\database\migrations';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, 'create_inventory_sessions_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->string('name');\n            \$table->string('status')->default('draft'); // draft, active, review, closed\n            \$table->date('start_date');\n            \$table->date('end_date')->nullable();\n            \$table->foreignId('created_by')->constrained('users')->cascadeOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_inventory_items_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignId('inventory_session_id')->constrained('inventory_sessions')->cascadeOnDelete();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->string('status')->default('pending'); // pending, found, missing, damaged\n            \$table->text('notes')->nullable();\n            \$table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_inventory_findings_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();\n            \$table->text('description');\n            \$table->text('action_taken')->nullable();\n            \$table->string('status')->default('open'); // open, resolved", $content);
        file_put_contents("$dir/$file", $content);
    }
}

$models = [
    'InventorySession' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\nuse Illuminate\Database\Eloquent\Relations\HasMany;\n\nclass InventorySession extends Model\n{\n    protected \$guarded = [];\n\n    protected \$casts = [\n        'start_date' => 'date',\n        'end_date' => 'date',\n    ];\n\n    public function creator(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'created_by');\n    }\n\n    public function items(): HasMany\n    {\n        return \$this->hasMany(InventoryItem::class);\n    }\n}\n",
    'InventoryItem' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\nuse Illuminate\Database\Eloquent\Relations\HasOne;\n\nclass InventoryItem extends Model\n{\n    protected \$guarded = [];\n\n    public function session(): BelongsTo\n    {\n        return \$this->belongsTo(InventorySession::class, 'inventory_session_id');\n    }\n\n    public function asset(): BelongsTo\n    {\n        return \$this->belongsTo(Asset::class);\n    }\n\n    public function checker(): BelongsTo\n    {\n        return \$this->belongsTo(User::class, 'checked_by');\n    }\n\n    public function finding(): HasOne\n    {\n        return \$this->hasOne(InventoryFinding::class);\n    }\n}\n",
    'InventoryFinding' => "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Relations\BelongsTo;\n\nclass InventoryFinding extends Model\n{\n    protected \$guarded = [];\n\n    public function item(): BelongsTo\n    {\n        return \$this->belongsTo(InventoryItem::class, 'inventory_item_id');\n    }\n}\n"
];

foreach ($models as $name => $content) {
    file_put_contents("c:\\laragon\\www\\bmn-gakkum-jambi\\app\\Models\\$name.php", $content);
}
echo "Done";
