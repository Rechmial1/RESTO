<?php

namespace App\Observers;

use App\Models\KotItem;
use App\Events\KotUpdated;

class KotItemObserver
{

    public function updated(KotItem $kotItem)
    {
        // Only proceed if status changed
        if (!$kotItem->isDirty('status')) {
            return;
        }

        $kot = $kotItem->kot;
        
        if (!$kot || !$kot->order) {
            return;
        }

        $order = $kot->order;
        $currentOrderStatus = $order->order_status?->value ?? $order->order_status;
        $higherStatuses = ['food_ready', 'ready_for_pickup', 'out_for_delivery', 'served', 'delivered'];
        
        // Don't interfere if order is in a higher status
        if (in_array($currentOrderStatus, $higherStatuses)) {
            return;
        }

        // Check if ANY item across ALL KOTs is in cooking status
        $hasAnyCookingItem = $order->kot()
            ->where('status', '!=', 'cancelled')
            ->with('items:id,kot_id,status')
            ->get()
            ->flatMap(fn($k) => $k->items)
            ->contains('status', 'cooking');

        // Update order status based on whether any items are cooking
        if ($hasAnyCookingItem && $currentOrderStatus !== 'preparing') {
            // At least one item is cooking, set to preparing
            $order->updateQuietly(['order_status' => 'preparing']);
        } elseif (!$hasAnyCookingItem && $currentOrderStatus === 'preparing') {
            // No items are cooking anymore, revert to previous status
            $pendingStatus = ($order->placed_via === 'pos')
                ? 'confirmed'
                : (restaurant()->auto_confirm_orders ? 'confirmed' : 'placed');
            
            $order->updateQuietly(['order_status' => $pendingStatus]);
        }
    }

    public function saved(KotItem $kotItem)
    {
        event(new KotUpdated($kotItem->kot));
    }

    public function deleted(KotItem $kotItem)
    {
        event(new KotUpdated($kotItem->kot));
    }
}
