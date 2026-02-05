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
            $table->string('trader_name')->nullable()->after('code_date');
            $table->string('crypto_pair', 20)->nullable()->after('trader_name'); // e.g., BTC/USDT
            $table->string('order_cycle', 10)->nullable()->after('crypto_pair'); // e.g., 60s, 5m
            $table->decimal('profit_rate', 5, 2)->nullable()->after('order_cycle'); // e.g., 76.60
            $table->decimal('winning_rate', 5, 2)->nullable()->after('profit_rate'); // e.g., 98.50
            $table->integer('followers_count')->default(0)->after('winning_rate');
            $table->string('order_direction', 20)->nullable()->after('followers_count'); // e.g., "Call", "Put"
            $table->decimal('order_amount', 15, 4)->nullable()->after('order_direction');
            $table->timestamp('order_time')->nullable()->after('order_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mining_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'trader_name',
                'crypto_pair',
                'order_cycle',
                'profit_rate',
                'winning_rate',
                'followers_count',
                'order_direction',
                'order_amount',
                'order_time',
            ]);
        });
    }
};
