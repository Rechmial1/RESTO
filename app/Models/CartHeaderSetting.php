<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartHeaderSetting extends BaseModel
{
    use HasFactory, HasRestaurant;

    protected $guarded = ['id'];

    protected $casts = [
        'header_type' => 'string',
        'is_header_disabled' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CartHeaderImage::class)->orderBy('sort_order');
    }

    public function getHeaderTextAttribute($value)
    {
        return $value ?: __('messages.frontHeroHeading');
    }
}
