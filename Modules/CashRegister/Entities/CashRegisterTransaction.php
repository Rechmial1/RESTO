<?php

namespace Modules\CashRegister\Entities;

use Illuminate\Database\Eloquent\Model;

class CashRegisterTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }
}


