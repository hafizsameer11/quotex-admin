<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletOps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvestmentControlController extends Controller
{
    /**
     * Display all active investments with management options
     */
    public function index()
    {
        $activeInvestments = Investment::with(['user', 'investmentPlan'])
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_active' => Investment::where('status', 'active')->count(),
            'total_amount' => Investment::where('status', 'active')->sum('amount'),
            'total_expected_return' => Investment::where('status', 'active')->sum('expected_return'),
            'today_started' => Investment::where('status', 'active')
                ->whereDate('start_date', Carbon::today())
                ->count(),
        ];

        return view('admin.pages.investment-control', compact('activeInvestments', 'stats'));
    }

    /**
     * Cancel an active investment
     */
    public function cancelInvestment(Request $request, $investmentId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'refund_amount' => 'nullable|numeric|min:0'
        ]);

        $investment = Investment::with('user.wallet')->findOrFail($investmentId);

        if ($investment->status !== 'active') {
            return redirect()->back()->with('error', 'Only active investments can be cancelled.');
        }

        DB::beginTransaction();
        try {
            // Calculate refund amount (default to locked amount if not specified)
            $refundAmount = $request->refund_amount ?? $investment->amount;
            
            // Update investment status
            $investment->update([
                'status' => 'canceled',
                'end_date' => Carbon::now(),
                'notes' => 'Cancelled by admin: ' . $request->reason
            ]);

            // Refund to user's deposit amount
            $wallet = $investment->user->wallet;
            if ($wallet) {
                $wallet->increment('deposit_amount', $refundAmount);
                $wallet->decrement('locked_amount', $refundAmount);
                $wallet->save();
            }

            // Log the admin action
            \App\Models\AdminEdit::create([
                'admin_id' => Auth::id(),
                'user_id' => $investment->user_id,
                'field_name' => 'investment_cancelled',
                'old_value' => 'active',
                'new_value' => 'canceled',
                'edit_type' => 'cancel_investment',
                'reason' => $request->reason
            ]);

            DB::commit();
            return redirect()->back()->with('success', "Investment #{$investmentId} cancelled successfully. Refunded \${$refundAmount} to user's deposit balance.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to cancel investment: ' . $e->getMessage());
        }
    }

    /**
     * Force complete an investment
     */
    public function completeInvestment(Request $request, $investmentId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'final_profit' => 'nullable|numeric|min:0'
        ]);

        $investment = Investment::with('user.wallet')->findOrFail($investmentId);

        if ($investment->status !== 'active') {
            return redirect()->back()->with('error', 'Only active investments can be completed.');
        }

        DB::beginTransaction();
        try {
            // Calculate final profit (default to expected return if not specified)
            $finalProfit = $request->final_profit ?? $investment->expected_return;
            
            // Update investment status
            $investment->update([
                'status' => 'completed',
                'end_date' => Carbon::now(),
                'expected_return' => $finalProfit,
                'notes' => 'Completed by admin: ' . $request->reason
            ]);

            // Add profit to user's wallet
            $wallet = $investment->user->wallet;
            if ($wallet) {
                $wallet->increment('profit_amount', $finalProfit);
                $wallet->decrement('locked_amount', $investment->amount);
                $wallet->save();
            }

            // Log the admin action
            \App\Models\AdminEdit::create([
                'admin_id' => Auth::id(),
                'user_id' => $investment->user_id,
                'field_name' => 'investment_completed',
                'old_value' => 'active',
                'new_value' => 'completed',
                'edit_type' => 'complete_investment',
                'reason' => $request->reason
            ]);

            DB::commit();
            return redirect()->back()->with('success', "Investment #{$investmentId} completed successfully. Added \${$finalProfit} profit to user's wallet.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to complete investment: ' . $e->getMessage());
        }
    }

    /**
     * Get investment details for modal
     */
    public function getInvestmentDetails($investmentId)
    {
        $investment = Investment::with(['user', 'investmentPlan', 'claimedAmounts'])
            ->findOrFail($investmentId);

        return response()->json([
            'success' => true,
            'data' => $investment
        ]);
    }

    /**
     * Update investment details
     */
    public function updateInvestment(Request $request, $investmentId)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'expected_return' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'reason' => 'required|string|max:500'
        ]);

        $investment = Investment::findOrFail($investmentId);
        $oldValues = $investment->toArray();

        DB::beginTransaction();
        try {
            $investment->update($request->only(['amount', 'expected_return', 'start_date', 'end_date']));

            // Log changes
            foreach ($request->only(['amount', 'expected_return', 'start_date', 'end_date']) as $field => $value) {
                if ($oldValues[$field] != $value) {
                    \App\Models\AdminEdit::create([
                        'admin_id' => Auth::id(),
                        'user_id' => $investment->user_id,
                        'field_name' => $field,
                        'old_value' => $oldValues[$field],
                        'new_value' => $value,
                        'edit_type' => 'update_investment',
                        'reason' => $request->reason
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "Investment #{$investmentId} updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update investment: ' . $e->getMessage());
        }
    }
}







