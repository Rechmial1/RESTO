<?php

namespace Modules\CashRegister\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Denomination extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_value',
        'type_label',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getFormattedValueAttribute(): string
    {
        return number_format((float) $this->value, 2);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'coin' => __('cashregister::app.coin'),
            'note' => __('cashregister::app.note'),
            'bill' => __('cashregister::app.bill'),
            default => __('cashregister::app.unknown'),
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeCoins($query)
    {
        return $query->where('type', 'coin');
    }

    public function scopeNotes($query)
    {
        return $query->where('type', 'note');
    }

    public function scopeBills($query)
    {
        return $query->where('type', 'bill');
    }

    public function scopeOrderedByValue($query, $direction = 'asc')
    {
        return $query->orderBy('value', $direction);
    }

    public static function getCreateRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'value' => 'required|numeric|min:0.01|max:999999.99',
            'type' => 'required|in:coin,note,bill',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500',
        ];
    }

    public static function getUpdateRules(int $id): array
    {
        return [
            'name' => 'required|string|max:255',
            'value' => 'required|numeric|min:0.01|max:999999.99',
            'type' => 'required|in:coin,note,bill',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500',
        ];
    }

    public static function getAvailableTypes(): array
    {
        return [
            'coin' => __('cashregister::app.coin'),
            'note' => __('cashregister::app.note'),
            'bill' => __('cashregister::app.bill'),
        ];
    }
}


