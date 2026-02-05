<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ClaimedAmount;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{

public function dashboard()
{
    try {
        $userId = Auth::id();
        if (!$userId) {
            return ResponseHelper::error('Unauthorized', 401);
        }

        Log::info('Dashboard request for user ID: ' . $userId);

        // Ensure wallet exists
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $userId],
            [
                'withdrawal_amount' => 0,
                'deposit_amount'    => 0,
                'profit_amount'     => 0,
                'bonus_amount'      => 0,
                'referral_amount'   => 0,
                'total_balance'     => 0,
                'locked_amount'     => 0,
                'is_invested'       => false,
                'status'            => 'active',
            ]
        );

        // ✅ Step 1: Get latest investment
        $latestInvestment = Investment::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->where('status', 'active')
            ->first();

        $calculatedProfit = 0;
        $todaysProfit = 0;
        $totalProfitEarned = 0;

        if ($latestInvestment) {
            // Check expiry + auto-complete plan
            $latestInvestment->checkAndComplete();

            // Calculate profit live
            $calculatedProfit = $latestInvestment->total_profit;

            // Claimed amounts only for this investment
            $todaysProfit = (float) ClaimedAmount::where('user_id', $userId)
                ->where('investment_id', $latestInvestment->id)
                ->whereDate('created_at', Carbon::today())
                ->sum('amount');
                Log::info("todaysProfit for user $userId with investment id $latestInvestment->id", [$todaysProfit]);

            $totalProfitEarned = (float) ClaimedAmount::where('user_id', $userId)
                ->where('investment_id', $latestInvestment->id)
                ->sum('amount');
        }

        // ✅ Step 2: Balances
        $totalBalance   = (float) $wallet->total_balance;
        $lockedAmount   = (float) $wallet->locked_amount;
        $available      = max(0, $totalBalance - $lockedAmount);

        $withdrawn      = (float) ($wallet->withdrawal_amount ?? 0);
        $bonus          = (float) ($wallet->bonus_amount ?? 0);
        $referral       = (float) ($wallet->referral_amount ?? 0);

        // ✅ Step 3: Active plans count
        $activePlansCount = Investment::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        return ResponseHelper::success([
            // Wallet balances
            'total_balance'        => round($totalBalance, 2),       // from wallet
            'available_balance'    => round($available, 2),          // minus locked
            'locked_amount'        => round($lockedAmount, 2),

            // Plans
            'active_plans'         => $activePlansCount,

            // Profits (only latest investment)
            'profit_amount'        => round($calculatedProfit, 2),
            'todays_profit'        => round($todaysProfit, 2),
            'total_profit_earned'  => round($totalProfitEarned, 2),

            // Bonuses
            'referral_bonus_earned'=> round($bonus + $referral, 2),

            // Withdrawals (informational)
            'withdrawal_amount'    => round($withdrawn, 2),
        ], 'Dashboard data retrieved successfully');

    } catch (Exception $ex) {
        Log::error('Dashboard error', ['e' => $ex]);
        return ResponseHelper::error('Failed to load dashboard: ' . $ex->getMessage(), 500);
    }
}



    public function about()
    {
        try {
            // Starting point
            $startDate = Carbon::parse('2025-08-11');

            // Current time
            $now = Carbon::now();

            // Get total days passed
            $daysPassed = $startDate->diffInDays($now);

            // Convert days to weeks (including fractions)
            $weeksPassed = $daysPassed / 7;

            // Base and increment values
            $baseAmount = 500; // Start from 500 users
            $incrementPerWeek = 50; // Add 50 users per week

            $total_users = round($baseAmount + ($incrementPerWeek * $weeksPassed), 0);

            // Get real active investments count
            $active_investments = Investment::where('status', 'active')->count();

            return ResponseHelper::success([
                'total_users' => $total_users,
                'active_plans' => (string)$active_investments,
            ], 'About data retrieved successfully');
        } catch (Exception $ex) {
            Log::error('About data error: ' . $ex->getMessage());
            return ResponseHelper::error('Failed to retrieve about data: ' . $ex->getMessage());
        }
    }

    public function index()
    {
        $total_users = User::where('role', '!=', 'admin')->count();
        $all_users = User::where('role', '!=', 'admin')->latest()->limit(10)->get();
        $active_users = User::where('role', '!=', 'admin')->where('status', 'active')->count();

        $total_deposit = Deposit::sum('amount');
        $total_withdrawal = Withdrawal::where('status', 'active')->count();
        $total_withdrawal_amount = Withdrawal::where('status', 'active')->sum('amount');

        // New metrics
        $todayDepositsAmount = Deposit::whereDate('created_at', Carbon::today())->sum('amount');
        $todayDepositsCount = Deposit::whereDate('created_at', Carbon::today())->count();
        $todayWithdrawalsAmount = Withdrawal::whereDate('created_at', Carbon::today())->sum('amount');
        $todayWithdrawalsCount = Withdrawal::whereDate('created_at', Carbon::today())->count();

        $approved_deposits = Deposit::where('status', 'active')->count();
        $pending_deposits = Deposit::where('status', 'pending')->count();
        $active_withdrawal = Withdrawal::where('status', 'active')->count();
        $pending_withdrawal = Withdrawal::where('status', 'pending')->count();

        return view('admin.index', compact(
            'total_users',
            'all_users',
            'active_users',
            'total_deposit',
            'total_withdrawal',
            'total_withdrawal_amount',
            'approved_deposits',
            'pending_deposits',
            'active_withdrawal',
            'pending_withdrawal',
            'todayDepositsAmount',
            'todayDepositsCount',
            'todayWithdrawalsAmount',
            'todayWithdrawalsCount'
        ));
    }
}
