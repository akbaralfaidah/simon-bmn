<?php
$dir = 'c:\laragon\www\bmn-gakkum-jambi\database\migrations';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, 'create_custody_assignments_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();\n            \$table->string('status')->default('active'); // active, revoked\n            \$table->date('start_date');\n            \$table->date('end_date')->nullable();\n            \$table->text('notes')->nullable();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_custody_events_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignId('custody_assignment_id')->constrained('custody_assignments')->cascadeOnDelete();\n            \$table->string('type'); // assigned, revoked, transferred\n            \$table->text('notes')->nullable();\n            \$table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
}
echo "Done";
