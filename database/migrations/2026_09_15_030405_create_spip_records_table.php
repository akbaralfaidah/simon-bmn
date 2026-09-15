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
        Schema::create('spip_records', function (Blueprint $table) {
            $table->id();
            $table->string('period');
            $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->text('risk_description');
            $table->text('control_action')->nullable();
            $table->string('status')->default('open'); // open, resolved
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spip_records');
    }
};
