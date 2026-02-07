<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ClaimedAmount;
use App\Models\Investment;
use App\Models\MiningSession;
use App\Models\MiningCode;
use App\Models\UserExtraCode;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MiningController extends Controller
{
    public function start()
    {
        try {
            $user = Auth::user();
            if (!$user) return ResponseHelper::error('Unauthorized', 401);

            // One active session at a time
            $activeSession = MiningSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeSession) {
                return ResponseHelper::error('You already have an active mining session', 400);
            }

            // Require an active investment with a plan
            $investment = Investment::with('investmentPlan')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$investment || !$investment->investmentPlan) {
                return ResponseHelper::error('No active investment/plan found. Please invest to start mining.', 400);
            }

            $session = MiningSession::create([
                'user_id'       => $user->id,
                'investment_id' => $investment->id,
                'started_at'    => now(),
                'status'        => 'active',
                'progress'      => 0,
                'rewards_claimed' => false,
            ]);

            return ResponseHelper::success($session, 'Mining session started successfully');
        } catch (Exception $ex) {
            return ResponseHelper::error('Failed to start mining session: ' . $ex->getMessage());
        }
    }

    /**
     * Report mining status; mark completed when 24h elapse.
     * Does NOT credit wallet here.
     */
    public function status()
    {
        try {
            $user = Auth::user();
            if (!$user) return ResponseHelper::error('Unauthorized', 401);

            $session = MiningSession::where('user_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->orderByDesc('id')
                ->first();

            if (!$session) {
                return ResponseHelper::success([
                    'status'         => 'idle',
                    'progress'       => 0.00,
                    'time_remaining' => 0,
                    'started_at'     => null,
                    'session'        => null,
                    'debug'          => ['reason' => 'no_session'],
                ], 'No active mining session');
            }

            $duration = 24 * 60 * 60; // 24h in seconds
            $updatesRan = false;

            // ===== Non-active session branch =====
            if ($session->status !== 'active') {
                $safeProgress = ($session->status === 'completed')
                    ? 100.00
                    : max(0.00, min(100.00, (float)($session->progress ?? 0)));

                if ((float)$session->progress !== (float)$safeProgress) {
                    $session->update(['progress' => $safeProgress]);
                    $updatesRan = true;
                }

                $session->refresh();

                Log::info('mining.status non-active', [
                    'user_id'     => $user->id,
                    'status'      => $session->status,
                    'progress_db' => $session->progress,
                    'updates_ran' => $updatesRan,
                ]);

                return ResponseHelper::success([
                    'status'         => $session->status,
                    'progress'       => (float)$session->progress,
                    'time_remaining' => 0,
                    'started_at'     => $session->started_at,
                    'session'        => $session,
                    'debug'          => ['branch' => 'non_active'],
                ], $session->status === 'completed'
                    ? 'Mining session completed. Please claim your rewards.'
                    : 'Mining session not active');
            }

            // ===== ACTIVE: compute elapsed with timestamps (robust) =====
            $startedAt = \Carbon\Carbon::parse($session->started_at)->utc();
            $nowUtc    = now('UTC');

            // Compute elapsed in seconds
            $elapsed = $nowUtc->getTimestamp() - $startedAt->getTimestamp();

            // Clamp elapsed between 0 and 24h
            if ($elapsed < 0) $elapsed = 0;
            if ($elapsed > $duration) $elapsed = $duration;

            $progress      = round(($elapsed / $duration) * 100, 2);
            $timeRemaining = (int) max(0, $duration - $elapsed);

            $log = [
                'user_id'            => $user->id,
                'session_id'         => $session->id,
                'started_at_db'      => (string) $session->started_at,
                'started_at_utc'     => $startedAt->toIso8601String(),
                'now_utc'            => $nowUtc->toIso8601String(),
                'elapsed_epoch_s'    => $elapsed,
                'progress_calc'      => $progress,
                'time_remaining'     => $timeRemaining,
                'status_before'      => $session->status,
                'progress_db_before' => (float)$session->progress,
            ];

            // Completed
            if ($progress >= 100.00) {
                $updates = [
                    'status'   => 'completed',
                    'progress' => 100.00,
                ];
                if (empty($session->stopped_at)) {
                    $updates['stopped_at'] = $nowUtc;
                }
                $session->update($updates);
                $updatesRan = true;

                $session->refresh();
                $log['status_after'] = $session->status;
                $log['progress_db_after'] = (float)$session->progress;
                $log['updates_ran'] = $updatesRan;

                Log::info('mining.status transitioned_to_completed', $log);

                return ResponseHelper::success([
                    'status'         => 'completed',
                    'progress'       => 100.00,
                    'time_remaining' => 0,
                    'started_at'     => $session->started_at,
                    'session'        => $session,
                    'debug'          => ['branch' => 'completed'],
                ], 'Mining session completed. Please claim your rewards.');
            }

            // Still running → persist progress
            if ((float)$session->progress !== (float)$progress) {
                $session->update(['progress' => $progress]);
                $updatesRan = true;
            }
            $session->refresh();

            $log['status_after']       = $session->status;
            $log['progress_db_after']  = (float)$session->progress;
            $log['updates_ran']        = $updatesRan;

            Log::info('mining.status active_running', $log);

            return ResponseHelper::success([
                'status'         => 'active',
                'progress'       => (float)$session->progress,
                'time_remaining' => $timeRemaining,
                'started_at'     => $session->started_at,
                'session'        => $session,
                'debug'          => ['branch' => 'active_running'],
            ], 'Mining session in progress');
        } catch (\Throwable $ex) {
            Log::error('mining.status error', [
                'ex'    => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
            return ResponseHelper::error('Failed to get mining status: ' . $ex->getMessage());
        }
    }







    /**
     * Stop an active session (no rewards credited).
     */
    public function stop()
    {
        try {
            $user = Auth::user();
            if (!$user) return ResponseHelper::error('Unauthorized', 401);

            $session = MiningSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$session) {
                return ResponseHelper::error('No active mining session found', 400);
            }

            $session->update([
                'status'     => 'stopped',
                'stopped_at' => now(),
            ]);

            return ResponseHelper::success($session, 'Mining session stopped successfully');
        } catch (Exception $ex) {
            return ResponseHelper::error('Failed to stop mining session: ' . $ex->getMessage());
        }
    }

    /**
     * Get available trading sessions for today
     * Only shows sessions for codes the user has access to (daily codes + extra codes)
     */
    public function getAvailableSessions(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) return ResponseHelper::error('Unauthorized', 401);

            $today = Carbon::today();

            // Get codes available to this user
            // 1. Daily codes (code1 and code2)
            $dailyCodes = MiningCode::where('date', $today)
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();

            // 2. User-specific extra codes
            $extraCodes = UserExtraCode::where('user_id', $user->id)
                ->where('code_date', $today)
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();

            // Combine all available codes for this user
            $availableCodes = array_merge($dailyCodes, $extraCodes);

            if (empty($availableCodes)) {
                return ResponseHelper::success([], 'No trading sessions available. Please wait for admin to set codes.');
            }

            // Get sessions for this user with codes they have access to
            // Include both claimed and unclaimed sessions (to show "Already Claimed" status)
            $sessions = MiningSession::where('user_id', $user->id)
                ->where('code_date', $today)
                ->whereIn('used_code', $availableCodes)
                ->whereNotNull('trader_name')
                ->whereNotNull('crypto_pair')
                ->select([
                    'id',
                    'trader_name',
                    'crypto_pair',
                    'order_cycle',
                    'profit_rate',
                    'winning_rate',
                    'followers_count',
                    'order_direction',
                    'order_amount',
                    'order_time',
                    'used_code',
                    'rewards_claimed',
                ])
                ->orderBy('rewards_claimed', 'asc') // Unclaimed first
                ->orderBy('order_time', 'desc')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'trader_name' => $session->trader_name,
                        'crypto_pair' => $session->crypto_pair,
                        'order_cycle' => $session->order_cycle,
                        'profit_rate' => $session->profit_rate,
                        'winning_rate' => $session->winning_rate,
                        'followers_count' => $session->followers_count,
                        'order_direction' => $session->order_direction,
                        'order_amount' => $session->order_amount,
                        'order_time' => $session->order_time,
                        'used_code' => $session->used_code,
                        'is_claimed' => $session->rewards_claimed, // Add claimed status
                    ];
                })
                ->unique(function ($session) {
                    return $session['trader_name'] . '_' . $session['crypto_pair'] . '_' . $session['used_code'];
                })
                ->values();

            return ResponseHelper::success($sessions, 'Available trading sessions retrieved successfully');
        } catch (Exception $ex) {
            return ResponseHelper::error('Failed to get trading sessions: ' . $ex->getMessage());
        }
    }

    /**
     * Claim rewards using daily code:
     * - Admin has already created sessions for all users when codes were set
     * - User enters code, we find the matching session
     * - If code matches and session not claimed yet, give reward (half of full amount)
     * - Mark session as claimed and add to wallet
     */
    public function claimRewards(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) return ResponseHelper::error('Unauthorized', 401);

            // Validate code is provided
            $request->validate([
                'code' => 'required|string|max:50',
            ]);

            $today = Carbon::today();
            $code = trim($request->code);

            // Find the mining session for this user with matching code (case-insensitive)
            // First try exact match, then try case-insensitive
            $session = MiningSession::where('user_id', $user->id)
                ->where('code_date', $today)
                ->where('rewards_claimed', false)
                ->where(function($query) use ($code) {
                    $query->where('used_code', $code)
                          ->orWhereRaw('LOWER(used_code) = LOWER(?)', [$code]);
                })
                ->with('investment.investmentPlan')
                ->first();

            if (!$session) {
                // Check if code is valid but already claimed (case-insensitive)
                $claimedSession = MiningSession::where('user_id', $user->id)
                    ->where('code_date', $today)
                    ->where('rewards_claimed', true)
                    ->where(function($query) use ($code) {
                        $query->where('used_code', $code)
                              ->orWhereRaw('LOWER(used_code) = LOWER(?)', [$code]);
                    })
                    ->first();

                if ($claimedSession) {
                    return ResponseHelper::error('You have already claimed rewards for this code today.', 400);
                }

                // Check if code exists but no session found - could be regular daily code or user-specific extra code
                $validCode = MiningCode::where('date', $today)
                    ->where('is_active', true)
                    ->where(function($query) use ($code) {
                        $query->where('code', $code)
                              ->orWhereRaw('LOWER(code) = LOWER(?)', [$code]);
                    })
                    ->first();

                // Also check for user-specific extra codes
                $userExtraCode = UserExtraCode::where('user_id', $user->id)
                    ->where('code_date', $today)
                    ->where('is_active', true)
                    ->where(function($query) use ($code) {
                        $query->where('code', $code)
                              ->orWhereRaw('LOWER(code) = LOWER(?)', [$code]);
                    })
                    ->first();

                // Check if already claimed this user-specific code
                if ($userExtraCode) {
                    $claimedExtraCode = MiningSession::where('user_id', $user->id)
                        ->where('code_date', $today)
                        ->where('rewards_claimed', true)
                        ->where(function($query) use ($code) {
                            $query->where('used_code', $code)
                                  ->orWhereRaw('LOWER(used_code) = LOWER(?)', [$code]);
                        })
                        ->first();

                    if ($claimedExtraCode) {
                        return ResponseHelper::error('You have already claimed rewards for this code today.', 400);
                    }

                    // User has valid extra code - create session if they have active investment
                    $activeInvestment = Investment::with('investmentPlan')
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($activeInvestment && $activeInvestment->investmentPlan) {
                        // Generate trading data for this session
                        $tradingData = $this->generateTradingData($activeInvestment);
                        
                        // Create session for this user's extra code
                        $newSession = MiningSession::create(array_merge([
                            'user_id' => $user->id,
                            'investment_id' => $activeInvestment->id,
                            'started_at' => now(),
                            'status' => 'completed',
                            'progress' => 100.00,
                            'rewards_claimed' => false,
                            'used_code' => $code,
                            'code_date' => $today,
                        ], $tradingData));

                        // Retry finding the session
                        $session = MiningSession::where('id', $newSession->id)
                            ->with('investment.investmentPlan')
                            ->first();
                    } else {
                        return ResponseHelper::error('No active investment found. Please invest to claim rewards.', 400);
                    }
                } elseif ($validCode) {
                    // User has valid regular code but no session - they might have created investment after codes were set
                    // Try to create session on-the-fly if they have active investment
                    $activeInvestment = Investment::with('investmentPlan')
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($activeInvestment && $activeInvestment->investmentPlan) {
                        // Generate trading data for this session
                        $tradingData = $this->generateTradingData($activeInvestment);
                        
                        // Create session for this user
                        $newSession = MiningSession::create(array_merge([
                            'user_id' => $user->id,
                            'investment_id' => $activeInvestment->id,
                            'started_at' => now(),
                            'status' => 'completed',
                            'progress' => 100.00,
                            'rewards_claimed' => false,
                            'used_code' => $code,
                            'code_date' => $today,
                        ], $tradingData));

                        // Retry finding the session
                        $session = MiningSession::where('id', $newSession->id)
                            ->with('investment.investmentPlan')
                            ->first();
                    } else {
                        return ResponseHelper::error('No mining session found for this code. Please contact support.', 400);
                    }
                } else {
                    return ResponseHelper::error('Invalid or expired mining code. Please enter a valid code for today.', 400);
                }
            }

            // Get the investment from session
            $investment = $session->investment;
            if (!$investment || !$investment->investmentPlan) {
                return ResponseHelper::error('Investment/plan not found for this session.', 422);
            }

            // Check if investment is still active
            if ($investment->status !== 'active') {
                return ResponseHelper::error('This investment is no longer active. Cannot claim rewards.', 400);
            }

            // Calculate reward as HALF of normal (since 2 codes = 1 full session)
            $percentage = (float) $investment->investmentPlan->profit_percentage;
            $baseAmount = (float) $investment->amount;
            $fullAmount = round($baseAmount * ($percentage / 100), 2);
            $amount = round($fullAmount / 2, 2); // Half reward per code

            if ($amount <= 0) {
                return ResponseHelper::error('Calculated reward amount is zero. Check plan percentage and investment amount.', 422);
            }

            DB::transaction(function () use ($user, $session, $investment, $amount) {
                // 1) Mark session as claimed
                $session->update([
                    'rewards_claimed' => true,
                    'stopped_at' => now(),
                ]);

                // 2) Create claimed_amounts record
                ClaimedAmount::create([
                    'user_id'       => $user->id,
                    'investment_id' => $investment->id,
                    'amount'        => $amount,
                    'reason'        => 'mining_daily_profit_code_claim',
                ]);

                // 3) Update wallet.profit_amount
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0, 'profit_amount' => 0, 'bonus_amount' => 0]
                );

                $wallet->increment('profit_amount', $amount);
                // also update the total_balance
                $wallet->increment('total_balance', $amount);
            });

            return ResponseHelper::success([
                'claimed'     => true,
                'amount'      => $amount,
                'currency'    => 'USD',
                'message'     => 'Rewards claimed and added to wallet profit_amount',
            ], 'Mining rewards claimed successfully');
        } catch (Exception $ex) {
            Log::error('Claim rewards error', [
                'user_id' => Auth::id(),
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
            return ResponseHelper::error('Failed to claim mining rewards: ' . $ex->getMessage());
        }
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
