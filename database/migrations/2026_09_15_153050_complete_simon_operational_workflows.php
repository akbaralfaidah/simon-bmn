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
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->string('submission_hash', 64)->nullable();
        });
        Schema::table('basts', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('supersedes_id')->nullable()->constrained('basts');
            $table->text('review_notes')->nullable();
            $table->string('scan_status')->default('pending');
        });
        Schema::table('spip_records', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->date('due_date')->nullable();
            $table->text('effectiveness')->nullable();
            $table->text('followup')->nullable();
            $table->string('evidence_path')->nullable();
            $table->unsignedInteger('version')->default(1);
        });
        Schema::table('role_assignments', function (Blueprint $table) {
            $table->foreignId('granted_by')->nullable()->constrained('users');
            $table->text('reason')->nullable();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('scan_status')->default('pending');
            $table->string('error_code')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->string('profile_version')->default('1');
            $table->string('variant_checksum', 64)->nullable();
        });
        foreach (['organization_units', 'rooms', 'asset_categories'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        }
        Schema::create('notification_receipts', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_receipts');
        foreach (['organization_units', 'rooms', 'asset_categories'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('is_active'));
        }
        Schema::table('media', fn (Blueprint $table) => $table->dropColumn(['scan_status', 'error_code', 'attempts', 'ready_at', 'processing_started_at', 'profile_version', 'variant_checksum']));
        Schema::table('role_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('granted_by');
            $table->dropColumn('reason');
        });
        Schema::table('spip_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['due_date', 'effectiveness', 'followup', 'evidence_path', 'version']);
        });
        Schema::table('basts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supersedes_id');
            $table->dropColumn(['version', 'review_notes', 'scan_status']);
        });
        Schema::table('loan_requests', fn (Blueprint $table) => $table->dropColumn(['version', 'submission_hash']));
    }
};
