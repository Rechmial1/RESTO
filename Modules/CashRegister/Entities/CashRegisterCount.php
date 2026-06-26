<?php

namespace Modules\CashRegister\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterCount extends Model
{
    protected $guarded = [];

    public function denomination(): BelongsTo
    {
        return $this->belongsTo(\Modules\CashRegister\Entities\Denomination::class, 'cash_denomination_id');
    }
}


