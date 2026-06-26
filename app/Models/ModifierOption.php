<?php

namespace App\Models;

use App\Models\BaseModel;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasContextualPricing;

class ModifierOption extends BaseModel
{
    use HasFactory, HasTranslations, HasContextualPricing;

    protected $guarded = ['id'];

    public $translatable = ['name'];

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    public function orderItemModifierOptions(): HasMany
    {
        return $this->hasMany(OrderItemModifierOption::class, 'modifier_option_id');
    }

    public function kotItemModiferOptions(): HasMany
    {
        return $this->hasMany(KotItemModifierOption::class, 'modifier_option_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany('Modules\\Inventory\\Entities\\Recipe', 'modifier_option_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ModifierOptionPrice::class, 'modifier_option_id');
    }

    /**
     * Implementation of HasContextualPricing trait
     * Resolves contextual price from modifier_option_prices table
     * Uses eager-loaded prices when available
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

            // Relax delivery app (use eager-loaded)
            if ($deliveryAppId) {
                $byOrderType = $this->prices
                    ->where('status', true)
                    ->where('order_type_id', $orderTypeId)
                    ->whereNull('delivery_app_id')
                    ->first();
                if ($byOrderType) {
                    return (float)$byOrderType->final_price;
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

            // Relax delivery app
            if ($deliveryAppId) {
                $byOrderType = $this->prices()
                    ->where('status', true)
                    ->where('order_type_id', $orderTypeId)
                    ->whereNull('delivery_app_id')
                    ->first();
                if ($byOrderType) {
                    return (float)$byOrderType->final_price;
                }
            }
        }

        // Fallback to base price
        return (float)($this->attributes['price'] ?? 0);
    }
}
