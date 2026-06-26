<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EpayPayment extends Model
{
    use HasFactory;

    protected $table = 'epay_payments';

    protected $fillable = [
        'epay_payment_id',
        'order_id',
        'amount',
        'payment_status',
        'payment_date',
        'payment_error_response',
        'epay_invoice_id',
        'epay_secret_hash',
        'epay_access_token',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'payment_error_response' => 'array',
    ];

    /**
     * Relationship: Each payment belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
