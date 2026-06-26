<?php

namespace Modules\Inventory\Listeners;

use App\Events\NewOrderCreated;
use Modules\Inventory\Entities\Recipe;
use Modules\Inventory\Entities\BatchRecipe;
use Modules\Inventory\Entities\BatchStock;
use Modules\Inventory\Entities\BatchConsumption;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryStock;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use Illuminate\Support\Facades\DB;

class UpdateInventoryOnOrderReceived
{
    public function handle(NewOrderCreated $event): void
    {
        $order = $event->order;

        // Get all order items
        foreach ($order->load('items.modifierOptions')->items as $orderItem) {
            // Check if this order item uses a batch recipe
            $batchRecipe = null;
            $servingSize = null;
            
            // Check variation first
            if ($orderItem->menu_item_variation_id) {
                $variation = MenuItemVariation::find($orderItem->menu_item_variation_id);
                if ($variation && $variation->batch_recipe_id) {
                    $batchRecipe = BatchRecipe::find($variation->batch_recipe_id);
                    $servingSize = $variation->batch_serving_size;
                }
            }
            
            // If no batch recipe at variation level, check menu item level
            if (!$batchRecipe) {
                $menuItem = MenuItem::find($orderItem->menu_item_id);
                if ($menuItem && $menuItem->batch_recipe_id) {
                    $batchRecipe = BatchRecipe::find($menuItem->batch_recipe_id);
                    $servingSize = $menuItem->batch_serving_size;
                }
            }
            
            // If batch recipe exists, deduct from batch stock
            if ($batchRecipe && $servingSize) {
                $this->processBatchRecipe($order, $orderItem, $batchRecipe, $servingSize);
            } else {
                // Otherwise, process regular recipes (raw ingredients)
                $this->processRegularRecipes($order, $orderItem);
            }

            // Process recipes for modifier options (always use regular recipes)
            foreach ($orderItem->modifierOptions as $modifierOption) {
                $modifierRecipes = Recipe::where('modifier_option_id', $modifierOption->id)->get();
                foreach ($modifierRecipes as $recipe) {
                    // Calculate quantity needed based on order quantity
                    $quantityNeeded = $recipe->quantity * $orderItem->quantity;

                    $this->processRecipe($order, $recipe, $quantityNeeded);
                }
            }
        }
    }
    
    private function processRegularRecipes($order, $orderItem): void
    {
        // Get recipe for this menu item or variation
        $recipes = collect();
        
        // If order item has a variation, get recipes for that variation
        if ($orderItem->menu_item_variation_id) {
            $recipes = Recipe::where('menu_item_id', $orderItem->menu_item_id)
                ->where('menu_item_variation_id', $orderItem->menu_item_variation_id)
                ->get();
        }
        
        // If no variation or no variation-specific recipes found, get base menu item recipes
        if ($recipes->isEmpty()) {
            $recipes = Recipe::where('menu_item_id', $orderItem->menu_item_id)
                ->whereNull('menu_item_variation_id')
                ->get();
        }
        
        foreach ($recipes as $recipe) {
            // Calculate quantity needed based on order quantity
            $quantityNeeded = $recipe->quantity * $orderItem->quantity;

            $this->processRecipe($order, $recipe, $quantityNeeded);
        }
    }
    
    private function processBatchRecipe($order, $orderItem, $batchRecipe, $servingSize): void
    {
        try {
            DB::transaction(function () use ($order, $orderItem, $batchRecipe, $servingSize) {
                // Calculate total quantity needed
                $totalQuantityNeeded = $servingSize * $orderItem->quantity;
                
                // Find available batch stocks (FIFO - oldest first, active only)
                $batchStocks = BatchStock::where('batch_recipe_id', $batchRecipe->id)
                    ->where('branch_id', $order->branch_id)
                    ->where('status', 'active')
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                $remainingNeeded = $totalQuantityNeeded;
                
                foreach ($batchStocks as $batchStock) {
                    if ($remainingNeeded <= 0) {
                        break;
                    }

                    // Remaining quantity in this batch before we consume anything in this loop
                    $availableQuantity = $batchStock->remaining_quantity;

                    if ($availableQuantity > 0) {
                        $quantityToConsume = min($remainingNeeded, $availableQuantity);

                        // Create batch consumption record – this is what drives the remaining_quantity accessor
                        BatchConsumption::create([
                            'branch_id'      => $order->branch_id,
                            'batch_stock_id' => $batchStock->id,
                            'order_id'       => $order->id,
                            'order_item_id'  => $orderItem->id,
                            'quantity'       => $quantityToConsume,
                            'cost'           => $batchStock->cost_per_unit * $quantityToConsume,
                        ]);

                        // Reduce how much we still need from batches
                        $remainingNeeded -= $quantityToConsume;

                        // If we've exhausted this batch stock, mark it as finished so it won't be picked again
                        $newRemaining = $availableQuantity - $quantityToConsume;
                        if ($newRemaining <= 0) {
                            $batchStock->update(['status' => 'finished']);
                        }
                    }
                }
                
                // If we couldn't fulfill the entire order, log a warning
                if ($remainingNeeded > 0) {
                    \Log::warning("Insufficient batch stock for order {$order->id}. Needed: {$totalQuantityNeeded}, Available: " . ($totalQuantityNeeded - $remainingNeeded));
                }
            });
        } catch (\Exception $e) {
            \Log::error('Error processing batch recipe for order: ' . $order->id . ' - ' . $e->getMessage());
        }
    }

    private function processRecipe($order, $recipe, $quantityNeeded): void
    {
        try {
            DB::transaction(function () use ($order, $recipe, $quantityNeeded) {
                // Update inventory stock
                $stock = InventoryStock::where('branch_id', $order->branch_id)
                    ->where('inventory_item_id', $recipe->inventory_item_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {

                    // Create inventory movement record for stock out (linked to order)
                    InventoryMovement::create([
                        'branch_id'         => $order->branch_id,
                        'inventory_item_id' => $recipe->inventory_item_id,
                        'order_id'          => $order->id,
                        'transaction_type'  => InventoryMovement::TRANSACTION_TYPE_ORDER_USED,
                        'quantity'          => $quantityNeeded,
                        'added_by'          => auth()->check() ? auth()->id() : null,
                    ]);

                    // Update stock quantity
                    $stock->quantity = $stock->quantity - $quantityNeeded;
                    $stock->save();

                    if ($recipe->inventoryItem->current_stock <= 0) {
                        foreach ($recipe->inventoryItem->menuItems as $menuItem) {
                            $menuItem->update([
                                'in_stock' => 0
                            ]);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::error('Error updating inventory for order: ' . $order->id . ' - ' . $e->getMessage());
        }
    }
} 