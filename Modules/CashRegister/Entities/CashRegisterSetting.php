<?php

namespace Modules\CashRegister\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashRegisterSetting extends BaseModel
{
    use HasFactory;

    protected $table = 'cash_register_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'restaurant_id',
        'force_open_after_login',
        'force_open_roles',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'force_open_after_login' => 'boolean',
        'force_open_roles' => 'array',
    ];

    /**
     * Get the restaurant that owns the setting.
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
