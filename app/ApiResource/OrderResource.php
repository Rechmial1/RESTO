<?php

namespace App\ApiResource;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Enums\OrderStatus;

class OrderResource extends JsonResource
{
    /**
     * Calculate distance between two coordinates in kilometers
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        if (!$lat1 || !$lng1 || !$lat2 || !$lng2) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 1);
    }

    /**
     * Format ETA in minutes to "XX mins" format
     */
    private function formatETA($minutes)
    {
        if (!$minutes) {
            return null;
        }
        return $minutes . ' mins';
    }

    /**
     * Get order status label
     */
    private function getStatusLabel()
    {
        $status = $this->order_status ?? $this->status;

        if (is_object($status)) {
            $status = $status->value ?? $status->name ?? 'pending';
        }


        switch ($status) {
            case OrderStatus::PLACED->value:
                return 'Order Placed';
            case OrderStatus::CONFIRMED->value:
                return 'Order Confirmed';
            case OrderStatus::PREPARING->value:
                return 'Order Preparing';
            case OrderStatus::FOOD_READY->value:
                return 'Food is Ready';
            case OrderStatus::READY_FOR_PICKUP->value:
                return 'Order is Ready for Pickup';
            case OrderStatus::OUT_FOR_DELIVERY->value:
                return 'Order is Out for Delivery';
            case OrderStatus::SERVED->value:
                return 'Order Served';
            case OrderStatus::DELIVERED->value:
                return 'Delivered';
            case OrderStatus::CANCELLED->value:
                return 'Order Cancelled';
        }

        return $status;
    }

    /**
     * Build timeline from order events
     */
    private function buildTimeline()
    {
        $timeline = [];

        if ($this->created_at) {
            $timeline[] = [
                'time' => $this->created_at->format('H:i'),
                'label' => 'Order placed',
            ];
        }

        if ($this->delivery_started_at) {
            $timeline[] = [
                'time' => $this->delivery_started_at->format('H:i'),
                'label' => 'Out for delivery',
            ];
        }

        if ($this->delivered_at) {
            $timeline[] = [
                'time' => $this->delivered_at->format('H:i'),
                'label' => 'Delivered',
            ];
        }

        return $timeline;
    }

    public function toArray($request)
    {
        $branch = $this->branch;
        $restaurant = $branch?->restaurant;
        $customer = $this->customer;
        $deliveryExecutive = $this->deliveryExecutive;
        $payment = $this->payments()->latest()->first();

        // Calculate distances
        $distanceToPickup = null;
        $distanceToCustomer = null;

        if ($branch && $branch->lat && $branch->lng) {
            // Distance to customer (from branch to customer)
            if ($this->customer_lat && $this->customer_lng) {
                $distanceToCustomer = $this->calculateDistance(
                    $branch->lat,
                    $branch->lng,
                    $this->customer_lat,
                    $this->customer_lng
                );
            }
        }

        // Format payment amount
        $paymentAmount = null;
        $paymentMode = null;
        if ($payment) {
            $paymentAmount = currency_format($this->total, $restaurant?->currency_id ?? null);
            $paymentMode = $payment->payment_method === 'cash' ? 'COD' : 'Prepaid';
        } else {
            $paymentMode = 'COD'; // Default to COD if no payment
            $paymentAmount = currency_format($this->total, $restaurant?->currency_id ?? null);
        }

        // Get items
        $items = [];
        if ($this->items && $this->items->count() > 0) {
            $items = $this->items->map(function ($item) {
                return [
                    'name' => $item->menuItem?->item_name ?? $item->menuItem?->name ?? 'Unknown Item',
                    'quantity' => $item->quantity ?? 1,
                ];
            })->toArray();
        } elseif ($this->kot && $this->kot->count() > 0) {
            // Fallback to KOT items if order items not available
            foreach ($this->kot as $kot) {
                if ($kot->items) {
                    foreach ($kot->items as $kotItem) {
                        $menuItem = $kotItem->menuItem;
                        if ($menuItem) {
                            $items[] = [
                                'name' => $menuItem->item_name ?? $menuItem->name ?? 'Unknown Item',
                                'quantity' => $kotItem->quantity ?? 1,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'id' => $this->id,
            'order_number' => $this->formatted_order_number ?? $this->show_formatted_order_number ?? 'ORD-' . $this->order_number,
            'status' => $this->getStatusLabel(),
            'restaurant' => [
                'name' => $branch?->name ?? 'Restaurant',
                'address' => $branch?->address ?? $restaurant?->address ?? '',
                'contact' => $branch?->phone ?? $restaurant?->phone ?? '',
                'coordinates' => [
                    'latitude' => $branch?->lat ?? null,
                    'longitude' => $branch?->lng ?? null,
                ],
            ],
            'customer' => [
                'name' => $customer?->name ?? '',
                'phone' => ($customer?->phone_code ?? '') . ($customer?->phone ?? ''),
                'address' => $customer?->address ?? $this->delivery_address ?? '',
                'coordinates' => [
                    'latitude' => $this->customer_lat ?? null,
                    'longitude' => $this->customer_lng ?? null,
                ],
            ],
            'partner' => [
                'name' => $deliveryExecutive?->name ?? '',
                'contact' => $deliveryExecutive?->phone ?? $deliveryExecutive?->contact ?? '',
            ],
            'pickupETA' => $this->formatETA($this->estimated_eta_min),
            'dropETA' => $this->formatETA($this->estimated_eta_max),
            'distanceToPickup' => $distanceToPickup ? $distanceToPickup . ' km' : null,
            'distanceToCustomer' => $distanceToCustomer ? $distanceToCustomer . ' km' : null,
            'instructions' => $this->note ?? null,
            'payment' => [
                'mode' => $paymentMode,
                'amount' => $paymentAmount,
            ],
            'timeline' => $this->buildTimeline(),
            'items' => $items,
        ];
    }
}
