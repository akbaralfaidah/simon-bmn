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
        Schema::create('asset_stagings', function (Blueprint $table) {
            $table->id();
            $table->string('import_batch_id');
            $table->json('raw_data');
            $table->string('status')->default('pending'); // pending, validated, committed, error
            $table->text('validation_errors')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_stagings');
    }
};
