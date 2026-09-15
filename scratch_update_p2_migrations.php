<?php
$dir = 'c:\laragon\www\bmn-gakkum-jambi\database\migrations';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, 'create_asset_categories_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->string('code')->unique();\n            \$table->string('name');\n            \$table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_assets_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->uuid('id')->primary();\n            \$table->string('satker_code')->nullable();\n            \$table->string('item_code')->nullable();\n            \$table->string('nup')->nullable();\n            \$table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();\n            \$table->string('name');\n            \$table->string('brand_type')->nullable();\n            \$table->string('serial_number')->nullable();\n            \$table->text('specification')->nullable();\n            \$table->date('acquisition_date')->nullable();\n            \$table->string('acquisition_source')->nullable();\n            \$table->decimal('value', 15, 2)->nullable();\n            \$table->string('condition')->default('Baik'); // Baik, Rusak Ringan, Rusak Berat\n            \$table->boolean('is_loanable')->default(false);\n            \$table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();\n            \$table->string('status')->default('active'); // active, written_off, maintenance", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_asset_identifiers_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->string('identifier_type');\n            \$table->string('identifier_value');\n            \$table->date('assigned_date')->nullable();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_asset_location_histories_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->foreignId('from_room_id')->nullable()->constrained('rooms')->nullOnDelete();\n            \$table->foreignId('to_room_id')->nullable()->constrained('rooms')->nullOnDelete();\n            \$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();\n            \$table->text('notes')->nullable();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_asset_components_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->string('name');\n            \$table->string('specification')->nullable();\n            \$table->boolean('is_present')->default(true);", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_asset_occupancies_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();\n            \$table->string('occupancy_type'); // custody, loan, transfer, maintenance\n            \$table->timestamp('starts_at');\n            \$table->timestamp('ends_at')->nullable();\n            \$table->boolean('is_active')->default(true);", $content);
        file_put_contents("$dir/$file", $content);
    }
}
echo "Done";
