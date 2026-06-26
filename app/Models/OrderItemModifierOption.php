<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifierOption extends Pivot
{
    protected $guarded = ['id'];

    protected $table = 'order_item_modifier_options';

    protected static function booted(): void
    {
        static::creating(function (OrderItemModifierOption $pivot) {
            // Skip if data already populated or no modifier reference
            if (!empty($pivot->modifier_option_name) || !$pivot->modifier_option_id) {
                return;
            }

            $modifierOption = ModifierOption::select('id', 'name', 'price')->find($pivot->modifier_option_id);
            
            if (!$modifierOption) {
                return;
            }

            // Get order context for pricing
            $orderItem = OrderItem::select('id', 'order_id')
                ->with(['order:id,order_type_id,delivery_app_id'])
                ->find($pivot->order_item_id);

            // Apply contextual pricing if available
            if ($orderItem?->order?->order_type_id) {
                $modifierOption->setPriceContext(
                    $orderItem->order->order_type_id,
                    $orderItem->order->delivery_app_id
                );
            }

            // Extract localized name
            $name = $modifierOption->name;
            if (is_array($name)) {
                $name = $name[app()->getLocale()] ?? reset($name);
            }

            $pivot->modifier_option_name = $name;
            $pivot->modifier_option_price = $modifierOption->price ?? 0;
        });
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function modifierOption(): BelongsTo
    {
        return $this->belongsTo(ModifierOption::class, 'modifier_option_id');
    }
}
