<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;

class OrderType extends Model
{
    use HasBranch;

    protected $guarded = ['id'];


    protected $casts = [
        'enable_token_number' => 'boolean',
        'enable_from_customer_site' => 'boolean',
    ];

    /**
     * Get the translated name for the order type
     */
    public function getTranslatedNameAttribute()
    {
        // Try to get translation based on slug
        if ($this->slug) {
            $translationKey = 'modules.order.' . $this->slug;
            $translated = __($translationKey);

            // If translation exists, use it
            if ($translated !== $translationKey) {
                return $translated;
            }
        }

        // Fallback to order_type_name
        return $this->order_type_name;
    }
}
