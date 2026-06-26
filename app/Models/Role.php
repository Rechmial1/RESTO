<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasRestaurant;

    protected $fillable = [
        'name',
        'guard_name',
        'restaurant_id',
        'display_name'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the translated display name for the role
     */
    public function getTranslatedNameAttribute()
    {
        // Try to get translation from modules.staff namespace first
        $translationKey = 'modules.staff.' . $this->display_name;
        $translated = __($translationKey);

        // If translation key returns itself, it means no translation exists
        if ($translated === $translationKey) {
            // Return the display_name as is
            return $this->display_name;
        }

        return $translated;
    }
}
