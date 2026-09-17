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
        Schema::table('custody_assignments', function (Blueprint $table) {
            $table->string('condition_before')->nullable();
            $table->string('condition_after')->nullable();
            $table->string('return_completeness')->nullable();
            $table->text('return_notes')->nullable();
            $table->timestamp('physically_received_at')->nullable();
            $table->string('return_evidence_path')->nullable();
            $table->string('return_evidence_checksum', 64)->nullable();
        });
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->foreignId('custody_assignment_id')->nullable()->constrained('custody_assignments')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_logs', fn (Blueprint $table) => $table->dropConstrainedForeignId('custody_assignment_id'));
        Schema::table('custody_assignments', fn (Blueprint $table) => $table->dropColumn(['condition_before', 'condition_after', 'return_completeness', 'return_notes', 'physically_received_at', 'return_evidence_path', 'return_evidence_checksum']));
    }
};
