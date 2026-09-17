<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_assignments', function (Blueprint $table) {
            $table->boolean('is_global')->default(false);
            $table->boolean('can_administer')->default(false);
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
        });
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->text('decision_reason')->nullable();
            $table->date('requested_end_date')->nullable();
            $table->text('extension_reason')->nullable();
            $table->uuid('submission_key')->nullable()->unique();
        });
        Schema::table('loan_items', function (Blueprint $table) {
            $table->string('condition_before')->nullable();
            $table->string('condition_after')->nullable();
            $table->json('checklist')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users');
            $table->foreignId('inspected_by')->nullable()->constrained('users');
            foreach (['handed_at', 'accepted_at', 'return_requested_at', 'physically_received_at', 'closed_at'] as $column) {
                $table->timestamp($column)->nullable();
            }
        });
        Schema::table('basts', function (Blueprint $table) {
            $table->json('snapshot')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('disk')->default('public');
            $table->string('source_path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('processing_status')->default('legacy');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('source_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
        });
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->json('snapshot')->nullable();
        });
        Schema::table('custody_assignments', function (Blueprint $table) {
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('accepted_at')->nullable();
            $table->string('evidence_path')->nullable();
        });
        Schema::table('disposals', function (Blueprint $table) {
            $table->date('sk_date')->nullable();
            $table->string('sk_issuer')->nullable();
            $table->string('evidence_path')->nullable();
        });
        Schema::create('work_records', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->index();
            $table->string('title');
            $table->string('status')->default('draft')->index();
            $table->foreignId('unit_id')->nullable()->constrained('organization_units');
            $table->foreignUuid('asset_id')->nullable()->constrained('assets');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->json('data')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('unit_id')->nullable()->constrained('organization_units');
            $table->string('subject_type');
            $table->string('subject_id');
            $table->string('action');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedBigInteger('value')->default(0);
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'document_sequences', 'audit_events', 'work_records'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('disposals', fn (Blueprint $table) => $table->dropColumn(['sk_date', 'sk_issuer', 'evidence_path']));
        Schema::table('inventory_items', fn (Blueprint $table) => $table->dropColumn('snapshot'));
        Schema::table('custody_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn(['accepted_at', 'evidence_path']);
        });
        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['disk', 'source_path', 'checksum', 'processing_status', 'original_name', 'source_size', 'width', 'height']);
        });
        Schema::table('basts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['snapshot', 'checksum', 'verified_at']);
        });
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prepared_by');
            $table->dropConstrainedForeignId('inspected_by');
            $table->dropColumn(['condition_before', 'condition_after', 'checklist', 'handed_at', 'accepted_at', 'return_requested_at', 'physically_received_at', 'closed_at']);
        });
        Schema::table('loan_requests', fn (Blueprint $table) => $table->dropColumn(['decision_reason', 'requested_end_date', 'extension_reason', 'submission_key']));
        Schema::table('assets', fn (Blueprint $table) => $table->dropColumn('version'));
        Schema::table('role_assignments', fn (Blueprint $table) => $table->dropColumn(['is_global', 'can_administer']));
    }
};
