<?php

namespace Modules\CashRegister\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'opened_by')->withoutGlobalScopes();
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'opened_by')->withoutGlobalScopes();
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'closed_by')->withoutGlobalScopes();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }
    
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}


