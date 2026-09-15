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
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('satker_code')->nullable();
            $table->string('item_code')->nullable();
            $table->string('nup')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('name');
            $table->string('brand_type')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('specification')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->string('acquisition_source')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('condition')->default('Baik'); // Baik, Rusak Ringan, Rusak Berat
            $table->boolean('is_loanable')->default(false);
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('status')->default('active'); // active, written_off, maintenance
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
