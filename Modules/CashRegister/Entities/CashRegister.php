<?php

namespace Modules\CashRegister\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $guarded = [];

    public function sessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }
}


