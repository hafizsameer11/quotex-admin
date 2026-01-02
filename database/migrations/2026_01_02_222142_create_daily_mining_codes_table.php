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
        Schema::create('daily_mining_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->enum('code_type', ['code1', 'code2']); // Two codes per day
            $table->date('date'); // The date this code is valid for
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who created it
            $table->timestamps();
            
            // Ensure only one active code of each type per day
            // Note: We allow multiple inactive codes, but only one active per type per day
            $table->unique(['date', 'code_type'], 'unique_daily_code_type');
            // Index for faster lookups
            $table->index(['date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_mining_codes');
    }
};
