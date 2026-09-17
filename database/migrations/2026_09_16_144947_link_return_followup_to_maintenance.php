<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->foreignId('loan_item_id')->nullable()->constrained('loan_items');
            $table->foreignId('incident_id')->nullable()->constrained('work_records');
            $table->foreignId('inspected_by')->nullable()->constrained('users');
            $table->text('result_notes')->nullable();
        });
        Schema::table('loan_items', function (Blueprint $table) {
            $table->timestamp('inspected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loan_item_id');
            $table->dropConstrainedForeignId('incident_id');
            $table->dropConstrainedForeignId('inspected_by');
            $table->dropColumn('result_notes');
        });
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropColumn('inspected_at');
        });
    }
};
