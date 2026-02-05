<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Investment;
use App\Models\MiningSession;
use App\Models\MiningCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TradingPairsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cryptoPairs = ['BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'SOL/USDT', 'ADA/USDT', 'XRP/USDT', 'DOGE/USDT', 'DOT/USDT'];
        $orderCycles = ['60s', '5m', '15m', '30m', '1h'];
        $traderNames = ['Leon Jones', 'Sarah Chen', 'Michael Torres', 'Emma Wilson', 'David Kim', 'Lisa Anderson', 'James Brown', 'Maria Garcia'];
        $orderDirections = ['Call', 'Put'];

        // Get today's date
        $today = Carbon::today();
        
        // Get all active investments
        $investments = Investment::with(['user', 'investmentPlan'])
            ->where('status', 'active')
            ->get();

        if ($investments->isEmpty()) {
            $this->command->info('No active investments found. Skipping trading pairs generation.');
            return;
        }

        // Get today's mining codes
        $todayCodes = MiningCode::where('date', $today)
            ->where('is_active', true)
            ->orderBy('code_type')
            ->get();

        if ($todayCodes->isEmpty()) {
            $this->command->info('No active mining codes found for today. Please create codes first.');
            return;
        }

        $sessionsCreated = 0;

        DB::transaction(function () use ($investments, $todayCodes, $cryptoPairs, $orderCycles, $traderNames, $orderDirections, $today, &$sessionsCreated) {
            foreach ($investments as $investment) {
                $user = $investment->user;
                
                // Create sessions for each code
                foreach ($todayCodes as $code) {
                    // Check if session already exists
                    $existingSession = MiningSession::where('user_id', $user->id)
                        ->where('code_date', $today)
                        ->where('used_code', $code->code)
                        ->first();

                    if ($existingSession) {
                        continue; // Skip if already exists
                    }

                    // Randomly select trading data
                    $cryptoPair = $cryptoPairs[array_rand($cryptoPairs)];
                    $orderCycle = $orderCycles[array_rand($orderCycles)];
                    $traderName = $traderNames[array_rand($traderNames)];
                    $orderDirection = $orderDirections[array_rand($orderDirections)];
                    
                    // Generate realistic profit and winning rates
                    $profitRate = round(rand(5000, 9000) / 100, 2); // 50.00% to 90.00%
                    $winningRate = round(rand(8500, 9950) / 100, 2); // 85.00% to 99.50%
                    $followersCount = rand(500, 5000);
                    
                    // Calculate order amount based on investment
                    $orderAmount = round($investment->amount * (rand(80, 120) / 100), 4);
                    
                    // Create mining session with trading data
                    MiningSession::create([
                        'user_id' => $user->id,
                        'investment_id' => $investment->id,
                        'started_at' => now(),
                        'status' => 'completed',
                        'progress' => 100.00,
                        'rewards_claimed' => false,
                        'used_code' => $code->code,
                        'code_date' => $today,
                        'trader_name' => $traderName,
                        'crypto_pair' => $cryptoPair,
                        'order_cycle' => $orderCycle,
                        'profit_rate' => $profitRate,
                        'winning_rate' => $winningRate,
                        'followers_count' => $followersCount,
                        'order_direction' => $orderDirection,
                        'order_amount' => $orderAmount,
                        'order_time' => now(),
                    ]);

                    $sessionsCreated++;
                }
            }
        });

        $this->command->info("Created {$sessionsCreated} trading sessions with crypto pairs for today.");
    }
}
