<?php

namespace Modules\CashRegister\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CashRegister\Entities\CashDenomination;
use Modules\CashRegister\Entities\CashRegister;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;
use Modules\CashRegister\Entities\CashRegisterSetting;
use Modules\CashRegister\Entities\CashRegisterApproval;
use Modules\CashRegister\Entities\Denomination;
use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;

class CashRegisterDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('codecanyon')) {
            return; // Skip if in codecanyon environment
        }

        // Delete all existing cash register data to start fresh
        CashRegisterTransaction::truncate();
        CashRegisterSession::truncate();
        CashRegisterApproval::truncate();

        $this->seedDenominations();
        $this->seedCashDenominations();
        $this->seedCashRegisters();
        $this->seedCashRegisterSettings();
        $this->seedCashRegisterSessions(); // This now creates transactions for open sessions
        $this->seedCashRegisterTransactions(); // This handles closed sessions
        $this->closeExtraOpenSessions(); // Close all but one open session per user
        $this->seedAdminDemoData();
    }

    private function seedDenominations(): void
    {
        $denominations = [
            ['name' => '2000', 'value' => 2000.00, 'type' => 'note', 'is_active' => true],
            ['name' => '500', 'value' => 500.00, 'type' => 'note', 'is_active' => true],
            ['name' => '200', 'value' => 200.00, 'type' => 'note', 'is_active' => true],
            ['name' => '100', 'value' => 100.00, 'type' => 'note', 'is_active' => true],
            ['name' => '50', 'value' => 50.00, 'type' => 'note', 'is_active' => true],
            ['name' => '20', 'value' => 20.00, 'type' => 'note', 'is_active' => true],
            ['name' => '10', 'value' => 10.00, 'type' => 'coin', 'is_active' => true],
            ['name' => '5', 'value' => 5.00, 'type' => 'coin', 'is_active' => true],
            ['name' => '2', 'value' => 2.00, 'type' => 'coin', 'is_active' => true],
            ['name' => '1', 'value' => 1.00, 'type' => 'coin', 'is_active' => true],
        ];

        // Get first available restaurant
        $restaurant = \App\Models\Restaurant::first();

        if (!$restaurant) {
            return; // Skip if no restaurants exist
        }

        foreach ($denominations as $denomination) {
            Denomination::firstOrCreate(
                [
                    'value' => $denomination['value'],
                    'type' => $denomination['type'],
                    'restaurant_id' => $restaurant->id,
                ],
                [
                    'name' => $denomination['name'],
                    'is_active' => $denomination['is_active'],
                ]
            );
        }
    }

    private function seedCashDenominations(): void
    {
        $cashDenominations = [
            ['value' => 2000, 'sort_order' => 1, 'is_active' => true],
            ['value' => 500, 'sort_order' => 2, 'is_active' => true],
            ['value' => 200, 'sort_order' => 3, 'is_active' => true],
            ['value' => 100, 'sort_order' => 4, 'is_active' => true],
            ['value' => 50, 'sort_order' => 5, 'is_active' => true],
            ['value' => 20, 'sort_order' => 6, 'is_active' => true],
            ['value' => 10, 'sort_order' => 7, 'is_active' => true],
            ['value' => 5, 'sort_order' => 8, 'is_active' => true],
            ['value' => 2, 'sort_order' => 9, 'is_active' => true],
            ['value' => 1, 'sort_order' => 10, 'is_active' => true],
        ];

        // Get first available restaurant
        $restaurant = \App\Models\Restaurant::first();
        if (!$restaurant) {
            return; // Skip if no restaurants exist
        }

        foreach ($cashDenominations as $cashDenomination) {
            \Modules\CashRegister\Entities\CashDenomination::firstOrCreate(
                [
                    'value' => $cashDenomination['value'],
                    'restaurant_id' => $restaurant->id,
                ],
                [
                    'sort_order' => $cashDenomination['sort_order'],
                    'is_active' => $cashDenomination['is_active'],
                ]
            );
        }
    }

    private function seedCashRegisters(): void
    {
        $branches = Branch::take(3)->get();

        if ($branches->isEmpty()) {
            return; // Skip if no branches exist
        }

        foreach ($branches as $branch) {
            CashRegister::firstOrCreate(
                [
                    'restaurant_id' => $branch->restaurant_id,
                    'branch_id' => $branch->id,
                    'name' => 'Main Register'
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedCashRegisterSettings(): void
    {
        $restaurants = \App\Models\Restaurant::take(2)->get();

        if ($restaurants->isEmpty()) {
            return; // Skip if no restaurants exist
        }

        foreach ($restaurants as $restaurant) {
            CashRegisterSetting::firstOrCreate(
                ['restaurant_id' => $restaurant->id],
                [
                    'force_open_after_login' => true,
                    'force_open_roles' => [2, 3], // Assuming role IDs 2 and 3 are cashier roles
                ]
            );
        }
    }

    private function seedCashRegisterSessions(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['cashier', 'Cashier']);
        })->take(3)->get();

        // Fallback to any users if no cashier users found
        if ($users->isEmpty()) {
            $users = User::take(3)->get();
        }

        // Skip if no users available
        if ($users->isEmpty()) {
            return;
        }

        $registers = CashRegister::with('branch')->take(3)->get();
        
        // Track users who have received open sessions
        $usersWithOpenSessions = [];

        foreach ($registers as $register) {
            $user = $users->random();

            // Create some closed sessions
            for ($i = 0; $i < 3; $i++) {
                $openedAt = Carbon::now()->subDays(rand(1, 30))->setHour(rand(8, 12))->setMinute(rand(0, 59));
                $closedAt = $openedAt->copy()->addHours(rand(6, 10));

                CashRegisterSession::create([
                    'cash_register_id' => $register->id,
                    'restaurant_id' => $register->restaurant_id,
                    'branch_id' => $register->branch_id,
                    'opened_by' => $user->id,
                    'closed_by' => $user->id,
                    'opened_at' => $openedAt,
                    'closed_at' => $closedAt,
                    'status' => 'closed',
                    'opening_float' => rand(1000, 5000),
                    'expected_cash' => rand(3000, 10000),
                    'counted_cash' => rand(2800, 9800),
                    'discrepancy' => rand(-200, 200),
                    'closing_note' => 'End of day closing',
                ]);
            }

            // Create one open session ONLY if user doesn't already have one
            if (!in_array($user->id, $usersWithOpenSessions)) {
                $openSession = CashRegisterSession::create([
                    'cash_register_id' => $register->id,
                    'restaurant_id' => $register->restaurant_id,
                    'branch_id' => $register->branch_id,
                    'opened_by' => $user->id,
                    'opened_at' => Carbon::now()->subHours(rand(1, 6)),
                    'status' => 'open',
                    'opening_float' => rand(1000, 5000),
                    'expected_cash' => rand(3000, 10000),
                ]);
                
                // Mark this user as having an open session
                $usersWithOpenSessions[] = $user->id;
                
                // Create transactions for this open session
                $this->createOpenSessionTransactions($openSession, $user);
            }
        }
    }

    private function createOpenSessionTransactions(CashRegisterSession $session, User $user): void
    {
        // Create opening float transaction
        CashRegisterTransaction::create([
            'cash_register_session_id' => $session->id,
            'restaurant_id' => $session->restaurant_id,
            'branch_id' => $session->branch_id,
            'type' => 'opening_float',
            'amount' => $session->opening_float,
            'reason' => 'Opening float',
            'happened_at' => $session->opened_at,
            'created_by' => $user->id,
        ]);

        // Create a mix of cash in, cash out, and safe drop transactions
        $transactions = [
            ['type' => 'cash_sale', 'amount' => rand(150, 500), 'reason' => 'Order payment - Table 5', 'minutes' => 30],
            ['type' => 'cash_sale', 'amount' => rand(100, 400), 'reason' => 'Takeaway order', 'minutes' => 60],
            ['type' => 'cash_in', 'amount' => rand(200, 800), 'reason' => 'Additional float added', 'minutes' => 90],
            ['type' => 'cash_sale', 'amount' => rand(200, 600), 'reason' => 'Order payment - Table 8', 'minutes' => 120],
            ['type' => 'cash_out', 'amount' => rand(100, 300), 'reason' => 'Petty cash for supplies', 'minutes' => 150],
            ['type' => 'safe_drop', 'amount' => rand(500, 1500), 'reason' => 'Security deposit', 'minutes' => 180],
            ['type' => 'cash_sale', 'amount' => rand(250, 700), 'reason' => 'Order payment - Table 12', 'minutes' => 210],
            ['type' => 'cash_in', 'amount' => rand(300, 600), 'reason' => 'Cash received for refund', 'minutes' => 240],
            ['type' => 'cash_out', 'amount' => rand(150, 250), 'reason' => 'Expense payment', 'minutes' => 270],
            ['type' => 'cash_sale', 'amount' => rand(180, 450), 'reason' => 'Walk-in customer payment', 'minutes' => 300],
            ['type' => 'safe_drop', 'amount' => rand(1000, 2000), 'reason' => 'Excess cash deposit', 'minutes' => 330],
            ['type' => 'cash_sale', 'amount' => rand(120, 350), 'reason' => 'Order payment - Table 3', 'minutes' => 360],
        ];

        foreach ($transactions as $transaction) {
            $happenedAt = Carbon::parse($session->opened_at)->addMinutes($transaction['minutes']);

            CashRegisterTransaction::create([
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $session->restaurant_id,
                'branch_id' => $session->branch_id,
                'type' => $transaction['type'],
                'amount' => $transaction['amount'],
                'reason' => $transaction['reason'],
                'happened_at' => $happenedAt,
                'created_by' => $user->id,
            ]);
        }
    }

    private function seedCashRegisterTransactions(): void
    {
        $sessions = CashRegisterSession::with('register')->get();

        foreach ($sessions as $session) {
            // Skip if transactions already exist for this session
            $existingTransactions = CashRegisterTransaction::where('cash_register_session_id', $session->id)->count();
            if ($existingTransactions > 0) {
                continue; // Skip this session if transactions already exist
            }

            // Create opening float transaction
            CashRegisterTransaction::create([
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $session->restaurant_id,
                'branch_id' => $session->branch_id,
                'type' => 'opening_float',
                'amount' => $session->opening_float,
                'reason' => 'Opening float',
                'happened_at' => $session->opened_at,
                'created_by' => $session->opened_by,
            ]);

            // Create random cash transactions
            $transactionTypes = ['cash_sale', 'cash_in', 'cash_out', 'safe_drop'];
            $transactionCount = rand(5, 20);

            for ($i = 0; $i < $transactionCount; $i++) {
                $type = $transactionTypes[array_rand($transactionTypes)];
                $amount = rand(50, 2000);
                $happenedAt = Carbon::parse($session->opened_at)->addMinutes(rand(10, 480));

                $reasons = [
                    'cash_sale' => ['Order payment', 'Walk-in customer', 'Takeaway order'],
                    'cash_in' => ['Additional float', 'Cash received', 'Refund processed'],
                    'cash_out' => ['Petty cash', 'Refund given', 'Expense payment'],
                    'safe_drop' => ['End of day deposit', 'Security deposit', 'Excess cash']
                ];

                CashRegisterTransaction::create([
                    'cash_register_session_id' => $session->id,
                    'restaurant_id' => $session->restaurant_id,
                    'branch_id' => $session->branch_id,
                    'type' => $type,
                    'amount' => $amount,
                    'reason' => $reasons[$type][array_rand($reasons[$type])],
                    'happened_at' => $happenedAt,
                    'created_by' => $session->opened_by,
                ]);
            }

            // Create closing transaction if session is closed
            if ($session->status === 'closed') {
                CashRegisterTransaction::create([
                    'cash_register_session_id' => $session->id,
                    'restaurant_id' => $session->restaurant_id,
                    'branch_id' => $session->branch_id,
                    'type' => 'closing',
                    'amount' => $session->counted_cash,
                    'reason' => 'End of day closing',
                    'happened_at' => $session->closed_at,
                    'created_by' => $session->closed_by,
                ]);
            }
        }
    }

    private function closeExtraOpenSessions(): void
    {
        // Get all users who have open sessions
        $openSessions = CashRegisterSession::where('status', 'open')->get();
        
        // Group by user_id
        $sessionsByUser = $openSessions->groupBy('opened_by');
        
        foreach ($sessionsByUser as $userId => $userSessions) {
            if ($userSessions->count() <= 1) {
                continue; // Skip if user only has one open session
            }
            
            // Sort by created_at desc and keep the most recent session
            $sortedSessions = $userSessions->sortByDesc('created_at');
            $keepSession = $sortedSessions->first();
            $otherSessions = $sortedSessions->slice(1);
            
            // Close all other sessions
            foreach ($otherSessions as $session) {
                $session->update([
                    'status' => 'closed',
                    'closed_by' => $userId,
                    'closed_at' => now(),
                    'counted_cash' => $session->expected_cash,
                    'discrepancy' => 0,
                    'closing_note' => 'Closed automatically by seeder',
                ]);
                
                // Delete transactions for closed sessions to avoid duplicates
                CashRegisterTransaction::where('cash_register_session_id', $session->id)->delete();
            }
        }
    }

    private function seedAdminDemoData(): void
    {
        // Get first restaurant and branch for admin demo
        $restaurant = \App\Models\Restaurant::first();
        $branch = Branch::where('restaurant_id', $restaurant->id)->first();

        if (!$restaurant || !$branch) {
            return; // Skip if no restaurant/branch available
        }

        // Get or create admin user for demo
        $adminUser = $this->getOrCreateAdminUser();

        if (!$adminUser) {
            return; // Skip if no admin user available
        }

        // Create or get admin demo cash register
        $adminRegister = CashRegister::firstOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branch->id,
                'name' => 'Admin Demo Register'
            ],
            [
                'is_active' => true,
            ]
        );

        // Create an open session for admin demo - use the admin user's ID
        $openSession = CashRegisterSession::firstOrCreate(
            [
                'cash_register_id' => $adminRegister->id,
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branch->id,
                'status' => 'open',
                'opened_by' => $adminUser->id,
            ],
            [
                'opened_at' => Carbon::now()->subHours(2), // Opened 2 hours ago
                'opening_float' => 5000.00, // Starting with 5000
                'expected_cash' => 8000.00, // Expected to have 8000 by end of day
            ]
        );

        // Create opening float transaction
        CashRegisterTransaction::firstOrCreate(
            [
                'cash_register_session_id' => $openSession->id,
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branch->id,
                'type' => 'opening_float',
                'amount' => $openSession->opening_float,
            ],
            [
                'reason' => 'Admin demo opening float',
                'happened_at' => $openSession->opened_at,
                'created_by' => $adminUser->id,
            ]
        );

        // Create some demo transactions for the open session
        $demoTransactions = [
            ['type' => 'cash_sale', 'amount' => 1250.00, 'reason' => 'Order #1001 - Table 5'],
            ['type' => 'cash_sale', 'amount' => 850.00, 'reason' => 'Order #1002 - Takeaway'],
            ['type' => 'cash_sale', 'amount' => 2100.00, 'reason' => 'Order #1003 - Table 12'],
            ['type' => 'cash_in', 'amount' => 500.00, 'reason' => 'Additional float added'],
            ['type' => 'cash_out', 'amount' => 200.00, 'reason' => 'Petty cash for supplies'],
            ['type' => 'cash_sale', 'amount' => 1750.00, 'reason' => 'Order #1004 - Table 8'],
            ['type' => 'safe_drop', 'amount' => 1000.00, 'reason' => 'Security deposit'],
        ];

        foreach ($demoTransactions as $index => $transaction) {
            $happenedAt = Carbon::parse($openSession->opened_at)->addMinutes(($index + 1) * 15);

            CashRegisterTransaction::firstOrCreate(
                [
                    'cash_register_session_id' => $openSession->id,
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $branch->id,
                    'type' => $transaction['type'],
                    'amount' => $transaction['amount'],
                    'reason' => $transaction['reason'],
                ],
                [
                    'happened_at' => $happenedAt,
                    'created_by' => $adminUser->id,
                ]
            );
        }

        // Create some cash denominations for the open session
        $this->seedCashDenominationsForSession($openSession, $restaurant->id);

        // Also create open sessions for other admin users to ensure coverage
        $this->createOpenSessionsForAllAdmins($restaurant, $branch, $adminRegister);

        // Create sessions with different approval statuses for testing
        $this->createApprovalTestSessions($restaurant, $branch, $adminRegister, $adminUser);
    }

    private function createOpenSessionsForAllAdmins($restaurant, $branch, $adminRegister): void
    {
        // Get all admin users
        $adminUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'like', '%Admin%');
        })->where('restaurant_id', $restaurant->id)->get();

        // If no admin users found, try to find any users with admin-like roles
        if ($adminUsers->isEmpty()) {
            $adminUsers = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'Admin', 'Super Admin']);
            })->where('restaurant_id', $restaurant->id)->get();
        }

        // Create open sessions for each admin user (but not duplicate the one we already created)
        foreach ($adminUsers as $adminUser) {
            // Skip if this user already has an open session
            $existingSession = CashRegisterSession::where('opened_by', $adminUser->id)
                ->where('restaurant_id', $restaurant->id)
                ->where('branch_id', $branch->id)
                ->where('status', 'open')
                ->first();

            if (!$existingSession) {
                CashRegisterSession::create([
                    'cash_register_id' => $adminRegister->id,
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $branch->id,
                    'opened_by' => $adminUser->id,
                    'opened_at' => Carbon::now()->subHours(rand(1, 4)), // Random time within last 4 hours
                    'status' => 'open',
                    'opening_float' => rand(2000, 8000), // Random opening float
                    'expected_cash' => rand(5000, 12000), // Random expected cash
                ]);
            }
        }
    }

    private function createApprovalTestSessions($restaurant, $branch, $adminRegister, $adminUser): void
    {
        // Create a session pending approval
        $pendingSession = CashRegisterSession::create([
            'cash_register_id' => $adminRegister->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'opened_by' => $adminUser->id,
            'closed_by' => $adminUser->id,
            'opened_at' => Carbon::now()->subDays(1)->setHour(9)->setMinute(0),
            'closed_at' => Carbon::now()->subDays(1)->setHour(18)->setMinute(0),
            'status' => 'pending_approval',
            'opening_float' => 3000.00,
            'expected_cash' => 7500.00,
            'counted_cash' => 7480.00,
            'discrepancy' => -20.00, // Short by ₹20
            'closing_note' => 'End of day closing - minor discrepancy noted',
        ]);

        // Create transactions for pending session
        $this->createSessionTransactions($pendingSession, $adminUser);

        // Create an approved session for Z-report testing
        $approvedSession = CashRegisterSession::create([
            'cash_register_id' => $adminRegister->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'opened_by' => $adminUser->id,
            'closed_by' => $adminUser->id,
            'opened_at' => Carbon::now()->subDays(2)->setHour(8)->setMinute(30),
            'closed_at' => Carbon::now()->subDays(2)->setHour(17)->setMinute(45),
            'status' => 'approved',
            'opening_float' => 4000.00,
            'expected_cash' => 9200.00,
            'counted_cash' => 9200.00,
            'discrepancy' => 0.00, // Perfect count
            'closing_note' => 'Perfect count - no discrepancies',
        ]);

        // Create transactions for approved session
        $this->createSessionTransactions($approvedSession, $adminUser);

        // Create cash register counts for both sessions
        $this->seedCashDenominationsForSession($pendingSession, $restaurant->id);
        $this->seedCashDenominationsForSession($approvedSession, $restaurant->id);

        // Create approval record for the approved session
        CashRegisterApproval::create([
            'cash_register_session_id' => $approvedSession->id,
            'approved_by' => $adminUser->id,
            'approved_at' => Carbon::now()->subDays(2)->setHour(18)->setMinute(0),
            'manager_note' => 'Session approved - perfect count with no discrepancies',
        ]);
    }

    private function createSessionTransactions($session, $user): void
    {
        // Create opening float transaction
        CashRegisterTransaction::create([
            'cash_register_session_id' => $session->id,
            'restaurant_id' => $session->restaurant_id,
            'branch_id' => $session->branch_id,
            'type' => 'opening_float',
            'amount' => $session->opening_float,
            'reason' => 'Opening float',
            'happened_at' => $session->opened_at,
            'created_by' => $user->id,
        ]);

        // Create various transactions throughout the day
        $transactions = [
            ['type' => 'cash_sale', 'amount' => 1200.00, 'reason' => 'Order #2001 - Table 3', 'minutes' => 30],
            ['type' => 'cash_sale', 'amount' => 850.00, 'reason' => 'Order #2002 - Takeaway', 'minutes' => 60],
            ['type' => 'cash_in', 'amount' => 500.00, 'reason' => 'Additional float', 'minutes' => 90],
            ['type' => 'cash_sale', 'amount' => 1800.00, 'reason' => 'Order #2003 - Table 7', 'minutes' => 120],
            ['type' => 'cash_out', 'amount' => 300.00, 'reason' => 'Petty cash for supplies', 'minutes' => 150],
            ['type' => 'cash_sale', 'amount' => 2200.00, 'reason' => 'Order #2004 - Table 12', 'minutes' => 180],
            ['type' => 'safe_drop', 'amount' => 1000.00, 'reason' => 'Security deposit', 'minutes' => 210],
            ['type' => 'cash_sale', 'amount' => 950.00, 'reason' => 'Order #2005 - Table 5', 'minutes' => 240],
            ['type' => 'cash_out', 'amount' => 150.00, 'reason' => 'Refund processed', 'minutes' => 270],
            ['type' => 'cash_sale', 'amount' => 1400.00, 'reason' => 'Order #2006 - Table 9', 'minutes' => 300],
        ];

        foreach ($transactions as $transaction) {
            $happenedAt = Carbon::parse($session->opened_at)->addMinutes($transaction['minutes']);

            CashRegisterTransaction::create([
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $session->restaurant_id,
                'branch_id' => $session->branch_id,
                'type' => $transaction['type'],
                'amount' => $transaction['amount'],
                'reason' => $transaction['reason'],
                'happened_at' => $happenedAt,
                'created_by' => $user->id,
            ]);
        }

        // Create closing transaction
        CashRegisterTransaction::create([
            'cash_register_session_id' => $session->id,
            'restaurant_id' => $session->restaurant_id,
            'branch_id' => $session->branch_id,
            'type' => 'closing',
            'amount' => $session->counted_cash,
            'reason' => 'End of day closing',
            'happened_at' => $session->closed_at,
            'created_by' => $user->id,
        ]);
    }

    private function getOrCreateAdminUser(): ?User
    {
        // First try to find an existing admin user
        $adminUser = User::whereHas('roles', function($q) {
            $q->where('name', 'like', '%Admin%');
        })->first();

        if ($adminUser) {
            return $adminUser;
        }

        // If no admin user found, try to find any user with admin-like role
        $adminUser = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'Admin', 'Super Admin']);
        })->first();

        if ($adminUser) {
            return $adminUser;
        }

        // If still no admin user, create a demo admin user
        $restaurant = \App\Models\Restaurant::first();
        if (!$restaurant) {
            return null;
        }

        $adminUser = User::create([
            'name' => 'Demo Admin',
            'email' => 'demo-admin@example.com',
            'password' => bcrypt('123456'),
            'restaurant_id' => $restaurant->id,
        ]);

        // Try to assign admin role
        $adminRole = \App\Models\Role::where('name', 'like', '%Admin%')->first();
        if ($adminRole) {
            $adminUser->assignRole($adminRole);
        }

        return $adminUser;
    }

    private function seedCashDenominationsForSession(CashRegisterSession $session, int $restaurantId): void
    {
        // Get cash denominations for this restaurant
        $cashDenominations = \Modules\CashRegister\Entities\CashDenomination::where('restaurant_id', $restaurantId)->get();

        foreach ($cashDenominations as $cashDenomination) {
            // Create random counts for each denomination
            $count = rand(0, 20);

            if ($count > 0) {
                \Modules\CashRegister\Entities\CashRegisterCount::firstOrCreate(
                    [
                        'cash_register_session_id' => $session->id,
                        'cash_denomination_id' => $cashDenomination->id,
                    ],
                    [
                        'count' => $count,
                        'subtotal' => $cashDenomination->value * $count,
                    ]
                );
            }
        }
    }

    /**
     * Static method to ensure current user has an open register session
     * This can be called from anywhere in the application
     */
    public static function ensureCurrentUserHasOpenRegister(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return false;
        }

        $restaurant = \App\Models\Restaurant::first();
        $branch = \App\Models\Branch::first();

        if (!$restaurant || !$branch) {
            return false;
        }

        // Check if user already has an open session
        $existingSession = CashRegisterSession::where('opened_by', $user->id)
            ->where('restaurant_id', $restaurant->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return true; // Already has open session
        }

        // Get or create register
        $register = CashRegister::firstOrCreate([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'name' => 'Admin Demo Register'
        ], [
            'is_active' => true,
        ]);

        // Create open session for current user
        CashRegisterSession::create([
            'cash_register_id' => $register->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'opened_by' => $user->id,
            'opened_at' => now(),
            'status' => 'open',
            'opening_float' => 5000.00,
            'expected_cash' => 8000.00,
        ]);

        return true;
    }
}
