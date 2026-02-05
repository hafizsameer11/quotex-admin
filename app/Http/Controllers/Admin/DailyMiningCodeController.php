<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MiningCode;
use App\Models\MiningSession;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyMiningCodeController extends Controller
{
    /**
     * Display the daily mining codes management page
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Get today's codes
        $todayCodes = MiningCode::where('date', $today)
            ->orderBy('code_type')
            ->get();
        
        // Get recent codes (last 7 days)
        $recentCodes = MiningCode::where('date', '>=', $today->copy()->subDays(7))
            ->orderBy('date', 'desc')
            ->orderBy('code_type')
            ->with('creator')
            ->get();
        
        // Check if codes exist for today
        $code1 = $todayCodes->where('code_type', 'code1')->first();
        $code2 = $todayCodes->where('code_type', 'code2')->first();
        
        return view('admin.pages.daily-mining-codes', compact('code1', 'code2', 'recentCodes', 'today'));
    }

    /**
     * Store or update today's mining codes
     */
    public function store(Request $request)
    {
        $request->validate([
            'code1' => 'required|string|max:50',
            'code2' => 'required|string|max:50',
        ]);

        $today = Carbon::today();
        $adminId = Auth::id();

        DB::beginTransaction();
        try {
            // Delete any existing codes for today (to avoid unique constraint violation)
            MiningCode::where('date', $today)->delete();

            // Create new codes for today
            $code1Record = MiningCode::create([
                'code' => trim($request->code1),
                'code_type' => 'code1',
                'date' => $today,
                'is_active' => true,
                'created_by' => $adminId,
            ]);

            $code2Record = MiningCode::create([
                'code' => trim($request->code2),
                'code_type' => 'code2',
                'date' => $today,
                'is_active' => true,
                'created_by' => $adminId,
            ]);

            // Delete any existing unclaimed sessions for today (in case admin updates codes)
            MiningSession::where('code_date', $today)
                ->where('rewards_claimed', false)
                ->delete();

            // Create mining sessions for ALL users with active investments
            $activeInvestments = Investment::with('investmentPlan')
                ->where('status', 'active')
                ->get();

            $sessionsCreated = 0;
            foreach ($activeInvestments as $investment) {
                if (!$investment->investmentPlan) continue;

                // Check if session already exists for this code (shouldn't happen after delete, but safety check)
                $existingCode1 = MiningSession::where('user_id', $investment->user_id)
                    ->where('code_date', $today)
                    ->where('used_code', $request->code1)
                    ->first();

                $existingCode2 = MiningSession::where('user_id', $investment->user_id)
                    ->where('code_date', $today)
                    ->where('used_code', $request->code2)
                    ->first();

                // Create session for code1 if doesn't exist
                if (!$existingCode1) {
                    $tradingData1 = $this->generateTradingData($investment);
                    MiningSession::create(array_merge([
                        'user_id' => $investment->user_id,
                        'investment_id' => $investment->id,
                        'started_at' => now(),
                        'status' => 'completed', // Pre-completed, waiting for code claim
                        'progress' => 100.00,
                        'rewards_claimed' => false,
                        'used_code' => trim($request->code1),
                        'code_date' => $today,
                    ], $tradingData1));
                    $sessionsCreated++;
                }

                // Create session for code2 if doesn't exist
                if (!$existingCode2) {
                    $tradingData2 = $this->generateTradingData($investment);
                    MiningSession::create(array_merge([
                        'user_id' => $investment->user_id,
                        'investment_id' => $investment->id,
                        'started_at' => now(),
                        'status' => 'completed', // Pre-completed, waiting for code claim
                        'progress' => 100.00,
                        'rewards_claimed' => false,
                        'used_code' => trim($request->code2),
                        'code_date' => $today,
                    ], $tradingData2));
                    $sessionsCreated++;
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "Daily mining codes set successfully. Created {$sessionsCreated} mining sessions for users with active investments.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to set daily mining codes: ' . $e->getMessage());
        }
    }

    /**
     * Get today's active codes (API endpoint)
     */
    public function getTodayCodes()
    {
        $today = Carbon::today();
        $codes = MiningCode::forToday()
            ->select('code_type', 'code')
            ->get()
            ->pluck('code', 'code_type');

        return response()->json([
            'success' => true,
            'data' => [
                'code1' => $codes->get('code1'),
                'code2' => $codes->get('code2'),
            ]
        ]);
    }

    /**
     * Show claim history for mining codes
     */
    public function claimHistory()
    {
        $claimedSessions = MiningSession::with(['user', 'investment.investmentPlan'])
            ->where('rewards_claimed', true)
            ->whereNotNull('used_code')
            ->orderBy('stopped_at', 'desc')
            ->paginate(50);

        $stats = [
            'total_claimed' => MiningSession::where('rewards_claimed', true)
                ->whereNotNull('used_code')
                ->count(),
            'today_claimed' => MiningSession::where('rewards_claimed', true)
                ->whereNotNull('used_code')
                ->whereDate('code_date', Carbon::today())
                ->count(),
            'total_rewards' => \App\Models\ClaimedAmount::where('reason', 'like', '%mining_daily_profit_code%')
                ->sum('amount'),
        ];

        return view('admin.pages.mining-claim-history', compact('claimedSessions', 'stats'));
    }

    /**
     * Generate random trading data for a session
     */
    private function generateTradingData($investment)
    {
        $cryptoPairs = ['BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'SOL/USDT', 'ADA/USDT', 'XRP/USDT', 'DOGE/USDT', 'DOT/USDT'];
        $orderCycles = ['60s', '5m', '15m', '30m', '1h'];
        $traderNames = ['Leon Jones', 'Sarah Chen', 'Michael Torres', 'Emma Wilson', 'David Kim', 'Lisa Anderson', 'James Brown', 'Maria Garcia'];
        $orderDirections = ['Call', 'Put'];

        $cryptoPair = $cryptoPairs[array_rand($cryptoPairs)];
        $orderCycle = $orderCycles[array_rand($orderCycles)];
        $traderName = $traderNames[array_rand($traderNames)];
        $orderDirection = $orderDirections[array_rand($orderDirections)];
        
        $profitRate = round(rand(5000, 9000) / 100, 2);
        $winningRate = round(rand(8500, 9950) / 100, 2);
        $followersCount = rand(500, 5000);
        $orderAmount = round($investment->amount * (rand(80, 120) / 100), 4);

        return [
            'trader_name' => $traderName,
            'crypto_pair' => $cryptoPair,
            'order_cycle' => $orderCycle,
            'profit_rate' => $profitRate,
            'winning_rate' => $winningRate,
            'followers_count' => $followersCount,
            'order_direction' => $orderDirection,
            'order_amount' => $orderAmount,
            'order_time' => now(),
        ];
    }
}
