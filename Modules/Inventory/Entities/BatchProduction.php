<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasBranch;
use App\Models\User;

class BatchProduction extends Model
{
    use HasBranch;

    protected $fillable = [
        'branch_id',
        'batch_recipe_id',
        'quantity',
        'total_cost',
        'produced_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function batchRecipe(): BelongsTo
    {
        return $this->belongsTo(BatchRecipe::class);
    }

    public function producedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by')->withoutGlobalScopes();
    }

    public function stock(): HasOne
    {
        return $this->hasOne(BatchStock::class);
    }
}

