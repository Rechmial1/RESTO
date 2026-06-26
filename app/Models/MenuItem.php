<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\OrderItem;
use App\Traits\HasBranch;
use App\Models\ItemCategory;
use App\Models\MenuItemVariation;
use App\Models\MenuItemPrices;
use App\Models\MenuItemTranslation;
use App\Models\DeliveryPlatform;
use Illuminate\Support\Facades\Cache;
use App\Scopes\AvailableMenuItemScope;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\BaseModel;
use App\Traits\HasContextualPricing;

class MenuItem extends BaseModel
{
    use HasFactory, HasBranch, HasTranslations, HasContextualPricing;


    const VEG = 'veg';
    const NONVEG = 'non-veg';
    const EGG = 'egg';

    const FILENAME_TO_EXCLUDE = [
        '.htaccess',
        'butter-chicken.webp',
        'chicken-hyderabadi-biryani.webp',
        'chicken-manchurian.webp',
        'chilli-paneer.webp',
        'dal-makhni.webp',
        'idli-sambar.webp',
        'masala-dosa.webp',
        'medu-vada.webp',
        'naan-recipe.webp',
        'paneer-tikka.webp',
        'spring-rolls.webp',
        'tandoori-roti.webp',
        'uttapam.webp',
        'vegetable-hakka-noodles.webp',
        'vegetable-manchow-soup.webp'
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'show_on_customer_site' => 'boolean',
    ];

    protected $appends = [
        'item_photo_url',
    ];

    protected $with = ['translations'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new AvailableMenuItemScope());
    }

    /**
     * Get contextual price for a specific variation
     * Usage: $menuItem->getVariationPrice($variationId)
     *
     * @param int $variationId
     * @return float
     */
    public function getVariationPrice(int $variationId): float
    {
        if ($this->contextOrderTypeId !== null) {
            return $this->resolvePrice(
                $this->contextOrderTypeId,
                $this->contextDeliveryAppId,
                $variationId
            );
        }

        // Fallback to base variation price
        $variation = $this->variations()->find($variationId);
        return $variation ? (float)$variation->price : (float)($this->attributes['price'] ?? 0);
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
        return $this->resolvePrice($orderTypeId, $deliveryAppId, null);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MenuItemTranslation::class, 'menu_item_id');
    }

    public function translation($locale = null): HasOne
    {
        return $this->hasOne(MenuItemTranslation::class)->where('locale', $locale ?? app()->getLocale());
    }

    public function getTranslatedValue(string $attribute, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = "menu_item_{$this->id}_{$attribute}_{$locale}";

        return Cache::remember($cacheKey, 3600, function () use ($locale, $attribute) {
            $translation = $this->translation($locale)->first();
            return $translation?->{$attribute} ?? $this->attributes[$attribute] ?? '';
        });
    }

    public function getItemNameAttribute(): string
    {
        return $this->getTranslatedValue('item_name');
    }

    public function getDescriptionAttribute(): string
    {
        return $this->getTranslatedValue('description');
    }

    public function itemPhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (in_array($this->image, MenuItem::FILENAME_TO_EXCLUDE)) {
                return asset_url('item/' . $this->image);
            }
            return $this->image ? asset_url_local_s3('item/' . $this->image) : asset('img/food.svg');
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(MenuItemVariation::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MenuItemPrices::class , 'menu_item_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Entities\Recipe::class);
    }

    public function batchRecipe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Entities\BatchRecipe::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(ItemModifier::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'item_modifiers', 'menu_item_id', 'modifier_group_id');
    }

    public function kotPlace()
    {
        return $this->belongsTo(KotPlace::class, 'kot_places_id');
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'menu_item_tax', 'menu_item_id', 'tax_id');
    }

    public function getTaxBreakdown($price, $selectedTaxIds = [], $isInclusive = null)
    {
        if (restaurant()->tax_mode !== 'item' || !$price) {
            return null;
        }

        if (empty($selectedTaxIds)) {
            return null;
        }

        $taxes = Tax::whereIn('id', $selectedTaxIds)->get();
        $taxPercent = $taxes->sum('tax_percent');
        $basePrice = floatval($price);

        // Use passed inclusive setting if provided, otherwise use restaurant setting
        // $inclusive = $isInclusive !== null ? $isInclusive : restaurant()->tax_inclusive;
        $inclusive = $isInclusive ?? restaurant()->tax_inclusive;

        $taxBreakdown = [];
        if ($inclusive) {
            $base = $basePrice / (1 + $taxPercent / 100);
            $totalTax = $basePrice - $base;

            // Calculate individual tax amounts
            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[] = [
                    'id' => $tax->id,
                    'name' => $tax->tax_name,
                    'rate' => $tax->tax_percent,
                    'amount' => round($amount, 2)
                ];
            }
        } else {
            $base = $basePrice;
            $totalTax = 0;

            // Calculate individual tax amounts
            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[] = [
                    'id' => $tax->id,
                    'name' => $tax->tax_name,
                    'rate' => $tax->tax_percent,
                    'amount' => round($amount, 2)
                ];
                $totalTax += $amount;
            }
        }

        $finalTotal = $base + $totalTax;

        return [
            'base' => currency_format($base, restaurant()->currency_id),
            'base_raw' => $base,
            'base_price' => number_format($base, 2),
            'tax' => currency_format($totalTax, restaurant()->currency_id),
            'tax_raw' => $totalTax,
            'total' => currency_format($finalTotal, restaurant()->currency_id),
            'total_raw' => $finalTotal,
            'final_price' => number_format($finalTotal, 2),
            'tax_percent' => $taxPercent,
            'inclusive' => $inclusive,
            'tax_breakdown' => $taxBreakdown
        ];
    }

    public static function calculateItemTaxes($itemPrice, $taxes = [], $inclusive)
    {
        // Ensure $taxes is a collection
        if (is_array($taxes)) {
            $taxes = collect($taxes);
        }

        $taxPercent = $taxes->sum('tax_percent');
        $basePrice = floatval($itemPrice);

        $taxBreakdown = [];
        $totalTax = 0;

        if ($inclusive) {
            $base = $basePrice / (1 + $taxPercent / 100);
            $totalTax = $basePrice - $base;

            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[$tax->tax_name] = [
                    'percent' => $tax->tax_percent,
                    'amount' => round($amount, 2)
                ];
                // No need to add to totalTax here as it's already calculated above
            }
        } else {
            $base = $basePrice;
            $totalTax = 0;

            foreach ($taxes as $tax) {
                $amount = $base * ($tax->tax_percent / 100);
                $taxBreakdown[$tax->tax_name] = [
                    'percent' => $tax->tax_percent,
                    'amount' => round($amount, 2)
                ];
                $totalTax += $amount;
            }
        }

        return [
            'base' => $base,
            'tax_amount' => $totalTax,
            'tax_percentage' => $taxPercent,
            'total_amount' => $base + $totalTax,
            'inclusive' => $inclusive,
            'tax_breakdown' => $taxBreakdown
        ];
    }

    /**
     * Get price for specific order type and delivery platform
     */
    public function getPriceForContext($orderTypeId, $deliveryAppId = null, $variationId = null)
    {
        $price = $this->prices()
            ->where('order_type_id', $orderTypeId)
            ->where('delivery_app_id', $deliveryAppId)
            ->where('menu_item_variation_id', $variationId)
            ->first();

        if ($price) {
            return $price->final_price;
        }

        // Fallback to base price
        if ($variationId) {
            $variation = $this->variations()->find($variationId);
            return $variation ? $variation->price : $this->price;
        }

        return $this->price;
    }

    /**
     * Get all pricing data for this menu item
     */
    public function getAllPricing()
    {
        return $this->prices()->with(['orderType', 'deliveryApp', 'menuItemVariation'])->get();
    }

    /**
     * Resolve price for this item considering order type, delivery app and optional variation.
     * Falls back in order:
     * - exact match (order_type + app + variation)
     * - order_type + variation (no app)
     * - order_type + app (no variation)
     * - order_type only
     * - variation base price
     * - item base price
     */
    public function resolvePrice(?int $orderTypeId, ?int $deliveryAppId = null, ?int $variationId = null)
    {
        $cacheKey = $this->priceCacheKey($orderTypeId, $deliveryAppId, $variationId);

        return Cache::remember(
            $cacheKey,
            now()->addHours(2),
            function () use ($orderTypeId, $deliveryAppId, $variationId) {
                // Try to use eager-loaded prices first (avoid query)
                if ($this->relationLoaded('prices')) {
                    $priceRow = $this->prices
                        ->where('status', true)
                        ->when($orderTypeId, fn($collection) => $collection->where('order_type_id', $orderTypeId))
                        ->when($variationId,
                            fn($collection) => $collection->where('menu_item_variation_id', $variationId),
                            fn($collection) => $collection->whereNull('menu_item_variation_id')
                        )
                        ->when($deliveryAppId,
                            fn($collection) => $collection->where('delivery_app_id', $deliveryAppId),
                            fn($collection) => $collection->whereNull('delivery_app_id')
                        )
                        ->first();

                    if ($priceRow) {
                        return (float)$priceRow->final_price;
                    }

                    // Relax delivery app (use eager-loaded)
                    if ($deliveryAppId) {
                        $relaxed = $this->prices
                            ->where('status', true)
                            ->when($orderTypeId, fn($collection) => $collection->where('order_type_id', $orderTypeId))
                            ->when($variationId,
                                fn($collection) => $collection->where('menu_item_variation_id', $variationId),
                                fn($collection) => $collection->whereNull('menu_item_variation_id')
                            )
                            ->whereNull('delivery_app_id')
                            ->first();

                        if ($relaxed) {
                            $basePrice = (float)$relaxed->final_price;
                            // Apply delivery platform commission
                            if ($this->relationLoaded('deliveryPlatform')) {
                                $deliveryPlatform = $this->deliveryPlatform;
                            } else {
                                $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
                            }
                            if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                                return $deliveryPlatform->getPriceWithCommission($basePrice);
                            }
                            return $basePrice;
                        }
                    }

                    // Relax variation (use eager-loaded)
                    if ($variationId) {
                        $relaxedVar = $this->prices
                            ->where('status', true)
                            ->when($orderTypeId, fn($collection) => $collection->where('order_type_id', $orderTypeId))
                            ->whereNull('menu_item_variation_id')
                            ->when($deliveryAppId,
                                fn($collection) => $collection->where('delivery_app_id', $deliveryAppId),
                                fn($collection) => $collection->whereNull('delivery_app_id')
                            )
                            ->first();

                        if ($relaxedVar) {
                            return (float)$relaxedVar->final_price;
                        }
                    }

                    // Only order type (use eager-loaded)
                    if ($orderTypeId) {
                        $orderTypeOnly = $this->prices
                            ->where('status', true)
                            ->where('order_type_id', $orderTypeId)
                            ->whereNull('menu_item_variation_id')
                            ->whereNull('delivery_app_id')
                            ->first();

                        if ($orderTypeOnly) {
                            return (float)$orderTypeOnly->final_price;
                        }
                    }
                } else {
                    // Try exact match
                    $query = $this->prices()->where('status', true);

                    if ($orderTypeId) {
                        $query = $query->where('order_type_id', $orderTypeId);
                    }

                    if ($variationId) {
                        $query = $query->where('menu_item_variation_id', $variationId);
                    } else {
                        $query = $query->whereNull('menu_item_variation_id');
                    }

                    if ($deliveryAppId) {
                        $query = $query->where('delivery_app_id', $deliveryAppId);
                    } else {
                        $query = $query->whereNull('delivery_app_id');
                    }

                    $priceRow = $query->first();

                    if ($priceRow) {
                        return (float)$priceRow->final_price;
                    }

                    // Relax delivery app
                    if ($deliveryAppId) {
                        $relaxed = $this->prices()
                            ->where('status', true)
                            ->when($orderTypeId, fn($q) => $q->where('order_type_id', $orderTypeId))
                            ->when($variationId, fn($q) => $q->where('menu_item_variation_id', $variationId), fn($q) => $q->whereNull('menu_item_variation_id'))
                            ->whereNull('delivery_app_id')
                            ->first();
                        if ($relaxed) {
                            $basePrice = (float)$relaxed->final_price;
                            // Apply delivery platform commission to the base price
                            $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
                            if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                                return $deliveryPlatform->getPriceWithCommission($basePrice);
                            }
                            return $basePrice;
                        }
                    }

                    // Relax variation
                    if ($variationId) {
                        $relaxedVar = $this->prices()
                            ->where('status', true)
                            ->when($orderTypeId, fn($q) => $q->where('order_type_id', $orderTypeId))
                            ->whereNull('menu_item_variation_id')
                            ->when($deliveryAppId, fn($q) => $q->where('delivery_app_id', $deliveryAppId), fn($q) => $q->whereNull('delivery_app_id'))
                            ->first();
                        if ($relaxedVar) {
                            return (float)$relaxedVar->final_price;
                        }
                    }

                    // Only order type
                    if ($orderTypeId) {
                        $orderTypeOnly = $this->prices()
                            ->where('status', true)
                            ->where('order_type_id', $orderTypeId)
                            ->whereNull('menu_item_variation_id')
                            ->whereNull('delivery_app_id')
                            ->first();
                        if ($orderTypeOnly) {
                            return (float)$orderTypeOnly->final_price;
                        }
                    }
                }

                // Variation base price
                $basePrice = null;
                if ($variationId) {
                    // Try eager-loaded variations first
                    if ($this->relationLoaded('variations')) {
                        $variation = $this->variations->find($variationId);
                        if ($variation && isset($variation->attributes['price'])) {
                            $basePrice = (float)$variation->attributes['price'];
                        }
                    } else {
                        $variation = $this->variations()->find($variationId);
                        if ($variation && isset($variation->attributes['price'])) {
                            $basePrice = (float)$variation->attributes['price'];
                        }
                    }
                }

                // Fallback to item base price from database (not accessor)
                if ($basePrice === null) {
                    $basePrice = (float)($this->attributes['price'] ?? 0);
                }

                // Apply delivery platform commission if we have a delivery app and no specific pricing
                if ($deliveryAppId && $basePrice > 0) {
                    $deliveryPlatform = DeliveryPlatform::find($deliveryAppId);
                    if ($deliveryPlatform && $deliveryPlatform->commission_value > 0) {
                        return $deliveryPlatform->getPriceWithCommission($basePrice);
                    }
                }

                return $basePrice;
            }
        );
    }

    protected function priceCacheKey(?int $orderTypeId, ?int $deliveryAppId, ?int $variationId): string
    {
        $orderTypePart = $orderTypeId ?? 'none';
        $deliveryAppPart = $deliveryAppId ?? 'none';
        $variationPart = $variationId ?? 'none';
        $updatedAt = $this->updated_at?->timestamp ?? '0';

        return "menu_item_price:{$this->id}:{$orderTypePart}:{$deliveryAppPart}:{$variationPart}:{$updatedAt}";
    }
}
