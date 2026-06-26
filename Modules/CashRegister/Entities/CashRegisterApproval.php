<?php

namespace Modules\CashRegister\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterApproval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by')->withoutGlobalScopes();
    }
}
