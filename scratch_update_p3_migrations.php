<?php
$dir = 'c:\laragon\www\bmn-gakkum-jambi\database\migrations';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, 'create_loan_requests_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();\n            \$table->text('purpose');\n            \$table->date('start_date');\n            \$table->date('end_date');\n            \$table->string('status')->default('draft'); // draft, pending, approved, active, completed, rejected\n            \$table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_loan_items_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignId('loan_request_id')->constrained('loan_requests')->cascadeOnDelete();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->string('status')->default('pending'); // pending, approved, rejected, returned\n            \$table->text('notes')->nullable();", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_reservations_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();\n            \$table->foreignId('loan_item_id')->nullable()->constrained('loan_items')->cascadeOnDelete();\n            \$table->dateTime('start_date');\n            \$table->dateTime('end_date');\n            \$table->string('status')->default('active'); // active, cancelled, fulfilled", $content);
        file_put_contents("$dir/$file", $content);
    }
    if (strpos($file, 'create_basts_table') !== false) {
        $content = file_get_contents("$dir/$file");
        $content = preg_replace('/\$table->id\(\);/', "\$table->id();\n            \$table->string('bast_number')->unique();\n            \$table->string('bast_type'); // loan, return, transfer\n            \$table->nullableMorphs('reference');\n            \$table->string('generated_pdf_path')->nullable();\n            \$table->string('signed_pdf_path')->nullable();\n            \$table->string('status')->default('draft'); // draft, signed\n            \$table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();\n            \$table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();", $content);
        file_put_contents("$dir/$file", $content);
    }
}
echo "Done";
