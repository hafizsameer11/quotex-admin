<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserExtraCode;
use App\Models\User;
use App\Models\MiningSession;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserExtraCodeController extends Controller
{
    /**
     * Display the user extra codes management page
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Get all users for selection
        $users = User::where('role', '!=', 'admin')
            ->orderBy('name')
            ->orderBy('email')
            ->get();
        
        // Get today's extra codes with users
        $todayExtraCodes = UserExtraCode::with(['user', 'creator'])
            ->where('code_date', $today)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Group by user
        $codesByUser = $todayExtraCodes->groupBy('user_id');
        
        return view('admin.pages.user-extra-codes', compact('users', 'todayExtraCodes', 'codesByUser', 'today'));
    }

    /**
     * Store extra codes for selected users
     */
    public function store(Request $request)
    {
        // Parse codes from textarea (split by newline or comma)
        $codesInput = $request->input('codes_input', '');
        $codes = array_filter(
            array_map('trim', preg_split('/[\n,]+/', $codesInput)),
            function($code) { return !empty($code); }
        );

        $request->merge(['codes' => array_values($codes)]);
        
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'codes' => 'required|array|min:1',
            'codes.*' => 'required|string|max:50',
        ]);

        $today = Carbon::today();
        $adminId = Auth::id();

        DB::beginTransaction();
        try {
            $sessionsCreated = 0;
            
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                if (!$user) continue;

                // Get user's active investment
                $investment = Investment::with('investmentPlan')
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$investment || !$investment->investmentPlan) {
                    continue; // Skip users without active investments
                }

                // Create codes for this user
                foreach ($request->codes as $code) {
                    $code = trim($code);
                    if (empty($code)) continue;

                    // Create the extra code
                    $extraCode = UserExtraCode::create([
                        'user_id' => $userId,
                        'code' => $code,
                        'code_date' => $today,
                        'is_active' => true,
                        'created_by' => $adminId,
                    ]);

                    // Create mining session for this code
                    MiningSession::create([
                        'user_id' => $userId,
                        'investment_id' => $investment->id,
                        'started_at' => now(),
                        'status' => 'completed',
                        'progress' => 100.00,
                        'rewards_claimed' => false,
                        'used_code' => $code,
                        'code_date' => $today,
                    ]);

                    $sessionsCreated++;
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "Extra codes assigned successfully. Created {$sessionsCreated} mining sessions for selected users.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to assign extra codes: ' . $e->getMessage());
        }
    }

    /**
     * Delete an extra code
     */
    public function destroy($id)
    {
        try {
            $extraCode = UserExtraCode::findOrFail($id);
            
            // Delete associated unclaimed session if exists
            MiningSession::where('user_id', $extraCode->user_id)
                ->where('code_date', $extraCode->code_date)
                ->where('used_code', $extraCode->code)
                ->where('rewards_claimed', false)
                ->delete();
            
            $extraCode->delete();
            
            return redirect()->back()->with('success', 'Extra code deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete extra code: ' . $e->getMessage());
        }
    }
}
