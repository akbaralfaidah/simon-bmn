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
        Schema::create('basts', function (Blueprint $table) {
            $table->id();
            $table->string('bast_number')->unique();
            $table->string('bast_type'); // loan, return, transfer
            $table->nullableMorphs('reference');
            $table->string('generated_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('status')->default('draft'); // draft, signed
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basts');
    }
};
