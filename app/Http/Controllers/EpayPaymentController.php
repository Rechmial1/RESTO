<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\EpayPayment;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\SendNewOrderReceived;
use App\Events\SendOrderBillEvent;

class EpayPaymentController extends Controller
{
    /**
     * Success redirect after payment
     */
    public function success(Request $request)
    {
        info('Epay Success Callback:', $request->all());

        $paymentId = $request->query('payment_id') ?? $request->query('id') ?? $request->input('payment_id') ?? $request->input('id');
        $referenceId = $request->query('reference_id') ?? $request->input('reference_id');
        $orderId = $request->query('order_id') ?? $request->input('order_id');
        $invoiceId = $request->query('invoiceId') ?? $request->input('invoiceId') ?? session('epay_invoice_id');

        // Try to find payment by invoiceId first (most reliable)
        $epayPayment = null;
        if ($invoiceId) {
            $epayPayment = EpayPayment::where('epay_invoice_id', $invoiceId)->first();
        }
        // Try by payment_id
        if (!$epayPayment && $paymentId) {
            $epayPayment = EpayPayment::where('epay_payment_id', $paymentId)->first();
        }
        // Try by reference_id
        if (!$epayPayment && $referenceId) {
            $epayPayment = EpayPayment::where('id', $referenceId)->first();
        }
        // Try by order_id (get most recent pending payment)
        if (!$epayPayment && $orderId) {
            $epayPayment = EpayPayment::where('order_id', $orderId)
                ->where('payment_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }
        // Fallback: use session payment_id
        if (!$epayPayment && session('epay_payment_id')) {
            $epayPayment = EpayPayment::find(session('epay_payment_id'));
        }
        // Last fallback: use session order_id
        if (!$epayPayment && session('epay_order_id')) {
            $epayPayment = EpayPayment::where('order_id', session('epay_order_id'))
                ->where('payment_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if (!$epayPayment) {
            Log::warning('Epay Success: Payment not found', [
                'request_data' => $request->all(),
                'session_invoice_id' => session('epay_invoice_id'),
                'session_order_id' => session('epay_order_id'),
            ]);
            session()->flash('flash.banner', 'Payment not found. Please check your order status.');
            session()->flash('flash.bannerStyle', 'warning');
            return redirect()->route('home');
        }

        // Check if payment is already completed (avoid duplicate processing)
        $wasAlreadyCompleted = $epayPayment->payment_status === 'completed';

        // Update payment status only if not already completed
        if (!$wasAlreadyCompleted) {
            $epayPayment->payment_status = 'completed';
            $epayPayment->payment_date = now();
            // Store any payment ID from Epay if provided
            if ($paymentId) {
                $epayPayment->epay_payment_id = $paymentId;
            }
            $epayPayment->save();
        }

        $order = Order::find($epayPayment->order_id);
        if (!$order) {
            Log::error('Epay Success: Order not found for payment', ['payment_id' => $epayPayment->id]);
            session()->flash('flash.banner', 'Order not found.');
            session()->flash('flash.bannerStyle', 'danger');
            // Clear session
            session()->forget(['epay_invoice_id', 'epay_order_id', 'epay_payment_id']);
            return redirect()->route('home');
        }

        // Only update order if payment wasn't already completed
        if (!$wasAlreadyCompleted) {
            // Update order payment
            $order->amount_paid = ($order->amount_paid ?? 0) + $epayPayment->amount;
            $order->status = 'paid';
            $order->save();

            // Create or update payment record
            Payment::updateOrCreate(
                [
                    'order_id' => $epayPayment->order_id,
                    'payment_method' => 'epay',
                ],
                [
                    'branch_id' => $order->branch_id,
                    'amount' => $epayPayment->amount,
                    'transaction_id' => $epayPayment->epay_payment_id ?? (string)$epayPayment->id,
                ]
            );

            SendNewOrderReceived::dispatch($order);

            if ($order->customer_id) {
                SendOrderBillEvent::dispatch($order);
            }
        }

        // Clear session after successful processing
        session()->forget(['epay_invoice_id', 'epay_order_id', 'epay_payment_id']);

        return redirect()->route('order_success', $order->uuid)->with([
            'flash.banner' => __('messages.paymentDoneSuccessfully'),
            'flash.bannerStyle' => 'success',
        ]);
    }

    /**
     * Cancel redirect
     */
    public function cancel(Request $request)
    {
        info('Epay Cancel Callback:', $request->all());

        $paymentId = $request->query('payment_id') ?? $request->query('id') ?? $request->input('payment_id') ?? $request->input('id');
        $referenceId = $request->query('reference_id') ?? $request->input('reference_id');
        $orderId = $request->query('order_id') ?? $request->input('order_id');
        $invoiceId = $request->query('invoiceId') ?? $request->input('invoiceId') ?? session('epay_invoice_id');
        $errorCode = $request->query('code') ?? $request->input('code');
        $reasonCode = $request->query('reasonCode') ?? $request->input('reasonCode');
        $reason = $request->query('reason') ?? $request->input('reason');

        // Try to find payment by invoiceId first (most reliable)
        $epayPayment = null;
        if ($invoiceId) {
            $epayPayment = EpayPayment::where('epay_invoice_id', $invoiceId)->first();
        }
        // Try by payment_id
        if (!$epayPayment && $paymentId) {
            $epayPayment = EpayPayment::where('epay_payment_id', $paymentId)->first();
        }
        // Try by reference_id
        if (!$epayPayment && $referenceId) {
            $epayPayment = EpayPayment::where('id', $referenceId)->first();
        }
        // Try by order_id (get most recent pending payment)
        if (!$epayPayment && $orderId) {
            $epayPayment = EpayPayment::where('order_id', $orderId)
                ->where('payment_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }
        // Fallback: use session payment_id
        if (!$epayPayment && session('epay_payment_id')) {
            $epayPayment = EpayPayment::find(session('epay_payment_id'));
        }
        // Last fallback: use session order_id
        if (!$epayPayment && session('epay_order_id')) {
            $epayPayment = EpayPayment::where('order_id', session('epay_order_id'))
                ->where('payment_status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        if ($epayPayment) {
            // Only update status if not already completed (don't override success)
            if ($epayPayment->payment_status !== 'completed') {
                $epayPayment->payment_status = 'failed';
                // Store error response for debugging
                $epayPayment->payment_error_response = $request->all();
                $epayPayment->save();
            }

            // Determine error message based on error code
            $errorMessage = 'Payment was cancelled.';
            if ($errorCode == 455 || $reasonCode == 455) {
                $errorMessage = '3D Secure verification is not available or the card number was entered incorrectly. Please try using a different card, browser, or device. If the error persists, contact your bank.';
            } elseif ($errorCode || $reasonCode) {
                $errorMessage = 'Payment failed. Error code: ' . ($errorCode ?? $reasonCode);
                if ($reason) {
                    $errorMessage .= ' - ' . $reason;
                }
            }

            // Clear session
            session()->forget(['epay_invoice_id', 'epay_order_id', 'epay_payment_id']);

            session()->flash('flash.banner', $errorMessage);
            session()->flash('flash.bannerStyle', 'warning');

            return redirect()->route('order_success', $epayPayment->order->uuid);
        }

        // Clear session even if payment not found
        session()->forget(['epay_invoice_id', 'epay_order_id', 'epay_payment_id']);

        // Determine error message based on error code
        $errorMessage = 'Payment was cancelled.';
        if ($errorCode == 455 || $reasonCode == 455) {
            $errorMessage = '3D Secure verification is not available or the card number was entered incorrectly. Please try using a different card, browser, or device.';
        }

        session()->flash('flash.banner', $errorMessage);
        session()->flash('flash.bannerStyle', 'warning');

        return redirect()->route('home');
    }

    /**
     * Show payment page with Epay JS library
     */
    

    /**
     * Handle webhook from Epay (postLink callback)
     * According to documentation:
     * - Success: code="ok", reason="success"
     * - Failure: code="error"
     */
    public function webhook(Request $request, $hash)
    {
        info('Epay Webhook Received:', $request->all());

        $restaurant = Restaurant::where('hash', $hash)->first();
        if (!$restaurant) {
            Log::error('Epay Webhook: Restaurant not found for hash: ' . $hash);
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        $invoiceId = $request->input('invoiceId');
        $code = $request->input('code');
        $reason = $request->input('reason');
        $secretHash = $request->input('secret_hash');
        $paymentId = $request->input('id');
        $reference = $request->input('reference');
        $accountId = $request->input('accountId');

        if (!$invoiceId) {
            Log::error('Epay Webhook: Missing invoiceId', $request->all());
            return response()->json(['error' => 'Missing invoiceId'], 400);
        }

        // Find payment by invoice ID and secret hash for security
        $epayPayment = EpayPayment::where('epay_invoice_id', $invoiceId)->first();
        
        // Verify secret hash if provided
        if ($secretHash && $epayPayment && $epayPayment->epay_secret_hash !== $secretHash) {
            Log::error('Epay Webhook: Secret hash mismatch for invoiceId: ' . $invoiceId);
            return response()->json(['error' => 'Invalid secret hash'], 403);
        }

        if (!$epayPayment) {
            Log::error('Epay Webhook: Payment not found for invoiceId: ' . $invoiceId);
            return response()->json(['error' => 'Payment not found'], 404);
        }

        $order = $epayPayment->order;
        if (!$order) {
            Log::error('Epay Webhook: Order not found for payment: ' . $epayPayment->id);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Verify restaurant matches
        if ($order->branch->restaurant_id !== $restaurant->id) {
            Log::error('Epay Webhook: Restaurant mismatch');
            return response()->json(['error' => 'Restaurant mismatch'], 403);
        }

        // Handle success according to documentation: code="ok" and reason="success"
        if ($code === 'ok' && $reason === 'success') {
            $epayPayment->payment_status = 'completed';
            $epayPayment->payment_date = now();
            $epayPayment->epay_payment_id = $paymentId ?? $reference ?? $invoiceId;
            $epayPayment->payment_error_response = $request->all();
            $epayPayment->save();

            // Update order
            $order->amount_paid = ($order->amount_paid ?? 0) + $epayPayment->amount;
            $order->status = 'paid';
            $order->save();

            // Create payment record
            Payment::updateOrCreate(
                [
                    'order_id' => $epayPayment->order_id,
                    'payment_method' => 'epay',
                ],
                [
                    'branch_id' => $order->branch_id,
                    'amount' => $epayPayment->amount,
                    'transaction_id' => $epayPayment->epay_payment_id ?? (string)$epayPayment->id,
                ]
            );

            SendNewOrderReceived::dispatch($order);

            if ($order->customer_id) {
                SendOrderBillEvent::dispatch($order);
            }

            return response()->json(['status' => 'success', 'message' => 'Payment processed']);
        }

        // Handle failure according to documentation: code="error"
        if ($code === 'error') {
            $epayPayment->payment_status = 'failed';
            $epayPayment->epay_payment_id = $paymentId ?? $reference ?? $invoiceId;
            $epayPayment->payment_error_response = $request->all();
            $epayPayment->save();

            Log::warning('Epay Webhook: Payment failed', [
                'invoiceId' => $invoiceId,
                'reason' => $reason,
                'reasonCode' => $request->input('reasonCode'),
            ]);

            return response()->json(['status' => 'failed', 'message' => 'Payment failed']);
        }

        // Unknown status
        Log::warning('Epay Webhook: Unknown payment status', [
            'invoiceId' => $invoiceId,
            'code' => $code,
            'reason' => $reason,
        ]);

        return response()->json(['status' => 'unknown', 'message' => 'Unknown payment status']);
    }
}
