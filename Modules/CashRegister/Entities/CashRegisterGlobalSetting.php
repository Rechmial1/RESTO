<?php

namespace Modules\CashRegister\Entities;

use App\Models\BaseModel;


class CashRegisterGlobalSetting extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    const MODULE_NAME = 'cashregister';

    protected $table = 'cash_register_global_settings';
}
