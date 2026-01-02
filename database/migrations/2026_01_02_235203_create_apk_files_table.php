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
        Schema::create('apk_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path'); // Storage path
            $table->string('original_name'); // Original uploaded filename
            $table->string('version')->nullable(); // APK version
            $table->bigInteger('file_size'); // File size in bytes
            $table->boolean('is_active')->default(true); // Only one active APK at a time
            $table->text('description')->nullable(); // Optional description
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who uploaded
            $table->timestamps();
            
            // Index for faster lookups
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apk_files');
    }
};
