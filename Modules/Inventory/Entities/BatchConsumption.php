<?php

namespace Modules\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasBranch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\KotItem;

class BatchConsumption extends Model
{
    use HasBranch;

    protected $fillable = [
        'branch_id',
        'batch_stock_id',
        'order_id',
        'order_item_id',
        'kot_item_id',
        'quantity',
        'cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function batchStock(): BelongsTo
    {
        return $this->belongsTo(BatchStock::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function kotItem(): BelongsTo
    {
        return $this->belongsTo(KotItem::class);
    }
}

