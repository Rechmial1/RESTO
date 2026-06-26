<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchRecipeItem extends Model
{
    protected $fillable = [
        'batch_recipe_id',
        'inventory_item_id',
        'quantity',
        'unit_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function batchRecipe(): BelongsTo
    {
        return $this->belongsTo(BatchRecipe::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}

