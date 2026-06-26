<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\BaseModel;
use App\Traits\HasContextualPricing;
use App\Models\DeliveryPlatform;

class MenuItemVariation extends BaseModel
{
    use HasFactory, HasContextualPricing;

    protected $guarded = ['id'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ItemModifier::class, 'menu_item_variation_id');
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'item_modifiers', 'menu_item_variation_id', 'modifier_group_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MenuItemPrices::class, 'menu_item_variation_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Entities\Recipe::class, 'menu_item_variation_id');
    }

    public function batchRecipe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Entities\BatchRecipe::class);
    }

    /**
     * Implementation of HasContextualPricing trait
     * Resolves contextual price from menu_item_prices table
     *
     * @param int $orderTypeId
     * @param int|null $deliveryAppId
     * @return float
     */
    protected function resolveContextualPrice(int $orderTypeId, ?int $deliveryAppId = null): float
    {
        // Try to use eager-loaded prices first
        if ($this->relationLoaded('prices')) {
            $exact = $this->prices
                ->where('status', true)
                ->where('order_type_id', $orderTypeId)
                ->when($deliveryAppId,
                    fn($collection) => $collection->where('delivery_app_id', $deliveryAppId),
                    fn($collection) => $collection->whereNull('delivery_app_id')
                )
                ->first();

            if ($exact) {
                return (float)$exact->final_price;
            }

            // Relax delivery app if it was provided (use eager-loaded)
            if ($deliveryAppId) {
                $relaxed = $this->prices
                    ->where('status', true)
                    ->where('order_type_id', $orderTypeId)
                    ->whereNull('delivery_app_id')
                    ->first();
                if ($relaxed) {
                    $basePrice = (float)$relaxed->final_price;

                    // Apply delivery platform commission
                    $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
                    if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                        return $deliveryPlatform->getPriceWithCommission($basePrice);
                    }
                    return $basePrice;
                }
            }
        } else {
            // Fallback to query-based approach if not eager-loaded
            $exact = $this->prices()
                ->where('status', true)
                ->where('order_type_id', $orderTypeId)
                ->when($deliveryAppId, fn($q) => $q->where('delivery_app_id', $deliveryAppId), fn($q) => $q->whereNull('delivery_app_id'))
                ->first();

            if ($exact) {
                return (float)$exact->final_price;
            }

            // Relax delivery app if it was provided
            if ($deliveryAppId) {
                $relaxed = $this->prices()
                    ->where('status', true)
                    ->where('order_type_id', $orderTypeId)
                    ->whereNull('delivery_app_id')
                    ->first();
                if ($relaxed) {
                    $basePrice = (float)$relaxed->final_price;

                    // Apply delivery platform commission
                    $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
                    if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                        return $deliveryPlatform->getPriceWithCommission($basePrice);
                    }
                    return $basePrice;
                }
            }
        }

        // Fallback to base variation price from database
        $basePrice = (float)($this->attributes['price'] ?? 0);

        // Apply delivery platform commission if we have a delivery app and no specific pricing
        if ($deliveryAppId && $basePrice > 0) {
            $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
            if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                return $deliveryPlatform->getPriceWithCommission($basePrice);
            }
        }

        return $basePrice;
    }
}
