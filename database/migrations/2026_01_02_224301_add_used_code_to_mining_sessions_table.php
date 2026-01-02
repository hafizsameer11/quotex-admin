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
        Schema::table('mining_sessions', function (Blueprint $table) {
            $table->string('used_code', 50)->nullable()->after('rewards_claimed');
            $table->date('code_date')->nullable()->after('used_code'); // Track which date the code was for
            $table->index(['user_id', 'code_date', 'used_code']); // Index for checking duplicate claims
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mining_sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'code_date', 'used_code']);
            $table->dropColumn(['used_code', 'code_date']);
        });
    }
};
