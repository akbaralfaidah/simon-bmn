<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_identity_guards', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
        });
        DB::table('asset_identity_guards')->insert(['id' => 1]);
        Schema::table('assets', function (Blueprint $table) {
            $table->timestamp('identity_verified_at')->nullable();
        });
        Schema::table('asset_identifiers', function (Blueprint $table) {
            $table->json('identity_snapshot')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->foreignId('work_record_id')->nullable()->constrained('work_records')->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_identifiers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_record_id');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn(['identity_snapshot', 'valid_until']);
        });
        Schema::table('assets', fn (Blueprint $table) => $table->dropColumn('identity_verified_at'));
        Schema::dropIfExists('asset_identity_guards');
    }
};
