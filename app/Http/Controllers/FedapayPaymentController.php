<?php

namespace App\Http\Controllers;

use App\Events\SendNewOrderReceived;
use App\Events\SendOrderBillEvent;
use App\Models\FedapayPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FedapayPaymentController extends Controller
{
    private function setKeys(string $restaurantHash): Restaurant
    {
        require_once base_path('vendor/fedapay/init.php');

        $restaurant = Restaurant::where('hash', $restaurantHash)->first();

        if (! $restaurant) {
            throw new \Exception('Invalid restaurant hash.');
        }

        $credential = $restaurant->paymentGateways;

        if (! $credential) {
            throw new \Exception('Payment credentials not found.');
        }

        $mode = ($credential->fedapay_mode ?? 'sandbox') === 'live' ? 'live' : 'sandbox';
        $secretKey = $mode === 'live'
            ? $credential->fedapay_secret_key
            : $credential->test_fedapay_secret_key;

        if (blank($secretKey)) {
            throw new \Exception('FedaPay credentials are not set correctly.');
        }

        \FedaPay\FedaPay::setApiKey($secretKey);
        \FedaPay\FedaPay::setEnvironment($mode);

        return $restaurant;
    }

    public function createPayment(Request $request, string $restaurantHash)
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        try {
            $restaurant = $this->setKeys($restaurantHash);
            $order = Order::with(['customer', 'branch.restaurant.currency'])->findOrFail($data['order_id']);

            // Vérifier si la commande n'est pas déjà payée
            if ($order->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => __('Order already paid'),
                ], 400);
            }

            $transaction = \FedaPay\Transaction::create([
                'description' => 'Order #' . ($order->formatted_order_number ?? $order->order_number ?? $order->id),
                'amount' => (int) round($order->total, 0),
                'currency' => [
                    'iso' => strtoupper($restaurant->currency->currency_code ?? 'XOF'),
                ],
                'callback_url' => route('fedapay.success', ['hash' => $restaurantHash]),
                'customer' => array_filter([
                    'firstname' => $order->customer?->name ?? 'Customer',
                    'email' => $order->customer?->email ?? 'customer@example.com',
                    'phone_number' => $order->customer?->phone ?? null,
                ]),
                'custom_metadata' => [
                    'order_id' => $order->id,
                    'restaurant_hash' => $restaurantHash,
                ],
            ]);

            $token = $transaction->generateToken();

            FedapayPayment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'fedapay_transaction_id' => $transaction->id,
                    'amount' => $order->total,
                    'payment_status' => 'pending',
                    'payment_error_response' => null,
                ]
            );

            Log::info('FedaPay payment created', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'payment_url' => $token->url ?? null,
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $token->url,
                'transaction_id' => $transaction->id,
                'token' => $token->token ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('FedaPay create payment failed', [
                'message' => $e->getMessage(),
                'restaurant_hash' => $restaurantHash,
                'order_id' => $data['order_id'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Payment creation failed'),
            ], 500);
        }
    }

public function success(Request $request, string $restaurantHash)
{
    $transactionId = $request->query('id');

    try {
        $restaurant = $this->setKeys($restaurantHash);

        if (! $transactionId) {
            return redirect()->route('shop_restaurant', [$restaurantHash])->with([
                'flash.banner' => __('Missing FedaPay transaction id.'),
                'flash.bannerStyle' => 'danger',
            ]);
        }

        $transaction = \FedaPay\Transaction::retrieve($transactionId);

        $fedapayPayment = FedapayPayment::where('fedapay_transaction_id', $transactionId)->first();

        if (! $fedapayPayment) {
            return redirect()->route('shop_restaurant', [$restaurantHash])->with([
                'flash.banner' => __('Payment not found.'),
                'flash.bannerStyle' => 'danger',
            ]);
        }

        $order = Order::with('branch.restaurant')->find($fedapayPayment->order_id);

        if (! $order) {
            return redirect()->route('shop_restaurant', [$restaurantHash])->with([
                'flash.banner' => __('Order not found.'),
                'flash.bannerStyle' => 'danger',
            ]);
        }

        // MODIFICATION: Vérifier si la commande est déjà payée
        if ($order->status === 'paid') {
            return redirect()->route('order_success', $order->uuid)->with([
                'flash.banner' => __('Order already paid'),
                'flash.bannerStyle' => 'info',
            ]);
        }

        if ($transaction->status !== 'approved') {
            $fedapayPayment->update([
                'payment_status' => 'failed',
                'payment_error_response' => json_encode([
                    'status' => $transaction->status ?? null,
                    'query' => $request->all(),
                ]),
            ]);

            return redirect()->route('order_success', $order->uuid)->with([
                'flash.banner' => __('Payment was not completed.'),
                'flash.bannerStyle' => 'warning',
            ]);
        }

        // MODIFICATION: Utiliser une transaction DB pour garantir l'intégrité
        DB::transaction(function () use ($order, $fedapayPayment, $transactionId, $restaurant) {
            $fedapayPayment->update([
                'payment_status' => 'completed',
                'payment_date' => now(),
                'payment_error_response' => null,
            ]);

            // MODIFICATION: Vérifier si le paiement n'existe pas déjà
            $existingPayment = Payment::where('transaction_id', $transactionId)->first();
            if (! $existingPayment) {
                Payment::create([
                    'order_id' => $order->id,
                    'branch_id' => $order->branch_id,
                    'payment_method' => 'fedapay',
                    'amount' => $fedapayPayment->amount,
                    'transaction_id' => $transactionId,
                    'payment_date' => now(),
                ]);
            }

            // MODIFICATION: Mettre à jour la commande
            $order->amount_paid = $order->total;
            $order->payment_date = now();
            $order->status = 'paid';
            
            // MODIFICATION: Gestion du statut sans écraser si déjà défini
            if ($order->status === 'draft' || $order->status === 'pending_verification') {
                $order->status = $restaurant->auto_confirm_orders ? 'kot' : 'paid';
            }
            
            if (empty($order->order_status) || $order->order_status === 'placed') {
                $order->order_status = $restaurant->auto_confirm_orders ? 'confirmed' : 'placed';
            }
            
            $order->save();
        });

        // MODIFICATION: Envoyer les notifications seulement si la commande n'est pas déjà traitée
        try {
            SendNewOrderReceived::dispatch($order->fresh());

            if ($order->customer_id) {
                SendOrderBillEvent::dispatch($order->fresh());
            }
        } catch (\Throwable $e) {
            Log::warning('FedaPay payment succeeded but notifications failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->route('order_success', $order->uuid)->with([
            'flash.banner' => __('messages.paymentDoneSuccessfully'),
            'flash.bannerStyle' => 'success',
        ]);
        
    } catch (\Throwable $e) {
        Log::error('FedaPay success callback failed', [
            'message' => $e->getMessage(),
            'restaurant_hash' => $restaurantHash,
            'transaction_id' => $transactionId,
            'query' => $request->all(),
        ]);

        $fedapayPayment = null;
        if ($transactionId) {
            $fedapayPayment = FedapayPayment::where('fedapay_transaction_id', $transactionId)->first();
        }

        if ($fedapayPayment && $fedapayPayment->order) {
            return redirect()->route('order_success', $fedapayPayment->order->uuid)->with([
                'flash.banner' => __('Payment processed, but we could not finalize everything automatically.'),
                'flash.bannerStyle' => 'warning',
            ]);
        }

        return redirect()->route('shop_restaurant', [$restaurantHash])->with([
            'flash.banner' => __('Payment processed, but we could not finalize the redirect.'),
            'flash.bannerStyle' => 'warning',
        ]);
    }
}

    public function cancel(Request $request, string $restaurantHash)
    {
        $transactionId = $request->query('id');

        try {
            $fedapayPayment = FedapayPayment::where('fedapay_transaction_id', $transactionId)->first();

            if ($fedapayPayment && $fedapayPayment->order) {
                $fedapayPayment->update([
                    'payment_status' => 'failed',
                    'payment_error_response' => 'Payment cancelled by customer.',
                ]);

                return redirect()->route('order_success', $fedapayPayment->order->uuid)->with([
                    'flash.banner' => __('Payment was cancelled.'),
                    'flash.bannerStyle' => 'warning',
                ]);
            }

            return redirect()->route('shop_restaurant', [$restaurantHash])->with([
                'flash.banner' => __('Payment was cancelled.'),
                'flash.bannerStyle' => 'warning',
            ]);
        } catch (\Throwable $e) {
            Log::error('FedaPay cancel failed', [
                'message' => $e->getMessage(),
                'restaurant_hash' => $restaurantHash,
                'transaction_id' => $transactionId,
            ]);

            return redirect()->route('shop_restaurant', [$restaurantHash])->with([
                'flash.banner' => __('Unable to cancel payment properly.'),
                'flash.bannerStyle' => 'warning',
            ]);
        }
    }

   public function webhook(Request $request, string $restaurantHash)
{
    try {
        $this->setKeys($restaurantHash);

        Log::info('FedaPay webhook received', $request->all());

        $transactionId = data_get($request->all(), 'entity.id')
            ?? data_get($request->all(), 'data.id')
            ?? $request->input('id');

        if (! $transactionId) {
            return response()->json(['message' => 'Transaction id missing'], 422);
        }

        $transaction = \FedaPay\Transaction::retrieve($transactionId);
        $fedapayPayment = FedapayPayment::where('fedapay_transaction_id', $transactionId)->first();

        if (! $fedapayPayment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $order = Order::with('branch.restaurant')->find($fedapayPayment->order_id);
        
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // MODIFICATION: Éviter les doublons de traitement
        if ($transaction->status === 'approved' && $fedapayPayment->payment_status !== 'completed') {
            DB::transaction(function () use ($order, $fedapayPayment, $transactionId, $transaction) {
                $fedapayPayment->update([
                    'payment_status' => 'completed',
                    'payment_date' => now(),
                ]);

                // MODIFICATION: Vérifier si le paiement n'existe pas déjà
                $existingPayment = Payment::where('transaction_id', $transactionId)->first();
                if (!$existingPayment) {
                    Payment::create([
                        'order_id' => $order->id,
                        'branch_id' => $order->branch_id,
                        'payment_method' => 'fedapay',
                        'amount' => $fedapayPayment->amount,
                        'transaction_id' => $transactionId,
                        'payment_date' => now(),
                    ]);
                }

                $restaurant = $order->branch->restaurant;

                // MODIFICATION: Ne mettre à jour la commande que si elle n'est pas déjà payée
                if ($order->status !== 'paid') {
                    $order->amount_paid = $order->total;
                    $order->payment_date = now();
                    $order->status = 'paid';
                    
                    if ($order->status === 'draft' || $order->status === 'pending_verification') {
                        $order->status = $restaurant->auto_confirm_orders ? 'kot' : 'paid';
                    }
                    
                    if (empty($order->order_status) || $order->order_status === 'placed') {
                        $order->order_status = $restaurant->auto_confirm_orders ? 'confirmed' : 'placed';
                    }
                    
                    $order->save();
                    
                    // MODIFICATION: Envoyer les notifications uniquement si c'est la première fois
                    try {
                        SendNewOrderReceived::dispatch($order);
                        if ($order->customer_id) {
                            SendOrderBillEvent::dispatch($order);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Webhook: notifications failed', ['order_id' => $order->id]);
                    }
                }
            });
        }

        if (in_array($transaction->status, ['declined', 'canceled', 'failed'])) {
            $fedapayPayment->update([
                'payment_status' => 'failed',
                'payment_error_response' => json_encode($request->all()),
            ]);
            
            // MODIFICATION: Mettre à jour le statut de paiement de la commande
            if ($order->status !== 'paid') {
                $order->status = 'failed';
                $order->save();
            }
        }

        return response()->json(['message' => 'Webhook processed']);
    } catch (\Throwable $e) {
        Log::error('FedaPay webhook failed', [
            'message' => $e->getMessage(),
            'payload' => $request->all(),
            'restaurant_hash' => $restaurantHash,
        ]);

        return response()->json([
            'message' => 'Webhook processing failed',
        ], 500);
    }
}
}