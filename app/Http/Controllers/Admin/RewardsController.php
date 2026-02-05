<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEdit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RewardsController extends Controller
{
    public function index()
    {
        $users = User::where('role', '!=', 'admin')->with('wallet')->paginate(20);
        return view('admin.pages.rewards', compact('users'));
    }

    public function updateMiningReward(Request $request, $userId)
    {
        $request->validate([
            'mining_reward' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255'
        ]);

        $user = User::findOrFail($userId);
        $wallet = $user->wallet;
        
        if (!$wallet) {
            return redirect()->back()->with('error', 'User wallet not found');
        }

        $oldValue = $wallet->mining_reward ?? 0;
        $newValue = $request->mining_reward;

        DB::beginTransaction();
        try {
            // Update wallet mining reward
            $wallet->mining_reward = $newValue;
            $wallet->save();

            // Log admin edit
            AdminEdit::create([
                'admin_id' => Auth::id(),
                'user_id' => $userId,
                'field_name' => 'mining_reward',
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'edit_type' => 'mining_reward',
                'reason' => $request->reason
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Mining reward updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update mining reward: ' . $e->getMessage());
        }
    }

    public function updateReferralBonus(Request $request, $userId)
    {
        $request->validate([
            'referral_bonus' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255'
        ]);

        $user = User::findOrFail($userId);
        $wallet = $user->wallet;
        
        if (!$wallet) {
            return redirect()->back()->with('error', 'User wallet not found');
        }

        $oldValue = $wallet->referral_amount ?? 0;
        $newValue = $request->referral_bonus;

        DB::beginTransaction();
        try {
            // Update wallet referral amount
            $wallet->referral_amount = $newValue;
            $wallet->save();

            // Log admin edit
            AdminEdit::create([
                'admin_id' => Auth::id(),
                'user_id' => $userId,
                'field_name' => 'referral_amount',
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'edit_type' => 'referral_bonus',
                'reason' => $request->reason
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Referral bonus updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update referral bonus: ' . $e->getMessage());
        }
    }

    public function updateLoyaltyBonus(Request $request, $userId)
    {
        // DISABLED - Loyalty bonus functionality is disabled
        return redirect()->back()->with('error', 'Loyalty bonus functionality is currently disabled');
    }

    public function getEditHistory($userId)
    {
        $edits = AdminEdit::with(['admin', 'user'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($edits);
    }
}
