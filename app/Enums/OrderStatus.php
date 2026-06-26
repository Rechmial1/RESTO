<?php

namespace App\Enums;

enum OrderStatus: string

{
    case PLACED = 'placed';
    // case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case FOOD_READY = 'food_ready';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case OUT_FOR_DELIVERY = 'out_for_delivery'; // Order is being delivered
    case SERVED = 'served'; // Order served at table (for dine-in)
    case DELIVERED = 'delivered'; // Order delivered to the customer
    case CANCELLED = 'cancelled'; // Order cancelled

    public function label(): string
    {
        return match ($this) {
            self::PLACED => 'Order Placed',
            self::CONFIRMED => 'Order Confirmed',
            self::PREPARING => 'Order Preparing',
            self::FOOD_READY => 'Food is Ready',
            self::READY_FOR_PICKUP => 'Order is Ready for Pickup',
            self::OUT_FOR_DELIVERY => 'Order is Out for Delivery',
            self::SERVED => 'Order Served',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Order Cancelled',
        };
    }

    /**
     * Get the translated label for the order status.
     *
     * @return string
     */
    public function translatedLabel(): string
    {
        return __('modules.order.' . $this->label());
    }

    /**
     * Get the SVG icon for the order status.
     *
     * @return string
     */
    public function icon(): string
    {
        return match ($this) {
            self::PLACED => '<svg class="w-5 h-5" fill="currentColor"  clip-rule="evenodd" fill-rule="evenodd" height="24" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20.75 3v19a.75.75 0 0 1-1.173.619l-2.495-1.703-2.359 1.693a.75.75 0 0 1-.931-.045L12 20.997l-1.792 1.567a.75.75 0 0 1-.931.045l-2.359-1.693-2.495 1.703A.75.75 0 0 1 3.25 22V3c0-.966.783-1.75 1.75-1.75h14c.967 0 1.75.784 1.75 1.75M8 10.75h8a.75.75 0 0 0 0-1.5H8a.75.75 0 0 0 0 1.5m0-4h8a.75.75 0 0 0 0-1.5H8a.75.75 0 0 0 0 1.5m0 8h4a.75.75 0 0 0 0-1.5H8a.75.75 0 0 0 0 1.5"/></svg>',

            self::CONFIRMED => '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',

            self::PREPARING => '<svg height="24" class="w-5 h-5 text-current" fill="currentColor" viewBox="0 0 512.733 512.733" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M512.371 256.358c0-23.952-3.309-47.671-9.835-70.498l-2.192-7.667c11.532-37.679 6.067-63.539-13.263-113.163-30.678-70.33-83.166-82.781-123.565-41.213C196.251-54.851-1.227 71.315.372 256.36c-1.619 179.914 186.101 305.902 351.999 237.447v-43.995c-139.83 72.063-314.096-35.413-311.998-193.457C38.006 104.118 202.432-4.215 341.228 57.667c-13.5 26.719-23.289 69.026-22.857 81.823.765 45.535 19.524 83.248 74 92.841v280.026h40V232.349c15.317-2.328 27.338-7.28 36.761-13.44a218 218 0 0 1 3.23 36.424l.009.024V393.91c26.04-40.715 40-88.059 40-137.552m-100-62.562c-54.163 1.312-67.892-44.008-38.052-112.372 23.692-54.079 52.185-54.534 76.118-.352 23.809 56.101 27.098 112.439-38.066 112.724m-176-117.438h40v200h-152v-40h112z"/></svg>',

            self::FOOD_READY, self::READY_FOR_PICKUP => '<svg class="w-5 h-5 text-current" fill="currentColor" height="24" viewBox="0 0 512 512" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M271 32.313V15c0-8.291-6.709-15-15-15s-15 6.709-15 15v16.692C141.226 38.557 84.692 112.788 67.101 181h378.448C426.286 99.904 356.214 38.91 271 32.313M497 211H15c-8.315 0-15.022 6.887-15 15.203.009 3.373.251 6.689.709 9.933C3.6 256.694 22.89 271 43.647 271h424.624c20.757 0 40.074-14.268 43.008-34.816a72 72 0 0 0 .721-9.963c.024-8.322-6.678-15.221-15-15.221m-64.459 90h-46.688a60 60 0 0 0-42.427 17.574l-48.638 48.638A30 30 0 0 1 273.573 376h-81.464a5.337 5.337 0 0 1-2.719-9.93s54.518-32.3 80.068-47.474c.646-2.195 1.785-4.281 1.967-6.581.249-3.135-.244-6.962-1.366-11.016H166.467c-9.717 0-19.305 2.276-27.874 6.861a967 967 0 0 0-31.274 17.499c-24.712 14.429-33.223 34.805-43.066 58.403-6.68 16.04-14.268 34.219-28.652 55.796-9.053 13.521 4.497 24.653 21.665 38.76C86.869 502.65 99.042 512 107.011 512c3.82 0 6.667-1.397 10.003-4.808C133.856 489.972 158.05 481 182.135 481h58.63c29.707 0 58.169-12.671 78.018-34.717L438.52 314.506A8.076 8.076 0 0 0 432.541 301"/></svg>',

            self::OUT_FOR_DELIVERY => '<svg class="w-5 h-5" fill="currentColor" height="24" viewBox="0 0 100 100" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M73.333 40h-10L41.666 28.333 30 46.666v6.666h13.604l6.667 20h7.027l-8.89-26.666H37.902l6.061-9.524 17.69 9.524h11.68z"/><path d="m76.667 40-3.334-13.334H65l-1.667 6.667h6.514l1.969 7.878zM58.333 18.333A8.336 8.336 0 0 1 50 26.666c-4.604 0-8.334-3.73-8.334-8.333S45.396 10 50 10a8.336 8.336 0 0 1 8.333 8.333M10 30v23.332h20V30zm13.334 16.666h-6.668v-10h6.668z"/><path d="M76.667 51.666V40H70v11.666c0 3.666-1.341 9.35-2.981 12.631L60 78.332H46.666l-6.666-20H20c-5.522 0-10 4.477-10 10v10h29.639L41.86 85h22.261l3.333-6.668H90v-6.666c0-7.812-5.091-14.883-13.333-20m-60.001 20v-3.334A3.337 3.337 0 0 1 20 65h15.195l2.222 6.666zm54.122 0 2.193-4.389c1.12-2.238 2.09-5.188 2.758-8.166 3.561 2.715 7.594 7.012 7.594 12.555z"/><path d="M73.333 46.666h10v6.666h-10zM10 13.333V10h23.334v5zm6.666 10V20h20v5zM66.667 80c0 5.523 4.476 10 10 10 5.521 0 10-4.477 10-10 0-1.83-.527-3.523-1.387-5H68.054c-.86 1.477-1.387 3.17-1.387 5m10-3.334a3.333 3.333 0 1 1 0 6.666A3.33 3.33 0 0 1 73.333 80a3.33 3.33 0 0 1 3.334-3.334M15 80c0 5.523 4.477 10 10 10 5.521 0 10-4.477 10-10 0-1.83-.527-3.523-1.387-5H16.387C15.527 76.477 15 78.17 15 80m10-3.334a3.333 3.333 0 1 1 0 6.666A3.33 3.33 0 0 1 21.666 80 3.33 3.33 0 0 1 25 76.666"/></svg>',

            self::DELIVERED, self::SERVED => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',

            self::CANCELLED => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>',

            default => '<svg width="20" height="20" viewBox="0 0 1024 1024" class="w-4 h-4 icon" fill="currentColor" stroke="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M128 352.576V352a288 288 0 0 1 491.072-204.224 192 192 0 0 1 274.24 204.48 64 64 0 0 1 57.216 74.24C921.6 600.512 850.048 710.656 736 756.992V800a96 96 0 0 1-96 96H384a96 96 0 0 1-96-96v-43.008c-114.048-46.336-185.6-156.48-214.528-330.496A64 64 0 0 1 128 352.64zm64-.576h64a160 160 0 0 1 320 0h64a224 224 0 0 0-448 0m128 0h192a96 96 0 0 0-192 0m439.424 0h68.544A128.256 128.256 0 0 0 704 192c-15.36 0-29.952 2.688-43.52 7.616 11.328 18.176 20.672 37.76 27.84 58.304A64.128 64.128 0 0 1 759.424 352M672 768H352v32a32 32 0 0 0 32 32h256a32 32 0 0 0 32-32zm-342.528-64h365.056c101.504-32.64 165.76-124.928 192.896-288H136.576c27.136 163.072 91.392 255.36 192.896 288"/></svg>',
        };
    }

    /**
     * Check if the package type is editable.
     *
     * @return bool
     */
    // public function isEditable(): bool
    // {
    //     return !in_array($this, [self::DELIVERED], true);
    // }

    /**
     * Check if the package type is deletable.
     *
     * @return bool
     */
    // public function isDeletable(): bool
    // {
    //     return !in_array($this, [self::DELIVERED], true);
    // }
}
