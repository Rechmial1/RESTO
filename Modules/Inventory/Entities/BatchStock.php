<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasBranch;

class BatchStock extends Model
{
    use HasBranch;

    protected $fillable = [
        'branch_id',
        'batch_recipe_id',
        'batch_production_id',
        'quantity',
        'cost_per_unit',
        'total_cost',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function batchRecipe(): BelongsTo
    {
        return $this->belongsTo(BatchRecipe::class);
    }

    public function batchProduction(): BelongsTo
    {
        return $this->belongsTo(BatchProduction::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(BatchConsumption::class);
    }

    /**
     * Get remaining quantity after consumptions
     */
    public function getRemainingQuantityAttribute()
    {
        $consumed = $this->consumptions()->sum('quantity');
        return max(0, $this->quantity - $consumed);
    }

    /**
     * Check if batch is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return now()->isAfter($this->expiry_date) && $this->status === 'active';
    }

    /**
     * Mark batch as expired
     */
    public function markAsExpired()
    {
        if ($this->status === 'active' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
            
            // Create waste movement for expired batch
            // Note: We'll handle this in a listener/observer
        }
    }
}

