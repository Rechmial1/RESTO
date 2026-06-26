<?php

namespace App\Observers;

use App\Models\Kot;
use App\Models\KotSetting;
use App\Events\KotUpdated;

class KotObserver
{
    public function creating(Kot $kot)
    {
        $kotSettings = KotSetting::first();

        if (branch() && $kot->branch_id == null) {
            $kot->branch_id = branch()->id;
        }

        $orderStatus = $kot->order?->order_status->value;
        $defaultKotStatus = $kotSettings->default_status;

        $kot->status = match(true) {
            $orderStatus === 'placed' && $defaultKotStatus === 'pending' => 'pending_confirmation',
            $defaultKotStatus === 'pending' => 'in_kitchen',
            $defaultKotStatus === 'cooking' => 'in_kitchen',
            default => 'pending_confirmation',
        };

    }

    public function updated(Kot $kot)
    {
        // Only proceed if status changed
        if (!$kot->wasChanged('status')) {
            return;
        }

        // Load order with all non-cancelled KOTs and their items eagerly
        $order = $kot->order()
            ->with(['kot' => fn($query) => $query
                ->where('status', '!=', 'cancelled')
                ->with('items:id,kot_id,status')
            ])
            ->first(['id', 'order_type', 'placed_via', 'order_status']);

        if (!$order || $order->kot->isEmpty()) {
            return;
        }

        $aggregatedOrderStatus = $this->determineOrderStatusFromKots($order->kot, $order);

        // Update order status if it changed (compare with enum value)
        if ($aggregatedOrderStatus && $order->order_status->value !== $aggregatedOrderStatus) {
            $order->updateQuietly(['order_status' => $aggregatedOrderStatus]);
        }
    }

    /**
     * Determine order status based on all KOTs statuses.
     * Professional POS flow: Order status = lowest (earliest) status among all items.
     * Supports both forward and backward transitions.
     */
    private function determineOrderStatusFromKots($kots, $order)
    {
        $orderType = $order->order_type;
        
        $pendingStatus = ($order->placed_via === 'pos')
            ? 'confirmed'
            : (restaurant()->auto_confirm_orders ? 'confirmed' : 'placed');

        $statusPriority = [
            'pending_confirmation' => 1,
            'in_kitchen' => 2,
            'food_ready' => 3,
            'served' => 4,
        ];

        $minPriority = $kots->min(fn($kot) => $statusPriority[$kot->status] ?? 1);
        $lowestKotStatus = array_search($minPriority, $statusPriority);

        // Check if ANY item across ALL KOTs is in cooking status
        $hasAnyCookingItem = $kots->contains(function ($kot) {
            return $kot->items->contains('status', 'cooking');
        });

        $statusMap = [
            'pending_confirmation' => $pendingStatus,
            'in_kitchen' => $hasAnyCookingItem ? 'preparing' : 'confirmed',
            'food_ready' => match($orderType) {
                'pickup' => 'ready_for_pickup',
                default => 'food_ready',
            },
            'served' => match($orderType) {
                'pickup' => 'ready_for_pickup',
                'delivery' => 'food_ready',
                default => 'served',
            },
        ];


        return $statusMap[$lowestKotStatus] ?? null;
    }


    public function saved(Kot $kot)
    {

        event(new KotUpdated($kot));
    }
}