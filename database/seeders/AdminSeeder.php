<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $existingAdmin = User::where('email', 'admin@admin.com')->first();
        
        if ($existingAdmin) {
            $this->command->info('Admin user already exists. Skipping...');
            return;
        }

        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'user_name' => 'admin',
            'email' => 'admin@admin.com',
            'phone' => '+1234567890',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('admin123'), // Default password - CHANGE THIS IN PRODUCTION!
            'email_verified_at' => now(),
            'user_code' => 'ADMIN' . strtoupper(Str::random(6)),
            'referral_code' => null, // Admin doesn't have a referral code
        ]);

        // Create wallet for admin (optional, but good for consistency)
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'withdrawal_amount' => 0,
                'deposit_amount' => 0,
                'profit_amount' => 0,
                'bonus_amount' => 0,
                'referral_amount' => 0,
                'total_balance' => 0,
                'locked_amount' => 0,
                'is_invested' => false,
                'status' => 'active',
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@admin.com');
        $this->command->info('Password: admin123');
        $this->command->warn('⚠️  IMPORTANT: Change the admin password after first login!');
    }
}

