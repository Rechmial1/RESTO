<?php

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Entities\BatchStock;
use Modules\Inventory\Entities\InventoryMovement;
use Carbon\Carbon;

class CheckBatchExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-batch-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired batches and mark them as expired, creating waste movements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired batches...');

        $expiredBatches = BatchStock::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now())
            ->get();

        $count = 0;

        foreach ($expiredBatches as $batchStock) {
            // Get remaining quantity (not consumed)
            $remainingQuantity = $batchStock->remaining_quantity;

            if ($remainingQuantity > 0) {
                // Mark as expired
                $batchStock->update(['status' => 'expired']);

                // Create waste movement for expired batch
                // Note: We create a movement record but don't deduct from inventory
                // since the raw ingredients were already deducted when batch was produced
                InventoryMovement::create([
                    'branch_id' => $batchStock->branch_id,
                    'inventory_item_id' => null, // Batch items don't have inventory_item_id
                    'quantity' => $remainingQuantity,
                    'transaction_type' => 'waste',
                    'waste_reason' => 'expiry',
                    'added_by' => null, // System action
                ]);

                $count++;
            } else {
                // No remaining quantity, just mark as finished
                $batchStock->update(['status' => 'finished']);
            }
        }

        $this->info("Processed {$count} expired batch(es).");

        return Command::SUCCESS;
    }
}

