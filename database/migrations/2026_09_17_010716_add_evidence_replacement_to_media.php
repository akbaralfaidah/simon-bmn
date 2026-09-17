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
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('replacement_media_id')->nullable()->constrained('media')->restrictOnDelete();
            $table->foreignId('replaced_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('replacement_reason')->nullable();
            $table->timestamp('replaced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_media_id');
            $table->dropConstrainedForeignId('replaced_by');
            $table->dropColumn(['replacement_reason', 'replaced_at']);
        });
    }
};
