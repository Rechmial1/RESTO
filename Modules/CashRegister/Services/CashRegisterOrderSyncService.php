<?php

namespace Modules\CashRegister\Services;

use App\Models\Order;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;

class CashRegisterOrderSyncService
{
    /**
     * Recalculate and upsert the cash sale transaction for an order
     */
    public static function syncCashForOrder(Order $order): void
    {
        // Only track paid orders
        $status = $order->status ?? $order->payment_status ?? null;
        $isPaid = ($status === 'paid');

        // Sum current cash payments for this order
        $order->loadMissing(['payments']);
        $cashAmount = 0.0;
        if (method_exists($order, 'payments')) {
            foreach ($order->payments as $payment) {
                if (($payment->payment_method ?? null) === 'cash') {
                    $cashAmount += (float) ($payment->amount ?? 0);
                }
            }
        }

        $userId = $order->created_by ?? \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return;
        }

        $session = CashRegisterSession::where('opened_by', $userId)
            ->where('status', 'open')
            ->where('branch_id', $order->branch_id ?? branch()->id ?? 0)
            ->latest('opened_at')
            ->first();

        // If not paid or no open session, remove any existing and stop
        if (!$isPaid || !$session) {
            $existing = CashRegisterTransaction::where('order_id', $order->id)->first();
            if ($existing) {
                $existing->delete();
            }
            return;
        }

        $existing = CashRegisterTransaction::where('order_id', $order->id)->first();

        // If no cash amount, remove existing transaction if any
        if ($cashAmount <= 0) {
            if ($existing) {
                $existing->delete();
            }
            return;
        }

        if ($existing) {
            $existing->update([
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $session->restaurant_id,
                'branch_id' => $session->branch_id,
                'happened_at' => now(),
                'type' => 'cash_sale',
                'reference' => (string) ($order->uuid ?? $order->id),
                'reason' => 'POS cash sale',
                'amount' => $cashAmount,
                'created_by' => $userId,
            ]);
            return;
        }

        CashRegisterTransaction::create([
            'cash_register_session_id' => $session->id,
            'restaurant_id' => $session->restaurant_id,
            'branch_id' => $session->branch_id,
            'happened_at' => now(),
            'type' => 'cash_sale',
            'reference' => (string) ($order->uuid ?? $order->id),
            'reason' => 'POS cash sale',
            'amount' => $cashAmount,
            'running_amount' => 0,
            'order_id' => $order->id,
            'created_by' => $userId,
        ]);
    }

    public static function syncPaidCashOrder(Order $order): void
    {
        // Accept both "status" and "payment_status" == paid semantics
        $status = $order->status ?? $order->payment_status ?? null;
        if ($status !== 'paid') {
            return;
        }

        // Determine cash payment from payments relation if available
        $isCash = false;
        if (method_exists($order, 'payments')) {
            foreach ($order->payments as $payment) {
                if (($payment->payment_method ?? null) === 'cash' && (float) ($payment->amount ?? 0) > 0) {
                    $isCash = true;
                    break;
                }
            }
        }

        if (!$isCash) {
            return;
        }

        $userId = $order->created_by ?? \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return;
        }

        $session = CashRegisterSession::where('opened_by', $userId)
            ->where('status', 'open')
            ->where('branch_id', $order->branch_id ?? branch()->id ?? 0)
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return; // no open session for user
        }

        // For paid orders, just delegate to recalculation logic
        self::syncCashForOrder($order);
    }
}


