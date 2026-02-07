<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Referrals;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Otp;
use App\Services\OtpService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    // register
    public function register(UserRequest $request)
    {
        try {
            $data = $request->validated();

            // Check if OTP is provided and valid
            if (!isset($data['otp'])) {
                return ResponseHelper::error('OTP is required for registration', 422);
            }

            // Check if user already exists
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                return ResponseHelper::error('User with this email already exists', 422);
            }

            // Verify OTP exists and is valid (check if already verified or still valid)
    
            // Allow registration even if OTP was already verified (frontend verification)
            // We'll mark it as used after successful registration

            $data['user_code'] = strtolower(str_replace(' ', '', $data['name'])) . rand(100, 999);
            $data['password'] = Hash::make($data['password']);

            $user = User::create($data);
            
            // Mark OTP as used after successful registration
            // $otpRecord->markAsUsed();
            $this->createWallet($user);

            // Create referral record
            Referrals::create([
                'referral_code' => $user->user_code,
                'user_id' => $user->id
            ]);

            // Process referral if provided
            if (!empty($user['referral_code'])) {
                $referralRecord = Referrals::where('referral_code', $user['referral_code'])->first();

                if ($referralRecord) {
                    $bonusAmount = $referralRecord->bonus_amount ?? 0;
                    $perUserBonus = 0;

                    // Get current total referrals
                    $total = $referralRecord->total_referrals;
                    $total += 1; // Increment locally

                    // Determine per-user bonus based on new total
                    if ($total <= 15) {
                        $perUserBonus = 15;
                    } elseif ($total <= 50) {
                        $perUserBonus = 20;
                    } elseif ($total <= 100) {
                        $perUserBonus = 25;
                    } elseif ($total <= 200) {
                        $perUserBonus = 30;
                    }

                    // Determine milestone bonus
                    if ($total == 15) {
                        $bonusAmount = 100;
                    } elseif ($total == 50) {
                        $bonusAmount = 200;
                    } elseif ($total == 100) {
                        $bonusAmount = 250;
                    } elseif ($total == 200) {
                        $bonusAmount = 300;
                    }

                    // Update values and save
                    if ($referralRecord) {
                        Referrals::where('id', $referralRecord->id)->update([
                            'total_referrals' => $total,
                            'per_user_referral' => $perUserBonus,
                            'referral_bonus_amount' => $bonusAmount
                        ]);
                    }
                }

                if (!empty($user['referral_code'])) {
                    // Multi-level referral chain
                    $this->processReferralChain($user, $user['referral_code']);
                }
            }

            return ResponseHelper::success($user, 'User is created successfully');
        } catch (Exception $ex) {
            return ResponseHelper::error('User is not created: ' . $ex->getMessage());
        }
    }
    protected function processReferralChain($newUser, $referralCode, $baseBonus = 100)
    {
        // Define profit chain percentages per level
        $profitChainPercentages = [
            1 => 1.00,  // 100%
            2 => 0.75,  // 75%
            3 => 0.50,  // 50%
            4 => 0.25,  // 25%
            5 => 0.05,  // 5%
        ];

        $currentReferralCode = $referralCode;
        $level = 1;

        while ($level <= 5 && $currentReferralCode) {
            // Find the referrer user by their user_code
            $referrerUser = User::where('user_code', $currentReferralCode)->first();

            if (!$referrerUser) {
                break; // Stop if no referrer found
            }

            // Find or create referral record for this referrer
            $referralRecord = Referrals::firstOrCreate(
                ['user_id' => $referrerUser->id],
                ['referral_code' => $referrerUser->user_code]
            );

            // Calculate level-based bonus
            $levelBonus = $baseBonus * $profitChainPercentages[$level];

            // Update total referrals
            $referralRecord->total_referrals += 1;

            // Add the bonus for this level
            $referralRecord->referral_bonus_amount += $levelBonus;

            // Save level bonus temporarily (could be used for audit or logs)
            $referralRecord->per_user_referral = $levelBonus;

            $referralRecord->save();

            // Go up the chain to next level
            $currentReferralCode = $referrerUser->referral_code;

            $level++;
        }
    }

    // user approval
    public function kyc($id)
    {
        try {
            $user = User::where('id', $id)->update([
                'status' => 'active',
            ]);

            return redirect()->route('users');
        } catch (Exception $ex) {
            return ResponseHelper::error('User KYC is not verified ' . $ex->getMessage());
        }
    }
    // login
  public function login(Request $request)
{
    try {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'otp'      => 'required|string|size:6',
        ]);

        // --- Step 1: Verify OTP ---
        $otpResult = $this->otpService->verifyOtp($data['email'], $data['otp'], 'login');
        if (!$otpResult['success']) {
            return ResponseHelper::error([
                'field'   => 'otp',
                'message' => $otpResult['message']
            ], 422);
        }

        // --- Step 2: Check user existence ---
        $user = \App\Models\User::where('email', $data['email'])->first();
        if (!$user) {
            return ResponseHelper::error([
                'field'   => 'email',
                'message' => 'No account found with this email address'
            ], 404);
        }

        // --- Step 3: Verify password ---
        if (!Hash::check($data['password'], $user->password)) {
            return ResponseHelper::error([
                'field'   => 'password',
                'message' => 'The password is incorrect'
            ], 401);
        }

        // --- Step 4: Successful login ---
        Auth::login($user);
        $token = $user->createToken("API Token")->plainTextToken;

        return response()->json([
            'status'     => true,
            'message'    => 'Login Successfully',
            'token_type' => 'bearer',
            'token'      => $token,
            'user'       => $user,
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return ResponseHelper::error([
            'field'   => $e->errors(),
            'message' => 'Validation failed'
        ], 422);
    } catch (Exception $ex) {
        return ResponseHelper::error('Login failed: ' . $ex->getMessage(), 500);
    }
}


    // all users
    public function allUser()
    {
        try {
            $users = User::where('role', '!=', 'admin')->get();
            return ResponseHelper::success($users, "All Users");
        } catch (Exception $ex) {
            return ResponseHelper::error('Don"t fetch all the users' . $ex);
        }
    }

    // single user/ profile
    public function profile()
    {
        try {
            Log::info('Profile request received', [
                'auth_id' => Auth::id(),
                'auth_check' => Auth::check(),
                'user' => Auth::user()
            ]);

            $user = User::find(Auth::id());
            if (!$user) {
                Log::error('User not found for ID: ' . Auth::id());
                return ResponseHelper::error('User not found');
            }

            Log::info('Profile data returned', ['user_id' => $user->id, 'email' => $user->email]);
            return ResponseHelper::success($user, "Your profile");
        } catch (Exception $ex) {
            Log::error('Profile error: ' . $ex->getMessage());
            return ResponseHelper::error('Not fetch the single user datas' . $ex);
        }
    }
    // update
    public function update(UserRequest $request)
    {
        try {
            Log::info('Profile update request received', [
                'auth_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            $data = $request->validated();
            $user = User::find(Auth::id());

            if (!$user) {
                Log::error('User not found for ID: ' . Auth::id());
                return ResponseHelper::error('User not found');
            }

            // Check if email is being changed and if it's already taken
            if (isset($data['email']) && $data['email'] !== $user->email) {
                $existingUser = User::where('email', $data['email'])->where('id', '!=', $user->id)->first();
                if ($existingUser) {
                    return ResponseHelper::error('Email is already taken');
                }
            }

            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Update user
            $user->update($data);

            Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($data)
            ]);

            return ResponseHelper::success($user, 'User profile updated successfully');
        } catch (Exception $ex) {
            Log::error('Profile update error: ' . $ex->getMessage());
            return ResponseHelper::error('Failed to update profile: ' . $ex->getMessage());
        }
    }

    // delete user
    public function deleteUser($userId)
    {
        try {
            $user = User::find($userId);

            User::where('id', $userId)->delete();
            return redirect()->route('users');
        } catch (Exception $e) {
            return ResponseHelper::error('User deletion failed', 500);
        }
    }

    // create wallet
    public function createWallet($user)
    {
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);
        Log::info('Wallet created for user', ['user_id' => $user->id, 'wallet_id' => $wallet->id]);
        return $wallet;
    }

    // logout
    public function logout()
    {
        try {
            $user = Auth::user();
            if ($user) {
                $user->tokens()->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Logged out successfully.'
                ]);
            }
            return ResponseHelper::success($user, 'User logged out successfully');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    // user-page
    public function user_page()
    {
        $all_users = User::where('role', '!=', 'admin')->get();
        $total_users = User::where('role', '!=', 'admin')->count();
        $active_users = User::where('role', '!=', 'admin')->where('status', 'active')->count();
        $inactive_user = User::where('role', '!=', 'admin')->where('status', 'inactive')->count();

        return view('admin.pages.users', compact('all_users', 'total_users', 'active_users', 'inactive_user'));
    }


    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function Adminlogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            // Optional: Ensure user is admin (if you use is_admin flag)
            if (auth()->user()->role === 'admin') {
                return redirect()->route('dashboard')->with('success', 'Welcome Admin!');
            }

            // Auth::logout();
            return back()->withErrors(['email' => 'You are not authorized as admin.']);
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
    }

    // Logout function
    public function Adminlogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }

    public function userDetail($id)
    {
        $user = User::with(['wallet', 'deposits', 'withdrawals', 'investments', 'claimedAmounts', 'transactions'])->find($id);
        // $referral = User::where('referral_code', $user->referral_id)->first();
        $referrals = $this->getUserReferrals($user, 5);
        if (!$user) {
            return ResponseHelper::error('User not found', 404);
        }
        
        // Get all transactions for the user
        $transactions = $user->transactions()->orderBy('created_at', 'desc')->get();
        
        // $user->load(['wallet', 'deposits', 'withdrawals', 'investments']);
        return view('admin.pages.user-detail', compact('user', 'referrals', 'transactions'));
    }

    // Update user wallet
    public function updateWallet(Request $request, $userId)
    {
        $request->validate([
            'deposit_amount' => 'nullable|numeric|min:0',
            'profit_amount' => 'nullable|numeric|min:0',
            'referral_amount' => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'withdrawal_amount' => 'nullable|numeric|min:0',
            'locked_amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:255'
        ]);

        $user = User::findOrFail($userId);
        $wallet = $user->wallet;

        if (!$wallet) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'status' => 'active'
            ]);
        }

        // Toggle user status between active/inactive
     

        DB::beginTransaction();
        try {
            $oldValues = $wallet->toArray();
            $changes = [];

            // Update each field if provided
            $fields = ['deposit_amount', 'profit_amount', 'referral_amount', 'bonus_amount', 'withdrawal_amount', 'locked_amount'];
            
            foreach ($fields as $field) {
                if ($request->has($field) && $request->$field !== null) {
                    $oldValue = $wallet->$field ?? 0;
                    $newValue = $request->$field;
                    
                    if ($oldValue != $newValue) {
                        $wallet->$field = $newValue;
                        $changes[$field] = [
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                    }
                }
            }

            $wallet->save();

            // Log each change
            foreach ($changes as $field => $change) {
                \App\Models\AdminEdit::create([
                    'admin_id' => Auth::id(),
                    'user_id' => $userId,
                    'field_name' => $field,
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                    'edit_type' => 'wallet_update',
                    'reason' => $request->reason ?? 'Admin updated wallet balance'
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Wallet balances updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update wallet: ' . $e->getMessage());
        }
    }
    public function toggleStatus(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->status = $newStatus;
        $user->save();

        return redirect()->back()->with('success', "User status updated to {$newStatus}.");
    }
    public function getUserReferrals(User $user, $maxDepth = 5)
{
    $referrals = collect(); // Flat collection of all referrals
    $currentLevel = collect([$user]);

    for ($depth = 1; $depth <= $maxDepth; $depth++) {
        $nextLevel = collect();

        foreach ($currentLevel as $u) {
            $children = User::where('referral_code', $u->user_code)->get();

            // Optional: Tag the referral level
            $children->each(function ($child) use ($depth) {
                $child->referral_level = $depth;
            });

            $referrals = $referrals->merge($children);
            $nextLevel = $nextLevel->merge($children);
        }

        // Stop if no children found
        if ($nextLevel->isEmpty()) {
            break;
        }

        $currentLevel = $nextLevel;
    }

    return $referrals;
}

    // Update transaction amount and description
    public function updateTransaction(Request $request, $transactionId)
    {
        try {
            $request->validate([
                'amount' => 'nullable|numeric|min:0',
                'description' => 'nullable|string|max:500',
                'status' => 'nullable|string|in:pending,completed,failed',
                'reason' => 'nullable|string|max:255'
            ]);

            $transaction = \App\Models\Transaction::findOrFail($transactionId);
            $oldValues = [
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'status' => $transaction->status
            ];

            $changes = [];
            
            // Update amount if provided
            if ($request->has('amount') && $request->amount !== null) {
                if ($transaction->amount != $request->amount) {
                    $transaction->amount = $request->amount;
                    $changes['amount'] = [
                        'old' => $oldValues['amount'],
                        'new' => $request->amount
                    ];
                }
            }

            // Update description if provided
            if ($request->has('description') && $request->description !== null) {
                if ($transaction->description != $request->description) {
                    $transaction->description = $request->description;
                    $changes['description'] = [
                        'old' => $oldValues['description'],
                        'new' => $request->description
                    ];
                }
            }

            // Update status if provided
            if ($request->has('status') && $request->status !== null) {
                if ($transaction->status != $request->status) {
                    $transaction->status = $request->status;
                    $changes['status'] = [
                        'old' => $oldValues['status'],
                        'new' => $request->status
                    ];
                }
            }

            if (empty($changes)) {
                return ResponseHelper::error('No changes detected', 400);
            }

            $transaction->save();

            // Log each change
            foreach ($changes as $field => $change) {
                \App\Models\AdminEdit::create([
                    'admin_id' => Auth::id(),
                    'user_id' => $transaction->user_id,
                    'field_name' => $field,
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                    'edit_type' => 'transaction_update',
                    'reason' => $request->reason ?? 'Admin updated transaction'
                ]);
            }

            return ResponseHelper::success($transaction, 'Transaction updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    // Get all transactions for admin with pagination and filters
    public function getAllTransactions(Request $request)
    {
        try {
            $query = \App\Models\Transaction::with(['user', 'deposit', 'withdrawal']);

            // Filter by user email if provided
            if ($request->has('user_email') && !empty($request->user_email)) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->user_email . '%');
                });
            }

            // Filter by transaction type if provided
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Filter by status if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by date range if provided
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $transactions = $query->paginate($perPage);

            return ResponseHelper::success($transactions, 'Transactions retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve transactions: ' . $e->getMessage(), 500);
        }
    }

    // Get single transaction details for admin
    public function getTransactionDetails($transactionId)
    {
        try {
            $transaction = \App\Models\Transaction::with(['user', 'deposit', 'withdrawal'])
                ->findOrFail($transactionId);

            // Get edit history for this transaction
            $editHistory = \App\Models\AdminEdit::where('user_id', $transaction->user_id)
                ->where('edit_type', 'transaction_update')
                ->orderBy('created_at', 'desc')
                ->get();

            return ResponseHelper::success([
                'transaction' => $transaction,
                'edit_history' => $editHistory
            ], 'Transaction details retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve transaction details: ' . $e->getMessage(), 500);
        }
    }

    // Bulk update transactions
    public function bulkUpdateTransactions(Request $request)
    {
        try {
            $request->validate([
                'transaction_ids' => 'required|array|min:1',
                'transaction_ids.*' => 'integer|exists:transactions,id',
                'amount' => 'nullable|numeric|min:0',
                'description' => 'nullable|string|max:500',
                'reason' => 'required|string|max:255'
            ]);

            $transactionIds = $request->transaction_ids;
            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($transactionIds as $transactionId) {
                    $transaction = \App\Models\Transaction::find($transactionId);
                    if (!$transaction) {
                        $errors[] = "Transaction ID {$transactionId} not found";
                        continue;
                    }

                    $oldValues = [
                        'amount' => $transaction->amount,
                        'description' => $transaction->description
                    ];

                    $changes = [];

                    // Update amount if provided
                    if ($request->has('amount') && $request->amount !== null) {
                        if ($transaction->amount != $request->amount) {
                            $transaction->amount = $request->amount;
                            $changes['amount'] = [
                                'old' => $oldValues['amount'],
                                'new' => $request->amount
                            ];
                        }
                    }

                    // Update description if provided
                    if ($request->has('description') && $request->description !== null) {
                        if ($transaction->description != $request->description) {
                            $transaction->description = $request->description;
                            $changes['description'] = [
                                'old' => $oldValues['description'],
                                'new' => $request->description
                            ];
                        }
                    }

                    if (!empty($changes)) {
                        $transaction->save();

                        // Log each change
                        foreach ($changes as $field => $change) {
                            \App\Models\AdminEdit::create([
                                'admin_id' => Auth::id(),
                                'user_id' => $transaction->user_id,
                                'field_name' => $field,
                                'old_value' => $change['old'],
                                'new_value' => $change['new'],
                                'edit_type' => 'bulk_transaction_update',
                                'reason' => $request->reason
                            ]);
                        }

                        $updatedCount++;
                    }
                }

                DB::commit();
                return ResponseHelper::success([
                    'updated_count' => $updatedCount,
                    'errors' => $errors
                ], "Bulk update completed. {$updatedCount} transactions updated successfully");
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to bulk update transactions: ' . $e->getMessage(), 500);
        }
    }

    // Update referral amount for a specific user
    public function updateReferralAmount(Request $request, $userId)
    {
        try {
            $request->validate([
                'referral_amount' => 'required|numeric|min:0',
                'reason' => 'required|string|max:255'
            ]);

            $user = User::findOrFail($userId);
            $wallet = $user->wallet;

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'status' => 'active'
                ]);
            }

            $oldAmount = $wallet->referral_amount ?? 0;
            $newAmount = $request->referral_amount;

            if ($oldAmount != $newAmount) {
                $wallet->referral_amount = $newAmount;
                $wallet->save();

                // Log the change
                \App\Models\AdminEdit::create([
                    'admin_id' => Auth::id(),
                    'user_id' => $userId,
                    'field_name' => 'referral_amount',
                    'old_value' => $oldAmount,
                    'new_value' => $newAmount,
                    'edit_type' => 'referral_amount_update',
                    'reason' => $request->reason
                ]);

                return ResponseHelper::success([
                    'user_id' => $userId,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'total_balance' => $wallet->total_balance
                ], 'Referral amount updated successfully');
            }

            return ResponseHelper::error('No changes detected', 400);
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update referral amount: ' . $e->getMessage(), 500);
        }
    }


    // Delete transaction
    public function deleteTransaction(Request $request, $transactionId)
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:255'
            ]);

            $transaction = \App\Models\Transaction::findOrFail($transactionId);
            $userId = $transaction->user_id;
            $amount = $transaction->amount;
            $type = $transaction->type;

            // Log the deletion
            \App\Models\AdminEdit::create([
                'admin_id' => Auth::id(),
                'user_id' => $userId,
                'field_name' => 'transaction_deleted',
                'old_value' => $amount,
                'new_value' => 0,
                'edit_type' => 'transaction_delete',
                'reason' => $request->reason
            ]);

            $transaction->delete();

            return ResponseHelper::success([
                'transaction_id' => $transactionId,
                'deleted_amount' => $amount,
                'transaction_type' => $type
            ], 'Transaction deleted successfully');

        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to delete transaction: ' . $e->getMessage(), 500);
        }
    }

    // Update specific referral transaction amount
    public function updateReferralTransaction(Request $request, $transactionId)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0',
                'reason' => 'required|string|max:255'
            ]);

            $transaction = \App\Models\Transaction::findOrFail($transactionId);
            
            // Verify this is a referral transaction
            if ($transaction->type !== 'referral') {
                return ResponseHelper::error('This is not a referral transaction', 400);
            }

            $oldAmount = $transaction->amount;
            $newAmount = $request->amount;

            if ($oldAmount != $newAmount) {
                $transaction->amount = $newAmount;
                $transaction->save();

                // Update user's wallet referral amount
                $user = $transaction->user;
                if ($user && $user->wallet) {
                    $wallet = $user->wallet;
                    $currentReferralAmount = $wallet->referral_amount ?? 0;
                    $difference = $newAmount - $oldAmount;
                    $wallet->referral_amount = max(0, $currentReferralAmount + $difference);
                    $wallet->save();
                }

                // Log the change
                \App\Models\AdminEdit::create([
                    'admin_id' => Auth::id(),
                    'user_id' => $transaction->user_id,
                    'field_name' => 'referral_transaction_amount',
                    'old_value' => $oldAmount,
                    'new_value' => $newAmount,
                    'edit_type' => 'referral_transaction_update',
                    'reason' => $request->reason
                ]);

                return ResponseHelper::success([
                    'transaction_id' => $transactionId,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'user_id' => $transaction->user_id
                ], 'Referral transaction updated successfully');
            }

            return ResponseHelper::error('No changes detected', 400);
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update referral transaction: ' . $e->getMessage(), 500);
        }
    }

    // Get user's referral transactions with edit history
    public function getUserReferralTransactions($userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            $referralTransactions = \App\Models\Transaction::where('user_id', $userId)
                ->where('type', 'referral')
                ->with(['user'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Get edit history for referral transactions
            $editHistory = \App\Models\AdminEdit::where('user_id', $userId)
                ->whereIn('edit_type', ['referral_amount_update', 'referral_transaction_update'])
                ->orderBy('created_at', 'desc')
                ->get();

            return ResponseHelper::success([
                'user' => $user,
                'referral_transactions' => $referralTransactions,
                'edit_history' => $editHistory,
                'wallet' => $user->wallet
            ], 'Referral transactions retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve referral transactions: ' . $e->getMessage(), 500);
        }
    }

    // Bulk update referral amounts for multiple users
    public function bulkUpdateReferralAmounts(Request $request)
    {
        try {
            $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'integer|exists:users,id',
                'referral_amount' => 'required|numeric|min:0',
                'reason' => 'required|string|max:255'
            ]);

            $userIds = $request->user_ids;
            $newAmount = $request->referral_amount;
            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($userIds as $userId) {
                    $user = User::find($userId);
                    if (!$user) {
                        $errors[] = "User ID {$userId} not found";
                        continue;
                    }

                    $wallet = $user->wallet;
                    if (!$wallet) {
                        $wallet = Wallet::create([
                            'user_id' => $user->id,
                            'status' => 'active'
                        ]);
                    }

                    $oldAmount = $wallet->referral_amount ?? 0;
                    
                    if ($oldAmount != $newAmount) {
                        $wallet->referral_amount = $newAmount;
                        $wallet->save();

                        // Log the change
                        \App\Models\AdminEdit::create([
                            'admin_id' => Auth::id(),
                            'user_id' => $userId,
                            'field_name' => 'referral_amount',
                            'old_value' => $oldAmount,
                            'new_value' => $newAmount,
                            'edit_type' => 'bulk_referral_amount_update',
                            'reason' => $request->reason
                        ]);

                        $updatedCount++;
                    }
                }

                DB::commit();
                return ResponseHelper::success([
                    'updated_count' => $updatedCount,
                    'errors' => $errors
                ], "Bulk update completed. {$updatedCount} users updated successfully");
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to bulk update referral amounts: ' . $e->getMessage(), 500);
        }
    }

}
