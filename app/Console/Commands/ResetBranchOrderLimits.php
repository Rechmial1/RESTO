<?php

namespace App\Console\Commands;

use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetBranchOrderLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-branch-order-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset branch order limits and counts daily. Sets total_orders from restaurant package order_limit and resets count_orders to 0.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily branch order limits reset...');

        // Get all active branches with their restaurant and package
        $branches = Branch::with(['restaurant.package'])
            ->whereHas('restaurant', function ($query) {
                $query->where('status', 'active')
                    ->whereNotNull('package_id');
            })
            ->get();

        $updatedCount = 0;
        $skippedCount = 0;
        $branchIds = [];

        foreach ($branches as $branch) {
            if (!$branch->restaurant || !$branch->restaurant->package) {
                $skippedCount++;
                continue;
            }

            $orderLimit = $branch->restaurant->package->order_limit ?? -1;

            // Update branch order limits using update without triggering observers
            $branch->total_orders = $orderLimit;
            $branch->count_orders = 0;
            $branch->saveQuietly();

            // Collect branch IDs for cache clearing
            $branchIds[] = $branch->id;
            $this->info("Updated branch {$branch->id} with order limit {$orderLimit}. count_orders reset to {$branch->count_orders}.");
            $updatedCount++;
        }

        // Clear caches for all updated branches
        foreach ($branchIds as $branchId) {
            cache()->forget('branch_' . $branchId . '_order_stats');
        }

        $this->info("Successfully updated {$updatedCount} branches.");
        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} branches (missing restaurant or package).");
        }

        $this->info('Daily branch order limits reset completed.');

        return Command::SUCCESS;
    }
}

