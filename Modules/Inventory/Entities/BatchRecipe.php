<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasBranch;

class BatchRecipe extends Model
{
    use HasBranch;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'yield_unit_id',
        'default_batch_size',
        'default_expiry_days',
    ];

    protected $casts = [
        'default_batch_size' => 'decimal:2',
        'default_expiry_days' => 'integer',
    ];

    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'yield_unit_id');
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(BatchRecipeItem::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(BatchProduction::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BatchStock::class);
    }

    /**
     * Get current total stock across all active batches
     */
    public function getCurrentStockAttribute()
    {
        return $this->stocks()
            ->where('status', 'active')
            ->sum('quantity');
    }

    /**
     * Calculate cost per unit based on recipe items
     */
    public function calculateCostPerUnit()
    {
        $totalCost = 0;
        
        foreach ($this->recipeItems as $item) {
            $inventoryItem = $item->inventoryItem;
            $quantityNeeded = $item->quantity;
            
            // Get current stock cost
            $stock = InventoryStock::where('inventory_item_id', $inventoryItem->id)
                ->where('branch_id', branch()->id)
                ->first();
            
            if ($stock && $inventoryItem->unit_purchase_price) {
                $totalCost += $inventoryItem->unit_purchase_price * $quantityNeeded;
            }
        }
        
        return $totalCost;
    }
}

