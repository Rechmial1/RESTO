<?php

namespace Modules\CashRegister\Console\Commands;

use Illuminate\Console\Command;
use Modules\CashRegister\Database\Seeders\CashRegisterDatabaseSeeder;

class EnsureOpenRegister extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashregister:ensure-open {--user-id= : Specific user ID to create open register for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure the current user (or specified user) has an open cash register session';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');

        if ($userId) {
            // Create open register for specific user
            $user = \App\Models\User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }

            // Temporarily set the authenticated user
            auth()->login($user);
        }

        $success = CashRegisterDatabaseSeeder::ensureCurrentUserHasOpenRegister();

        if ($success) {
            $user = auth()->user();
            $this->info("✅ Open cash register session created for user: {$user->name} ({$user->email})");
            $this->info("💰 Opening float: ₹5,000 | Expected cash: ₹8,000");
            $this->info("");
            $this->info("📊 Demo data includes:");
            $this->info("   • Open register sessions for all admin users");
            $this->info("   • Session pending approval (with ₹20 discrepancy)");
            $this->info("   • Approved session (perfect count) for Z-report testing");
            $this->info("   • Realistic transactions and cash denominations");
        } else {
            $this->error("❌ Failed to create open register session. Make sure you're logged in and have a restaurant/branch assigned.");
            return 1;
        }

        return 0;
    }
}
