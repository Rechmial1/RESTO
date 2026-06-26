<?php

namespace App\Livewire\Shop;

use App\Models\Kot;
use App\Models\Tax;
use App\Models\Area;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use App\Models\Country;
use Razorpay\Api\Api;
use App\Models\KotItem;
use App\Models\Payment;
use Livewire\Component;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\OrderCharge;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Models\ItemCategory;
use App\Models\PaypalPayment;
use App\Models\StripePayment;
use App\Models\ModifierOption;
use App\Events\NewOrderCreated;
use App\Models\RazorpayPayment;
use App\Models\EpayPayment;
use Illuminate\Validation\Rule;
use App\Models\RestaurantCharge;
use App\Models\MenuItemVariation;
use App\Models\FlutterwavePayment;
use App\Models\AdminPayfastPayment;
use Illuminate\Support\Facades\Log;
use App\Events\SendNewOrderReceived;
use App\Models\AdminPaystackPayment;
use App\Models\XenditPayment;
use App\Notifications\SendOrderBill;
use Illuminate\Support\Facades\Http;
use App\Scopes\AvailableMenuItemScope;
use App\Models\PaymentGatewayCredential;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Events\OrderUpdated;
use App\Traits\PrinterSetting;
use App\Models\KotPlace;
use App\Models\Printer;
use Livewire\Attributes\Computed;
use App\Models\FedapayPayment;
// use FedaPay\FedaPay;
// use FedaPay\Transaction;

class Cart extends Component
{

    use LivewireAlert;
    use PrinterSetting;

    public $search;
    public $tableID;
    public $filterCategories;
    public $kotList = [];
    public $showVariationModal = false;
    public $showCartVariationModal = false;
    public $showCustomerNameModal = false;
    public $showPaymentModal = false;
    public $showMenu = true;
    public $showCart = false;
    public $orderItemList = [];
    public $orderItemVariation = [];
    public $orderItemQty = [];
    public $cartItemQty = [];
    public $orderItemAmount = [];
    public $orderItemModifiersPrice = [];
    public $menuItem;
    public $subTotal;
    public $total;
    public $taxes;
    public $customer;
    public $customerName;
    public $customerPhone;
    public $customerPhoneCode;
    public $customerAddress;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;
    public $orderNumber;
    public $paymentGateway;
    public $paymentOrder;
    public $showVeg;
    public $razorpayStatus;
    public $stripeStatus;
    public $cartQty;
    public $restaurantHash;
    public $restaurant;
    public $shopBranch;
    public $orderType;
    public $orderTypeId; // Add orderTypeId for pricing context
    public $orderTypeSlug; // Add orderTypeSlug
    public $showOrderTypeModal = false; // Modal for order type selection
    public $cameFromQR = false; // Track if user came from QR code
    public $payNow = false;
    public $offline_payment_status;
    public $paymentMethod = 'fedapay';
    public $menuId;
    public $orderID;
    public $order;
    public $table;
    public $tables;
    public $getTable;
    public $qrCodeImage;
    public $enableQrPayment;
    public $showQrCode = false;
    public $showPaymentDetail = false;
    public $showTableModal = false;
    public $canCreateOrder;
    public $orderBeingProcessed = false;
    public $showModifiersModal = false;
    public $itemModifiersSelected = [];
    public $selectedModifierItem;
    public $showItemDetailModal = false;
    public $selectedItem;
    public $extraCharges;
    public $orderNote;
    public $showItemVariationsModal = false;
    public $showDeliveryAddressModal = false;
    public $addressLat;
    public $addressLng;
    public $deliveryAddress;
    public $deliveryFee = null;
    public $maxPreparationTime;
    public $etaMin;
    public $etaMax;
    public $itemNotes = [];
    public $orderItemTaxDetails = [];
    public $totalTaxAmount = 0;
    public $taxMode;
    public $showPickupDateTimeModal = false;
    public $pickupRange;
    public $now;
    public $minDate;
    public $maxDate;
    public $defaultDate;
    public $deliveryDateTime;
    public $showHalal;
    public $headerType = 'text';
    public $headerText;
    public $headerImages = [];
    public $isHeaderDisabled = false;
    public $showLocationModal = false;
    public $is_within_radius = true;
    public $menuItemsLoaded = 50;
    public $menuItemsPerLoad = 50;

    public function mount()
    {
        if ($this->tableID) {
            $this->table = Table::where('hash', $this->tableID)->firstOrFail();
            $restaurant = $this->table->branch->restaurant;

            $fetchActiveOrder = Order::where('table_id', $this->table->id)->where('status', 'kot')->whereDate('date_time', '=', now($restaurant->timezone)->toDateString())->first();

            if ($fetchActiveOrder) {
                $this->orderID = $fetchActiveOrder->id;
                $this->order = $fetchActiveOrder;
            }

            $this->restaurant = $restaurant;
            $this->restaurantHash = $restaurant->hash;
        }

        if (!$this->restaurant) {
            abort(404);
        }

        // Detect if user came from QR code
        $this->cameFromQR = request()->query('hash') === $this->restaurant->hash ||
                           request()->boolean('from_qr') ||
                           !is_null($this->tableID);
        $this->paymentGateway = PaymentGatewayCredential::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->first();
        $this->taxes = Tax::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->get();
        $this->customer = customer();
        $this->razorpayStatus = (bool)($this->paymentGateway->razorpay_status ?? false);
        $this->stripeStatus = (bool)($this->paymentGateway->stripe_status ?? false);

        // Initialize phone codes
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
        $this->customerPhoneCode = $this->customer?->phone_code ?? $this->restaurant->phone_code ?? $this->allPhoneCodes->first();

        // If came from QR code, auto-set to dine_in and don't show modal
        if ($this->cameFromQR) {
            $this->orderType = 'dine_in';
            $this->setDefaultOrderType();
            $this->showOrderTypeModal = false;
        } else {
            // For regular users, determine default order type but show modal first
            $this->orderType = $this->restaurant->allow_dine_in_orders ? 'dine_in' : ($this->restaurant->allow_customer_delivery_orders ? 'delivery' : 'pickup');

            // Check if we have multiple order types to show modal
            $availableOrderTypes = OrderType::where('branch_id', $this->shopBranch->id)
                ->where('is_active', true)
                ->where('enable_from_customer_site', true)
                ->count();

            // Show modal if more than one order type available
            $this->showOrderTypeModal = $availableOrderTypes > 1;

            // If only one order type, set it automatically
            if ($availableOrderTypes == 1) {
                $this->setDefaultOrderType();
            }
        }

        if (request()->has('current_order')) {
            $this->orderID = request()->get('current_order');
            $this->order = Order::find($this->orderID);
            if ($this->order->status == 'paid') {
                $this->redirect(module_enabled('Subdomain') ? url('/') : route('shop_restaurant', ['hash' => $this->order->branch->restaurant->hash]));
            }
        }

        // Fetch QR code image from database
        $this->qrCodeImage = $this->restaurant->qr_code_image;

        // Only call these if order type is already selected
        if (!$this->showOrderTypeModal) {
            $this->updatedOrderType($this->orderType);
        }
        $this->taxMode = $this->restaurant->tax_mode ?? 'order';

        $this->pickupRange = restaurant()->pickup_days_range ?? 1;
        $this->minDate = now()->format('Y-m-d\TH:i');
        $this->maxDate = now()->addDays($this->pickupRange - 1)->endOfDay()->format('Y-m-d\TH:i');
        $this->defaultDate = old('deliveryDateTime', $this->deliveryDateTime ?? $this->minDate);

        $this->taxMode = $this->order?->tax_mode ?? ($this->restaurant->tax_mode ?? 'order');

        // Initialize header settings
        $this->initializeHeaderSettings();

        // Handle location for QR orders only
        if ($this->cameFromQR) {
            // Check session first for stored location
            $sessionLocation = session('customer_location');

            if ($sessionLocation && !empty($sessionLocation['lat']) && !empty($sessionLocation['lng'])) {
                // Load location from session
                $this->addressLat = $sessionLocation['lat'];
                $this->addressLng = $sessionLocation['lng'];
                if (!empty($sessionLocation['address'])) {
                    $this->deliveryAddress = $sessionLocation['address'];
                }

                // Re-validate radius if needed
                if (!empty($this->restaurant->qr_order_radius_meters)) {
                    $this->checkRadiusRestriction();
                }
            } else {
                // Show location modal only if came from QR and no session location
                if (empty($this->addressLat) || empty($this->addressLng)) {
                    $this->showLocationModal = true;
                }
            }
        }
    }

    /**
     * Set default order type and ID
     */
    private function setDefaultOrderType()
    {
        // Get default order type for the current order type
        $orderTypeModel = OrderType::where('branch_id', $this->shopBranch->id)
            ->where('is_active', true)
            ->first();

        if ($orderTypeModel) {
            $this->orderTypeId = $orderTypeModel->id;
            $this->orderTypeSlug = $orderTypeModel->slug;
        }
    }

    public function requestCustomerLocation()
    {
        $this->dispatch('requestGeolocation');
    }
    /**
     * Haversine formula to calculate distance between two points (meters)
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);
        $latDiff = $lat2 - $lat1;
        $lngDiff = $lng2 - $lng1;
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos($lat1) * cos($lat2) * sin($lngDiff / 2) * sin($lngDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Check if customer is within allowed radius for QR orders
     * Returns true if allowed, false if restricted
     */
    private function checkRadiusRestriction()
    {
        // Only check for QR orders with radius configured
        if (!$this->cameFromQR || empty($this->restaurant->qr_order_radius_meters)) {
            return true; // Allow if not QR order or no radius restriction
        }

        // Check if location is set
        if (empty($this->addressLat) || empty($this->addressLng)) {
            return false; // Location required
        }

        // Get branch coordinates
        $branchLat = $this->shopBranch->lat ?? $this->shopBranch->latitude ?? null;
        $branchLng = $this->shopBranch->lng ?? $this->shopBranch->longitude ?? null;

        if (empty($branchLat) || empty($branchLng)) {
            return true; // Allow if branch coordinates missing
        }

        // Calculate distance
        $distance = $this->calculateDistance(
            $this->addressLat, $this->addressLng,
            $branchLat, $branchLng
        );

        // Update the flag
        $this->is_within_radius = $distance <= $this->restaurant->qr_order_radius_meters;

        return $this->is_within_radius;
    }

    public function setCustomerLocation($lat = null, $lng = null, $address = null)
    {
        if (is_null($lat) || is_null($lng)) {
            return;
        }

        $this->addressLat = $lat;
        $this->addressLng = $lng;
        if ($address) {
            $this->deliveryAddress = $address;
        }

        // Store location in session for current customer/order
        session([
            'customer_location' => [
                'lat' => $lat,
                'lng' => $lng,
                'address' => $address,
                'stored_at' => now()->toDateTimeString(),
            ]
        ]);

        $this->showLocationModal = false;

        // QR order radius enforcement
        if ($this->cameFromQR && !empty($this->restaurant->qr_order_radius_meters)) {
            // Check both lat/lng and latitude/longitude column names for branch
            $branchLat = $this->shopBranch->lat ?? $this->shopBranch->latitude ?? null;
            $branchLng = $this->shopBranch->lng ?? $this->shopBranch->longitude ?? null;

            if (!empty($branchLat) && !empty($branchLng)) {
                $distance = $this->calculateDistance(
                    $lat, $lng,
                    $branchLat,
                    $branchLng
                );
                $this->is_within_radius = $distance <= $this->restaurant->qr_order_radius_meters;

                if (!$this->is_within_radius) {
                    $this->alert('error', __('app.outsideAllowedArea'), [
                        'toast' => false,
                        'position' => 'center',
                    ]);

                    // Clear any existing cart items if outside radius
                    $this->orderItemList = [];
                    $this->orderItemQty = [];
                    $this->orderItemVariation = [];
                    $this->orderItemAmount = [];
                    $this->cartItemQty = [];
                    $this->orderItemModifiersPrice = [];
                    $this->itemModifiersSelected = [];
                    $this->itemNotes = [];
                    $this->calculateTotal();
                }
            } else {
                // If branch coordinates are missing, allow by default
                $this->is_within_radius = true;
            }
        } else {
            $this->is_within_radius = true;
        }

        // Recalculate delivery-related estimates if needed
        $this->calculateMaxPreparationTime();
        $this->calculateTotal();
    }

    /**
     * Handle order type change and update pricing context
     */
    public function updatedOrderTypeId($value)
    {
        if (!$value) {
            return;
        }

        // Get the order type information
        $orderType = OrderType::find($value);

        if (!$orderType) {
            return;
        }

        // Update the local variables
        $this->orderType = $orderType->type;
        $this->orderTypeSlug = $orderType->slug;

        // Get extra charges for this order type
        $mainExtraCharges = RestaurantCharge::withoutGlobalScopes()
            ->whereJsonContains('order_types', $this->orderTypeSlug)
            ->where('is_enabled', true)
            ->where('restaurant_id', $this->restaurant->id)
            ->get();

        // Update extra charges
        if (!$this->orderID) {
            // Only clear delivery-related fields if the order type is not delivery
            if ($this->orderTypeSlug !== 'delivery') {
                $this->addressLat = null;
                $this->addressLng = null;
                $this->deliveryAddress = null;
                $this->deliveryFee = null;
            }

            $this->calculateMaxPreparationTime();
            $this->extraCharges = $mainExtraCharges;
        } else {
            // For existing orders, keep existing charges if order type is unchanged
            $orderTypeFromOrder = $this->order->order_type_id
                ? (OrderType::where('id', $this->order->order_type_id)->value('slug') ?? $this->order->order_type)
                : $this->order->order_type;

            $this->extraCharges = $orderTypeFromOrder === $this->orderTypeSlug ? $this->order->extraCharges : $mainExtraCharges;
        }

        // Recalculate prices for all items in cart when order type changes
        foreach ($this->orderItemList ?? [] as $key => $item) {
            if ($this->orderTypeId) {
                $item->setPriceContext($this->orderTypeId, null);
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, null);
                }
            }

            // Recalculate modifier prices
            if (isset($this->itemModifiersSelected[$key]) && is_array($this->itemModifiersSelected[$key])) {
                $modifierPrice = 0;
                foreach ($this->itemModifiersSelected[$key] as $modifierId) {
                    $modifier = ModifierOption::find($modifierId);
                    if ($modifier) {
                        if ($this->orderTypeId) {
                            $modifier->setPriceContext($this->orderTypeId, null);
                        }
                        $modifierPrice += $modifier->price;
                    }
                }
                $this->orderItemModifiersPrice[$key] = $modifierPrice;
            }

            // Recalculate item amount
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $item->price;
            $this->orderItemAmount[$key] = $this->orderItemQty[$key] * ($basePrice + ($this->orderItemModifiersPrice[$key] ?? 0));
        }

        $this->calculateTotal();
    }

    /**
     * Handle order type selection from modal
     */
    public function selectOrderTypeFromModal($orderTypeId)
    {
        if (!$orderTypeId) {
            return;
        }

        // Set the order type ID which will trigger updatedOrderTypeId
        $this->orderTypeId = $orderTypeId;

        $orderType = OrderType::find($this->orderTypeId);
        $this->orderTypeSlug = $orderType->slug;
        $this->orderType = $orderType->type;
        // Close the modal
        $this->showOrderTypeModal = false;
    }

    public function initializeHeaderSettings()
    {
        $cartHeaderSetting = $this->restaurant->cartHeaderSetting;
        if ($cartHeaderSetting) {
            $this->headerType = $cartHeaderSetting->header_type;
            $this->headerText = $cartHeaderSetting->header_text;
            $this->headerImages = $cartHeaderSetting->images;
            $this->isHeaderDisabled = $cartHeaderSetting->is_header_disabled ?? false;
        } else {
            $this->headerText = __('messages.frontHeroHeading');
            $this->isHeaderDisabled = false;
        }
    }

    public function filterMenuItems($id)
    {
        $this->menuId = $id;
        $this->resetMenuItemsLoaded();
    }

    public function filterMenu($id = null)
    {
        $this->filterCategories = $id;
        $this->resetMenuItemsLoaded();
    }

    // Reset loaded items when search changes
    public function updatedSearch()
    {
        $this->resetMenuItemsLoaded();
    }

    // Reset loaded items when veg filter changes
    public function updatedShowVeg()
    {
        $this->resetMenuItemsLoaded();
    }

    // Reset loaded items when halal filter changes
    public function updatedShowHalal()
    {
        $this->resetMenuItemsLoaded();
    }

    // Helper method to reset menu items loaded
    private function resetMenuItemsLoaded()
    {
        $this->menuItemsLoaded = $this->menuItemsPerLoad;
    }

    public function showItemVariations($id)
    {
        $this->showItemVariationsModal = true;
        $this->menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->where('show_on_customer_site', true)->findOrFail($id);
    }

    public function addCartItems($id, $variationCount, $modifierCount)
    {
        // Check radius restriction before allowing cart operations
        if (!$this->checkRadiusRestriction()) {
            // Check if location is set
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        if (!$this->canCreateOrder) {
            $this->alert('error', __('messages.CartAddPermissionDenied'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        // Check order limit
        $orderStats = $this->shopBranch ? getRestaurantOrderStats($this->shopBranch->id) : null;
        if (!$orderStats || (!$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'])) {
            return;
        }

        $this->menuItem = MenuItem::where('show_on_customer_site', true)->find($id);


        if ($variationCount > 0) {
            $this->showVariationModal = true;
        } elseif ($modifierCount > 0) {
            $this->selectedModifierItem = $id;
            $this->showModifiersModal = true;
        } else {
            $this->syncCart($id);
        }

        // Ensure itemNotes key is initialized
        if (!isset($this->itemNotes[$id])) {
            $this->itemNotes[$id] = '';
        }
    }

    public function subCartItems($id)
    {
        $this->menuItem = MenuItem::find($id);
        $this->showCartVariationModal = true;
    }

    public function subModifiers($id)
    {
        $this->menuItem = MenuItem::find($id);
        // $this->showModifiersModal = true;
    }

    public function syncCart($id)
    {
        // Check radius restriction before adding items
        if (!$this->checkRadiusRestriction()) {
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        if (!isset($this->orderItemList[$id])) {

            $this->orderItemList[$id] = $this->menuItem;
            $this->orderItemQty[$id] = $this->orderItemQty[$id] ?? 1;

            // Set price context before using price
            if ($this->orderTypeId) {
                if (isset($this->orderItemVariation[$id])) {
                    $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, null);
                }
                if (isset($this->orderItemList[$id])) {
                    $this->orderItemList[$id]->setPriceContext($this->orderTypeId, null);
                }
            }

            $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
            $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
            $this->cartItemQty[$id] = isset($this->cartItemQty[$this->menuItem->id]) ? ($this->cartItemQty[$this->menuItem->id] + 1) : 1;
            $this->calculateTotal();
        } else {
            $this->addQty($id);
        }

        if (!isset($this->itemNotes[$id])) {
            $this->itemNotes[$id] = '';
        }
    }

    #[On('addQty')]
    public function addQty($id)
    {
        // Check radius restriction before allowing quantity increase
        if (!$this->checkRadiusRestriction()) {
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        $this->showCartVariationModal = false;
        $this->orderItemQty[$id] = isset($this->orderItemQty[$id]) ? ($this->orderItemQty[$id] + 1) : 1;
        $this->cartItemQty[$id] = isset($this->cartItemQty[$id]) ? ($this->cartItemQty[$id] + 1) : 1;

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, null);
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, null);
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    #[On('subQty')]
    public function subQty($id)
    {
        $this->showCartVariationModal = false;
        $this->orderItemQty[$id] = (isset($this->orderItemQty[$id]) && $this->orderItemQty[$id] > 1) ? ($this->orderItemQty[$id] - 1) : 0;

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$id])) {
                $this->orderItemVariation[$id]->setPriceContext($this->orderTypeId, null);
            }
            if (isset($this->orderItemList[$id])) {
                $this->orderItemList[$id]->setPriceContext($this->orderTypeId, null);
            }
        }

        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $menuID = explode('_', $id);

        if (isset($menuID[0])) {
            $menuID = str_replace('"', '', $menuID[0]);
        }

        $this->cartItemQty[$menuID] = isset($this->cartItemQty[$menuID]) ? ($this->cartItemQty[$menuID] - 1) : 0;

        if ($this->orderItemQty[$id] == 0) {
            unset($this->orderItemList[$id]);
            unset($this->orderItemVariation[$id]);
            unset($this->orderItemAmount[$id]);
            unset($this->orderItemQty[$id]);
        }

        if ($this->cartItemQty[$menuID] == 0) {
            unset($this->cartItemQty[$menuID]);
        }

        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->cartQty = 0;

        foreach ($this->orderItemQty ?? [] as $qty) {
            if ($qty > 0) {
                $this->cartQty++;
            }
        }

        $this->dispatch('updateCartCount', count: $this->cartQty);

        $this->total = 0;
        $this->subTotal = 0;
        $this->totalTaxAmount = 0;
        $this->orderItemTaxDetails = [];

        if (is_array($this->orderItemAmount)) {
            // Calculate item taxes first for proper subtotal calculation
            if ($this->taxMode === 'item') {
                $this->updateOrderItemTaxDetails();
            }

            foreach ($this->orderItemAmount as $key => $value) {
                $this->total += $value;

                // For inclusive taxes, subtract tax from subtotal
                if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
                    $taxDetail = $this->orderItemTaxDetails[$key];
                    $isInclusive = $this->restaurant->tax_inclusive ?? false;

                    if ($isInclusive) {
                        // For inclusive tax: subtotal = item amount - tax amount
                        $this->subTotal += ($value - ($taxDetail['tax_amount'] ?? 0));
                    } else {
                        // For exclusive tax: subtotal = item amount (tax will be added later)
                        $this->subTotal += $value;
                    }
                } else {
                    // No item taxes or order-level taxes
                    $this->subTotal += $value;
                }
            }
        }

        // Calculate taxes using centralized method
        $this->recalculateTaxTotals();

        // Add extra charges
        foreach ($this->extraCharges ?? [] as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                $this->total += $charge->getAmount($this->subTotal);
            }
        }
        $this->total += (float)$this->deliveryFee ?: 0;
    }

    /**
     * Legacy method kept for backward compatibility
     * Use updatedOrderTypeId for new implementation
     */
    public function updatedOrderType($value)
    {

        // Find the order type by slug or type
        $orderTypeModel = OrderType::where('branch_id', $this->shopBranch->id)
            ->where('is_active', true)
            ->where(function ($q) use ($value) {
                $q->where('slug', $value)
                    ->orWhere('type', $value);
            })
            ->first();

        if ($orderTypeModel) {
            $this->orderTypeId = $orderTypeModel->id;
            $mainExtraCharges = RestaurantCharge::withoutGlobalScopes()
                ->whereJsonContains('order_types', $value)
                ->where('is_enabled', true)
                ->where('restaurant_id', $this->restaurant->id)
                ->get();
            // Early return for new orders
            if (!$this->orderID) {
                // Only clear delivery-related fields if the order type is not delivery
                if ($value !== 'delivery') {
                    $this->addressLat = null;
                    $this->addressLng = null;
                    $this->deliveryAddress = null;
                    $this->deliveryFee = null;
                }

                $this->calculateMaxPreparationTime();
                $this->extraCharges = $mainExtraCharges;
                $this->calculateTotal();
                return;
            }

            // Early return if no valid order or order is paid
            if (!$this->order || $this->order->status === 'paid') {
                return;
            }

            // Efficiently get the slug from the order's order type
            $orderTypeFromOrder = $this->order->order_type_id
                ? (OrderType::where('id', $this->order->order_type_id)->value('slug') ?? $this->order->order_type)
                : $this->order->order_type;

            // Keep existing charges if order type is unchanged, otherwise set new ones
            $this->extraCharges = $orderTypeFromOrder === $value ? $this->order->extraCharges : $mainExtraCharges;

            $this->calculateTotal();
        }
    }

    #[On('setPosVariation')]
    public function setPosVariation($variationId)
    {
        $this->showVariationModal = false;
        $menuItemVariation = MenuItemVariation::find($variationId);

        // Set price context before using variation
        if ($this->orderTypeId) {
            $menuItemVariation->setPriceContext($this->orderTypeId, null);
        }

        $modifiersAvailable = $menuItemVariation->menuItem->modifiers->count();

        if ($modifiersAvailable) {
            $this->selectedModifierItem = $menuItemVariation->menu_item_id . '_' . $variationId;
            $this->showModifiersModal = true;
        } else {
            $this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId] = $menuItemVariation;
            $this->cartItemQty[$menuItemVariation->menu_item_id] = isset($this->cartItemQty[$menuItemVariation->menu_item_id]) ? ($this->cartItemQty[$menuItemVariation->menu_item_id] + 1) : 1;
            $this->orderItemAmount[$menuItemVariation->menu_item_id . '_' . $variationId] = (1 * (isset($this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId]) ? $this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId]->price : $this->orderItemList[$menuItemVariation->menu_item_id . '_' . $variationId]->price));
            $this->syncCart($menuItemVariation->menu_item_id . '_' . $variationId);
        }
    }

    #[On('setCustomer')]
    public function setCustomer($customer)
    {
        $customer = Customer::find($customer['id']);
        $this->customer = $customer;
    }

    #[On('showCartItems')]
    public function showCartItems()
    {
        $this->showCart = true;
        $this->showMenu = false;
    }

    #[On('showMenuItems')]
    public function showMenuItems()
    {
        $this->showCart = false;
        $this->showMenu = true;
    }

    public function updatedPhoneCodeIsOpen($value)
    {
        if (!$value) {
            $this->reset(['phoneCodeSearch']);
            $this->updatedPhoneCodeSearch();
        }
    }

    public function updatedPhoneCodeSearch()
    {
        $this->filteredPhoneCodes = $this->allPhoneCodes->filter(function ($phonecode) {
            return str_contains($phonecode, $this->phoneCodeSearch);
        })->values();
    }

    public function selectPhoneCode($phonecode)
    {
        $this->customerPhoneCode = $phonecode;
        $this->phoneCodeIsOpen = false;
        $this->phoneCodeSearch = '';
        $this->updatedPhoneCodeSearch();
    }

    public function submitCustomerName()
    {
        $rules = [
            'customerName' => 'required',
            'customerPhoneCode' => 'required',
            'customerPhone' => [
                'required',
                Rule::unique('customers', 'phone')->ignore($this->customer->id ?? null),
            ],
        ];

        // Require address when order type is delivery
        if ($this->orderType === 'delivery' || $this->orderTypeSlug === 'delivery') {
            $rules['customerAddress'] = 'required';
        }

        $this->validate($rules);


        $this->customer->name = $this->customerName;
        $this->customer->phone = $this->customerPhone;
        $this->customer->phone_code = $this->customerPhoneCode;
        $this->customer->delivery_address = $this->customerAddress;
        $this->customer->save();

        session(['customer' => $this->customer]);
        $this->dispatch('setCustomer', customer: $this->customer);

        $this->showCustomerNameModal = false;

        $this->placeOrder($this->payNow);
    }

    public function selectTableOrder($tableID = null)
    {
        if ($this->getTable) {
            $this->tableID = $tableID;
            $this->getTable = false;
            $this->showTableModal = false;
            $this->placeOrder($this->payNow);
        }
    }

    public function getShouldShowWaiterButtonMobileProperty()
    {

        $this->dispatch('refreshComponent');

        if (!$this->restaurant->is_waiter_request_enabled || !$this->restaurant->is_waiter_request_enabled_on_mobile) {
            return false;
        }

        $cameFromQR = request()->query('hash') === $this->restaurant->hash || request()->boolean('from_qr');

        if ($this->restaurant->is_waiter_request_enabled_open_by_qr && !$cameFromQR) {
            return false;
        }

        return true;
    }

    /**
     * Check if customer can create order based on radius restrictions
     */
    public function getCanCreateOrderProperty()
    {
        // For QR orders, check if within radius
        if ($this->cameFromQR && !empty($this->restaurant->qr_order_radius_meters)) {
            // If location is not set, allow for now (will be checked when adding items)
            if (empty($this->addressLat) || empty($this->addressLng)) {
                return true; // Allow to show UI, but will be blocked when adding itemss
            }

            // Return whether within radius
            return $this->is_within_radius ?? true;
        }

        // For non-QR orders or if no radius restriction, allow
        return true;
    }

    public function getAvailableTable()
    {
        $this->tables = Area::where('branch_id', $this->shopBranch->id)
            ->withCount([
                'tables' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
            ->with([
                'tables' => function ($query) {
                    $query->where('status', 'active');
                }
            ])
            ->get();
    }

    public function savePickupDateTime()
    {
        $this->showPickupDateTimeModal = false;
        $this->placeOrder();
    }

    public function showPickupDateTime()
    {
        $this->showPickupDateTimeModal = true;
    }

    public function placeOrder($pay = false, $updateOrder = null, $method = null)
    {
        // Force payment for every order type
        $pay = true;

        // FINAL QR RADIUS CHECK - always validate fresh
        if ($this->cameFromQR && !empty($this->restaurant->qr_order_radius_meters)) {
            $branchLat = $this->shopBranch->lat ?? $this->shopBranch->latitude ?? null;
            $branchLng = $this->shopBranch->lng ?? $this->shopBranch->longitude ?? null;

            if (!empty($branchLat) && !empty($branchLng)) {
                if (empty($this->addressLat) || empty($this->addressLng)) {
                    $this->showLocationModal = true;
                    $this->alert('error', __('app.locationAccessRequiredRadius'), [
                        'toast' => false,
                        'position' => 'center',
                    ]);
                    return;
                }

                $distance = $this->calculateDistance(
                    $this->addressLat,
                    $this->addressLng,
                    $branchLat,
                    $branchLng
                );

                $this->is_within_radius = $distance <= $this->restaurant->qr_order_radius_meters;

                if (! $this->is_within_radius) {
                    $this->alert('error', __('app.orderNotAllowedMeters', [
                        'meters' => $this->restaurant->qr_order_radius_meters
                    ]), [
                        'toast' => false,
                        'position' => 'center',
                    ]);
                    return;
                }
            }
        }

        if ($this->cameFromQR && ! $this->is_within_radius) {
            $this->alert('error', __('app.orderNotAllowedMeters', [
                'meters' => $this->restaurant->qr_order_radius_meters
            ]), [
                'toast' => false,
                'position' => 'center',
            ]);
            return;
        }

        if ($updateOrder) {
            $this->order = Order::find($updateOrder);

            Payment::create([
                'order_id' => $this->order->id,
                'branch_id' => $this->shopBranch->id,
                'payment_method' => $method,
                'amount' => $this->total,
            ]);

            Order::where('id', $this->order->id)->update([
                'status' => 'pending_verification',
            ]);

            $this->printKot($this->order);
            $this->sendNotifications($this->order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', [$this->order->uuid]));
            return;
        }

        if ($this->orderType == 'delivery') {
            $deliverySetting = $this->shopBranch->deliverySetting ?? null;
        }

        if (
            $this->customer &&
            (
                is_null($this->customer->name) ||
                (($this->orderType == 'delivery' && is_null($this->customerAddress)) && is_null($deliverySetting))
            )
        ) {
            $this->customerName = $this->customer->name;
            $this->customerAddress = $this->customer->delivery_address;
            $this->customerPhone = $this->customer->phone;
            $this->customerPhoneCode = $this->customer->phone_code ?? $this->restaurant->phone_code ?? $this->allPhoneCodes->first();
            $this->showCustomerNameModal = true;
            $this->payNow = true;
            return;
        }

        if (
            $this->customer &&
            $this->orderType === 'delivery' &&
            empty($this->addressLat) &&
            empty($this->addressLng) &&
            empty($this->deliveryAddress) &&
            isset($deliverySetting)
        ) {
            $this->customerAddress = $this->customer->delivery_address;
            $this->showDeliveryAddressModal = true;
            $this->payNow = true;
            return;
        }

        if ($this->orderType == 'dine_in' && $this->getTable) {
            $this->getAvailableTable();
            $this->payNow = true;
            $this->showTableModal = true;
            return;
        }

        if (!is_null($this->tableID)) {
            $table = Table::where('hash', $this->tableID)->firstOrFail();
        }

        if ($this->order && ($this->order->status == 'kot' || $this->order->status == 'draft')) {
            $order = $this->order;

            if (!is_null($this->tableID)) {
                $order->update(['table_id' => $table->id]);
            }
        } else {
            $orderNumberData = Order::generateOrderNumber($this->shopBranch);

            if ($this->orderTypeId) {
                $orderTypeModel = OrderType::find($this->orderTypeId);
                $orderTypeId = $orderTypeModel->id ?? null;
                $orderTypeName = $orderTypeModel->order_type_name ?? $this->orderType;

                if (! $this->orderTypeSlug && $orderTypeModel) {
                    $this->orderTypeSlug = $orderTypeModel->slug;
                }
            } else {
                $orderTypeModel = OrderType::where('is_default', 1)
                    ->where('type', $this->orderType)
                    ->first();

                $orderTypeId = $orderTypeModel->id ?? null;
                $orderTypeName = $orderTypeModel->order_type_name ?? $this->orderType;
                $this->orderTypeSlug = $orderTypeModel->slug ?? $this->orderType;
            }

            $order = Order::create([
                'order_number' => $orderNumberData['order_number'],
                'formatted_order_number' => $orderNumberData['formatted_order_number'],
                'branch_id' => $this->shopBranch->id,
                'table_id' => $table->id ?? null,
                'date_time' => now(),
                'customer_id' => $this->customer->id ?? null,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'order_type' => $this->orderTypeSlug ?? $this->orderType,
                'order_type_id' => $orderTypeId,
                'custom_order_type_name' => $orderTypeName,
                'pickup_date' => $this->deliveryDateTime,
                'delivery_address' => $this->customerAddress,
                'status' => 'pending_verification',
                'order_status' => $this->restaurant->auto_confirm_orders ? 'confirmed' : 'placed',
                'customer_lat' => $this->addressLat ?? null,
                'customer_lng' => $this->addressLng ?? null,
                'delivery_fee' => $this->deliveryFee ?? 0,
                'is_within_radius' => true,
                'delivery_started_at' => null,
                'delivered_at' => null,
                'estimated_eta_min' => $this->etaMin ?? null,
                'estimated_eta_max' => $this->etaMax ?? null,
                'placed_via' => 'shop',
                'tax_mode' => $this->taxMode,
            ]);
        }

        if ($this->customer && $this->orderType === 'delivery' && !empty($this->deliveryAddress) && isset($deliverySetting)) {
            $this->customer->delivery_address = $this->deliveryAddress;
            $this->customer->save();

            session(['customer' => $this->customer]);
        }

        $transactionId = uniqid('TXN_', true) . '_' . random_int(100000, 999999);
        session(['transaction_id' => $transactionId]);

        $kot = Kot::create([
            'branch_id' => $this->shopBranch->id,
            'kot_number' => (Kot::generateKotNumber($this->shopBranch) + 1),
            'order_id' => $order->id,
            'order_type_id' => $order->order_type_id,
            'token_number' => Kot::generateTokenNumber($this->shopBranch->id, $order->order_type_id),
            'note' => $this->orderNote,
            'transaction_id' => $transactionId
        ]);

        foreach ($this->orderItemList ?? [] as $key => $value) {
            $kotItem = KotItem::create([
                'kot_id' => $kot->id,
                'menu_item_id' => $this->orderItemVariation[$key]->menu_item_id ?? $this->orderItemList[$key]->id,
                'menu_item_variation_id' => isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null,
                'quantity' => $this->orderItemQty[$key],
                'transaction_id' => $transactionId,
                'note' => $this->itemNotes[$key] ?? null,
            ]);

            $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
            $kotItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
        }

        foreach ($this->orderItemList ?? [] as $key => $value) {
            $orderItem = OrderItem::create([
                'branch_id' => $this->shopBranch->id,
                'order_id' => $order->id,
                'menu_item_id' => isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id,
                'menu_item_variation_id' => isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null,
                'quantity' => $this->orderItemQty[$key],
                'price' => isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price,
                'amount' => $this->orderItemAmount[$key],
                'transaction_id' => $transactionId,
                'note' => $this->itemNotes[$key] ?? null,
                'tax_amount' => $this->orderItemTaxDetails[$key]['tax_amount'] ?? null,
                'tax_percentage' => $this->orderItemTaxDetails[$key]['tax_percent'] ?? null,
                'tax_breakup' => isset($this->orderItemTaxDetails[$key]['tax_breakup'])
                    ? json_encode($this->orderItemTaxDetails[$key]['tax_breakup'])
                    : null,
            ]);

            $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
            $orderItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
        }

        if ($this->taxMode === 'order') {
            foreach ($this->taxes ?? [] as $key => $value) {
                OrderTax::firstOrCreate([
                    'order_id' => $order->id,
                    'tax_id' => $value->id
                ]);
            }
        }

        if ($this->orderID) {
            $order->extraCharges()->detach();
        }

        foreach ($this->extraCharges ?? [] as $key => $value) {
            OrderCharge::create([
                'order_id' => $order->id,
                'charge_id' => $value->id
            ]);
        }

        $this->total = 0;
        $this->subTotal = 0;
        $this->totalTaxAmount = 0;

        foreach ($order->load('items')->items as $value) {
            $this->subTotal += $value->amount;
            $this->total += $value->amount;
        }

        $this->recalculateTaxTotals();

        if ($this->taxMode === 'item') {
            if ($this->restaurant->tax_inclusive) {
                $this->subTotal -= ($this->totalTaxAmount + $order?->total_tax_amount);
            } else {
                $this->total += $order?->total_tax_amount;
            }
        }

        foreach (($this->extraCharges ?? []) as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                $this->total += $charge->getAmount($this->subTotal);
            }
        }

        $this->total += (float) $this->deliveryFee ?: 0;
        $this->total += $order->tip_amount ?? 0;

        Order::where('id', $order->id)->update([
            'sub_total' => $this->subTotal,
            'total' => $this->total,
            'total_tax_amount' => $order->items->sum('tax_amount'),
            'tax_mode' => $this->taxMode,
        ]);

        event(new OrderUpdated($order, 'updated'));

        if (!is_null($this->tableID)) {
            $table->available_status = 'running';
            $table->saveQuietly();
        }

        $this->showPaymentModal = true;
        $this->paymentOrder = $order;

        cache()->forget('branch_' . $this->shopBranch->id . '_order_stats');

        return;
    }

    public function initiatePayment($id)
    {
        $total = round($this->total, 2);

        $payment = RazorpayPayment::create([
            'order_id' => $id,
            'amount' => $total
        ]);

        $orderData = [
            'amount' => ($total * 100),
            'currency' => $this->restaurant->currency->currency_code
        ];

        $apiKey = $this->restaurant->paymentGateways->razorpay_key;
        $secretKey = $this->restaurant->paymentGateways->razorpay_secret;

        $api  = new Api($apiKey, $secretKey);
        $razorpayOrder = $api->order->create($orderData);
        $payment->razorpay_order_id = $razorpayOrder->id;
        $payment->save();

        $this->dispatch('paymentInitiated', payment: $payment);
    }
    public function initiateFedapayPayment($id)
    {
        try {
            require_once base_path('vendor/fedapay/init.php');

            $paymentGateway = $this->restaurant->paymentGateways;
            $order = Order::with('customer')->findOrFail($id);

            $isSandbox = ($paymentGateway->fedapay_mode ?? 'sandbox') === 'sandbox';

            $secretKey = $isSandbox
                ? $paymentGateway->test_fedapay_secret_key
                : $paymentGateway->fedapay_secret_key;

            if (blank($secretKey)) {
                session()->flash('flash.banner', 'FedaPay credentials are not configured.');
                session()->flash('flash.bannerStyle', 'warning');
                return;
            }

            $user = $this->customer ?? $order->customer;

            if (! $user) {
                session()->flash('flash.banner', 'Customer information is required for FedaPay payment.');
                session()->flash('flash.bannerStyle', 'warning');
                return;
            }

            if (blank($user->email)) {
                session()->flash('flash.banner', 'Customer email is required for FedaPay payment.');
                session()->flash('flash.bannerStyle', 'warning');
                return;
            }

            Log::info('FedaPay initiation payload', [
                'order_id' => $order->id,
                'customer_id' => $user->id ?? null,
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
                'mode' => $isSandbox ? 'sandbox' : 'live',
            ]);

            \FedaPay\FedaPay::setApiKey($secretKey);
            \FedaPay\FedaPay::setEnvironment($isSandbox ? 'sandbox' : 'live');

            $transaction = \FedaPay\Transaction::create([
                'description' => 'Order Payment #' . ($order->formatted_order_number ?? $order->id),
                'amount' => (int) round($order->total),
                'currency' => [
                    'iso' => 'XOF',
                ],
                'callback_url' => route('fedapay.success', ['hash' => $this->restaurant->hash]),
                'customer' => [
                    'firstname' => $user->name ?? 'Customer',
                    'email' => $user->email,
                ],
                'custom_metadata' => [
                    'order_id' => $order->id,
                    'restaurant_hash' => $this->restaurant->hash,
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

            return redirect($token->url);
        } catch (\Throwable $e) {
            Log::error('FedaPay Payment Initiation Error: ' . $e->getMessage(), [
                'order_id' => $id,
                'restaurant_id' => $this->restaurant->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('flash.banner', 'FedaPay payment initiation failed.');
            session()->flash('flash.bannerStyle', 'danger');
        }
    }

    public function initiateStripePayment($id)
    {
        $payment = StripePayment::create([
            'order_id' => $id,
            'amount' => $this->total
        ]);

        $this->dispatch('stripePaymentInitiated', payment: $payment);
    }

    #[On('razorpayPaymentCompleted')]
    public function razorpayPaymentCompleted($razorpayPaymentID, $razorpayOrderID, $razorpaySignature)
    {
        $payment = RazorpayPayment::where('razorpay_order_id', $razorpayOrderID)
            ->where('payment_status', 'pending')
            ->first();

        if ($payment) {
            $payment->razorpay_payment_id = $razorpayPaymentID;
            $payment->payment_status = 'completed';
            $payment->payment_date = now()->toDateTimeString();
            $payment->razorpay_signature = $razorpaySignature;
            $payment->save();

            $order = Order::find($payment->order_id);
            $order->amount_paid = $this->total;
            $order->status = 'paid';
            $order->save();

            Payment::create([
                'order_id' => $payment->order_id,
                'branch_id' => $this->shopBranch->id,
                'payment_method' => 'razorpay',
                'amount' => $payment->amount,
                'transaction_id' => $razorpayPaymentID
            ]);

            $this->sendNotifications($order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', $payment->order->uuid));
        }
    }

    public function initiateFlutterwavePayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $apiSecret = $paymentGateway->flutterwave_secret;
            $amount = $this->total;
            $tx_ref = 'txn_' . time();

            $user = $this->customer ?? $this->restaurant;


            $data = [
                'tx_ref' => $tx_ref,
                'amount' => $amount,
                'currency' => $this->restaurant->currency->currency_code,
                'redirect_url' => route('flutterwave.success'),
                'payment_options' => 'card',
                'customer' => [
                    'email' => $user->email ?? 'no-email@example.com',
                    'name' => $user->name ?? 'Guest',
                    'phone_number' => $user->phone ?? '0000000000',
                ],
            ];
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiSecret",
                'Content-Type' => 'application/json'
            ])->post('https://api.flutterwave.com/v3/payments', $data);

            $responseData = $response->json();

            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                FlutterwavePayment::create([
                    'order_id' => $id,
                    'flutterwave_payment_id' => $tx_ref,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['link']);
            } else {
                return redirect()->route('flutterwave.failed')->withErrors(['error' => 'Payment initiation failed', 'message' => $responseData]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function initiatePaypalPayment($id)
    {
        $amount = $this->total;
        $currency = strtoupper($this->restaurant->currency->currency_code);

        $unsupportedCurrencies = ['INR'];
        if (in_array($currency, $unsupportedCurrencies)) {
            session()->flash('flash.banner', 'Currency not supported by PayPal.');
            session()->flash('flash.bannerStyle', 'warning');
            return redirect()->route('order_success', $id);
        }

        $clientId = $this->paymentGateway->paypal_client_id;
        $secret = $this->paymentGateway->paypal_secret;

        $paypalPayment = new PaypalPayment();
        $paypalPayment->order_id = $id;
        $paypalPayment->amount = $amount;
        $paypalPayment->payment_status = 'pending';
        $paypalPayment->save();

        $returnUrl = route('paypal.success');
        $cancelUrl = route('paypal.cancel');

        $paypalData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', '')
                ],
                'reference_id' => (string)$paypalPayment->id
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ]
        ];
        info('Paypal Data: ' . json_encode($paypalData));

        $auth = base64_encode("$clientId:$secret");

        $response = Http::withHeaders([
            'Authorization' => "Basic $auth",
            'Content-Type' => 'application/json'
        ])->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', $paypalData);

        if ($response->successful()) {
            $paypalResponse = $response->json();

            $paypalPayment->paypal_payment_id = $paypalResponse['id'];
            $paypalPayment->payment_status = 'pending';
            $paypalPayment->save();

            $approvalLink = collect($paypalResponse['links'])->firstWhere('rel', 'approve')['href'];
            return redirect($approvalLink);
        }
        $paypalPayment->payment_status = 'failed';
        $paypalPayment->save();

        return redirect()->route('paypal.cancel');
    }

    public function initiateEpayPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $amount = $this->total;
            $isSandbox = $paymentGateway->epay_mode === 'sandbox';

            $clientId = $isSandbox ? $paymentGateway->test_epay_client_id : $paymentGateway->epay_client_id;
            $clientSecret = $isSandbox ? $paymentGateway->test_epay_client_secret : $paymentGateway->epay_client_secret;
            $terminalId = $isSandbox ? $paymentGateway->test_epay_terminal_id : $paymentGateway->epay_terminal_id;

            if (!$clientId || !$clientSecret || !$terminalId) {
                session()->flash('flash.banner', 'Epay credentials are not configured.');
                session()->flash('flash.bannerStyle', 'warning');
                return redirect()->route('order_success', $id);
            }

            $order = Order::find($id);
            if (!$order) {
                session()->flash('flash.banner', 'Order not found.');
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->route('order_success', $id);
            }

            // Generate secret hash (random string for security)
            $secretHash = bin2hex(random_bytes(16));

            // Create payment record first to get unique ID
            $epayPayment = EpayPayment::create([
                'order_id' => $id,
                'amount' => $amount,
                'payment_status' => 'pending',
                'epay_secret_hash' => $secretHash,
            ]);

            // Generate unique invoice ID for THIS payment attempt - must be 6-15 digits
            // Use payment ID + timestamp to ensure uniqueness across all attempts
            // Format: payment_id (padded) + last 4 digits of timestamp = always unique
            $paymentIdStr = (string)$epayPayment->id;
            $timestampSuffix = substr((string)time(), -4); // Last 4 digits of timestamp
            $invoiceIdBase = $paymentIdStr . $timestampSuffix;

            // Ensure it's between 6-15 digits as per Epay requirements
            if (strlen($invoiceIdBase) < 6) {
                // Pad with zeros to reach minimum 6 digits
                $invoiceId = str_pad($invoiceIdBase, 6, '0', STR_PAD_LEFT);
            } elseif (strlen($invoiceIdBase) > 15) {
                // Truncate to 15 digits if too long
                $invoiceId = substr($invoiceIdBase, -15);
            } else {
                $invoiceId = $invoiceIdBase;
            }

            // Update payment record with invoice ID
            $epayPayment->epay_invoice_id = $invoiceId;
            $epayPayment->save();

            // Get access token with payment details - returns full token object
            $tokenResponse = $this->getEpayAccessToken($paymentGateway, $isSandbox, $invoiceId, $secretHash, $amount);
            if (!$tokenResponse || !isset($tokenResponse['access_token'])) {
                $epayPayment->payment_status = 'failed';
                $epayPayment->save();
                session()->flash('flash.banner', 'Failed to authenticate with Epay. Please try again.');
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->route('order_success', $id);
            }

            // Store full token response as JSON in payment record
            $epayPayment->epay_access_token = json_encode($tokenResponse);
            $epayPayment->save();

            // Store invoiceId in session for success/cancel callbacks (Epay doesn't always send it in redirect)
            session([
                'epay_invoice_id' => $invoiceId,
                'epay_order_id' => $id,
                'epay_payment_id' => $epayPayment->id,
            ]);

            // Reload payment with all relationships for JavaScript
            $epayPayment->load(['order.customer']);

            // Dispatch event to trigger payment directly on current page (like Razorpay)
            $this->dispatch('epayPaymentInitiated', payment: $epayPayment);
        } catch (\Exception $e) {
            Log::error('Epay Payment Initiation Error: ' . $e->getMessage());
            session()->flash('flash.banner', 'Payment initiation failed: ' . $e->getMessage());
            session()->flash('flash.bannerStyle', 'danger');
            return redirect()->route('order_success', $id);
        }
    }

    private function getEpayAccessToken($paymentGateway, $isSandbox, $invoiceId, $secretHash, $amount)
    {
        $clientId = $isSandbox ? $paymentGateway->test_epay_client_id : $paymentGateway->epay_client_id;
        $clientSecret = $isSandbox ? $paymentGateway->test_epay_client_secret : $paymentGateway->epay_client_secret;
        $terminalId = $isSandbox ? $paymentGateway->test_epay_terminal_id : $paymentGateway->epay_terminal_id;

        if (!$clientId || !$clientSecret || !$terminalId) {
            session()->flash('flash.banner', 'Epay credentials are not configured.');
            session()->flash('flash.bannerStyle', 'warning');
            return null;
        }

        // Correct token URL according to documentation
        $tokenUrl = $isSandbox
            ? 'https://test-epay-oauth.epayment.kz/oauth2/token'
            : 'https://epay-oauth.homebank.kz/oauth2/token';

        $currency = strtoupper($this->restaurant->currency->currency_code);
        $postLink = route('epay.webhook', ['hash' => $this->restaurant->hash]);
        $failurePostLink = route('epay.webhook', ['hash' => $this->restaurant->hash]);

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'scope' => 'payment',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'invoiceID' => $invoiceId,
            'secret_hash' => $secretHash,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'terminal' => $terminalId,
            'postLink' => $postLink,
            'failurePostLink' => $failurePostLink,
        ]);

        if ($response->successful()) {
            $tokenData = $response->json();
            // Return the complete token object, not just access_token
            // The auth field in halyk.pay() expects the full token response
            return $tokenData;
        }

        $errorResponse = $response->json();
        Log::error('Epay Token Error: ' . json_encode($errorResponse));
        session()->flash('flash.banner', 'Failed to authenticate with Epay. Please check your credentials.');
        session()->flash('flash.bannerStyle', 'danger');
        return null;
    }

    function generateSignature($data, $passPhrase)
    {
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }

        return md5($getString);
    }

    public function initiatePayfastPayment($id)
    {
        $paymentGateway = $this->restaurant->paymentGateways;
        $isSandbox = $paymentGateway->payfast_mode === 'sandbox';
        $merchantId = $isSandbox ? $paymentGateway->test_payfast_merchant_id : $paymentGateway->payfast_merchant_id;
        $merchantKey = $isSandbox ? $paymentGateway->test_payfast_merchant_key : $paymentGateway->payfast_merchant_key;
        $passphrase = $isSandbox ? $paymentGateway->test_payfast_passphrase : $paymentGateway->payfast_passphrase;
        $amount = number_format($this->total, 2, '.', '');
        $itemName = "Order Payment #$id";
        $reference = 'pf_' . time();
        $data = [
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => route('payfast.success', ['reference' => $reference]),
            'cancel_url' => route('payfast.failed', ['reference' => $reference]),
            'notify_url' => route('payfast.notify', ['company' => $this->restaurant->hash, 'reference' => $reference]),

            'name_first' => auth()->user()->name,
            'email_address' => auth()->user()->email,
            'm_payment_id' => $id, // Your internal ID
            'amount' => $amount,
            'item_name' => $itemName,
        ];


        $signature = $this->generateSignature($data, $passphrase);
        $data['signature'] = $signature;

        AdminPayfastPayment::create([
            'order_id' => $id,
            'payfast_payment_id' => $reference,
            'amount' => $amount,
            'payment_status' => 'pending',
        ]);

        $payfastBaseUrl = $isSandbox ? 'https://sandbox.payfast.co.za/eng/process' : 'https://api.payfast.co.za/eng/process';
        $redirectUrl = $payfastBaseUrl . '?' . http_build_query($data);
        return redirect($redirectUrl);
    }

    public function initiatePaystackPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;

            $secretKey = $paymentGateway->paystack_secret_data;
            $user = auth()->user();
            $amount = $this->total;
            $reference = 'psk_' . time();
            $data = [
                'reference' => $reference,
                'amount' => (int)($amount * 100), // Paystack expects amount in kobo
                'email' => $user->email,
                'currency' => $this->restaurant->currency->currency_code,
                'callback_url' => route('paystack.success'),
                'metadata' => [
                    'cancel_action' => route('paystack.failed', ['reference' => $reference])
                ]

            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer $secretKey",
                'Content-Type' => 'application/json'
            ])->post('https://api.paystack.co/transaction/initialize', $data);

            $responseData = $response->json();
            if (isset($responseData['status']) && $responseData['status'] === true) {
                AdminPaystackPayment::create([
                    'order_id' => $id,
                    'paystack_payment_id' => $reference,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['authorization_url']);
            } else {

                session()->flash('error', 'Payment initiation failed.');
                return redirect()->route('paystack.failed');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function initiateXenditPayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $secretKey = $paymentGateway->xendit_secret_key;
            $amount = $this->total;
            $externalId = 'xendit_' . time();

            $user = $this->customer ?? auth()->user();

            $data = [
                'external_id' => $externalId,
                'amount' => $amount,
                'description' => 'Order Payment #' . $id,
                'currency' => 'PHP',
                'success_redirect_url' => route('xendit.success', ['external' => $externalId]),
                'failure_redirect_url' => route('xendit.failed'),
                'payment_methods' => ['CREDIT_CARD', 'BCA', 'BNI', 'BSI', 'BRI', 'MANDIRI', 'OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY'],
                'should_send_email' => true,
                'customer' => [
                    'given_names' => $user->name ?? 'Guest',
                    'email' => $user->email ?? 'guest@example.com',
                    'mobile_number' => $user->phone ?? '+6281234567890',
                ],
                'items' => [
                    [
                        'name' => 'Order #' . $id,
                        'quantity' => 1,
                        'price' => $amount,
                        'category' => 'FOOD_AND_BEVERAGE'
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $data);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                XenditPayment::create([
                    'order_id' => $id,
                    'xendit_payment_id' => $externalId,
                    'xendit_invoice_id' => $responseData['id'],
                    'xendit_external_id' => $externalId,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['invoice_url']);
            } else {
                session()->flash('error', 'Xendit payment initiation failed: ' . ($responseData['message'] ?? 'Unknown error'));
                return redirect()->route('xendit.failed');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Xendit payment error: ' . $e->getMessage());
            return redirect()->route('xendit.failed');
        }
    }

    public function hidePaymentModal()
    {

        $this->showPaymentModal = false;
        Order::where('id', $this->paymentOrder->id)->where('status', 'draft')->delete();

        Kot::where('transaction_id', session('transaction_id'))->delete();
        KotItem::where('transaction_id', session('transaction_id'))->delete();
        OrderItem::where('transaction_id', session('transaction_id'))->delete();

        session()->forget('transaction_id');

        $this->paymentOrder = null;
    }

    public function sendNotifications($order)
    {
        NewOrderCreated::dispatch($order);

        SendNewOrderReceived::dispatch($order);
        if ($order->customer_id) {
            try {
                $order->customer->notify(new SendOrderBill($order));
            } catch (\Exception $e) {
                Log::error('Error sending order bill email: ' . $e->getMessage());
            }
        }
    }

    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function togglePaymenntDetail()
    {
        $this->showPaymentDetail = !$this->showPaymentDetail;
    }

    #[On('closeModifiersModal')]
    public function closeModifiersModal()
    {
        $this->selectedModifierItem = null;
        $this->showModifiersModal = false;
    }

    #[On('setPosModifier')]
    public function setPosModifier($modifierIds)
    {
        // Check radius restriction before adding items with modifiers
        if (!$this->checkRadiusRestriction()) {
            if (empty($this->addressLat) || empty($this->addressLng)) {
                $this->showLocationModal = true;
                $this->alert('error', __('app.locationAccessRequired'), [
                    'toast' => false,
                    'position' => 'center',
                ]);
            } else {
                $this->alert('error', __('app.outsideAllowedAreaMeters', ['meters' => $this->restaurant->qr_order_radius_meters]), [
                    'toast' => false,
                    'position' => 'center',
                    'showCancelButton' => true,
                    'cancelButtonText' => __('app.close')
                ]);
            }
            return;
        }

        $this->showModifiersModal = false;

        $sortNumber = Str::of(implode('', Arr::flatten($modifierIds)))
            ->split(1)->sort()->implode('');

        $keyId = $this->selectedModifierItem . '-' . $sortNumber;

        if (isset(explode('_', $this->selectedModifierItem)[1])) {
            $menuItemVariation = MenuItemVariation::find(explode('_', $this->selectedModifierItem)[1]);

            // Set price context on variation
            if ($this->orderTypeId) {
                $menuItemVariation->setPriceContext($this->orderTypeId, null);
            }

            $this->orderItemVariation[$keyId] = $menuItemVariation;
            $this->selectedModifierItem = explode('_', $this->selectedModifierItem)[0];
            $this->orderItemAmount[$keyId] = 1 * ($this->orderItemVariation[$keyId]->price ?? $this->orderItemList[$keyId]->price);
        }

        $this->cartItemQty[$keyId] = ($this->cartItemQty[$keyId] ?? 0) + 1;
        $this->itemModifiersSelected[$keyId] = Arr::flatten($modifierIds);

        // Set price context on modifiers before calculating total
        $modifierTotal = 0;
        foreach ($this->itemModifiersSelected[$keyId] ?? [] as $modifierId) {
            $modifier = ModifierOption::find($modifierId);
            if ($modifier) {
                if ($this->orderTypeId) {
                    $modifier->setPriceContext($this->orderTypeId, null);
                }
                $modifierTotal += $modifier->price;
            }
        }

        $this->orderItemModifiersPrice[$keyId] = $modifierTotal;

        $this->syncCart($keyId);
    }

    public function getModifierOptionsProperty()
    {
        return ModifierOption::whereIn('id', collect($this->itemModifiersSelected)->flatten()->all())->get()->keyBy('id');
    }

    public function showItemDetail($id)
    {
        $this->selectedItem = MenuItem::find($id);
        $this->showItemDetailModal = true;
    }

    #[On('selectedDeliveryDetails')]
    public function handleSelectedDeliveryDetails($details)
    {
        $this->addressLat = $details['lat'] ?? null;
        $this->addressLng = $details['lng'] ?? null;
        $this->deliveryAddress = $details['address'] ?? null;
        $this->deliveryFee = $details['deliveryFee'] ?? null;
        $this->etaMin = $details['eta_min'];
        $this->etaMax = $details['eta_max'];

        $this->calculateMaxPreparationTime();
        $this->calculateTotal();
        $this->showDeliveryAddressModal = false;
    }

    public function calculateMaxPreparationTime()
    {
        $this->maxPreparationTime = !empty($this->orderItemList) ? max(array_map(fn($item) => $item->preparation_time ?? 0, $this->orderItemList)) : 0;
    }

    // Centralized tax calculation methods to eliminate code duplication
    private function recalculateTaxTotals()
    {
        $this->totalTaxAmount = 0;

        if ($this->taxMode === 'order') {
            // Order-based taxation
            foreach ($this->taxes ?? [] as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $this->subTotal;
                $this->totalTaxAmount += $taxAmount;
                $this->total += $taxAmount;
            }
        } elseif ($this->taxMode === 'item' && !empty($this->orderItemAmount)) {
            // Item-based taxation - taxes are already calculated in calculateTotal()
            $totalInclusiveTax = 0;
            $totalExclusiveTax = 0;
            $isInclusive = $this->restaurant->tax_inclusive ?? false;

            // Calculate total tax amounts
            foreach ($this->orderItemTaxDetails ?? [] as $itemTaxDetail) {
                $taxAmount = $itemTaxDetail['tax_amount'] ?? 0;

                if ($isInclusive) {
                    $totalInclusiveTax += $taxAmount;
                } else {
                    $totalExclusiveTax += $taxAmount;
                }
            }

            $this->totalTaxAmount = $totalInclusiveTax + $totalExclusiveTax;

            // For exclusive taxes, add them to the total
            // (Inclusive taxes are already included in the item prices)
            if ($totalExclusiveTax > 0) {
                $this->total += $totalExclusiveTax;
            }
        }
    }

    public function updateOrderItemTaxDetails()
    {
        $this->orderItemTaxDetails = [];

        if ($this->taxMode !== 'item' || !is_array($this->orderItemAmount)) {
            return;
        }

        foreach ($this->orderItemAmount as $key => $value) {
            $menuItem = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menuItem : $this->orderItemList[$key];

            // Set price context before using prices
            if ($this->orderTypeId) {
                $menuItem->setPriceContext($this->orderTypeId, null);
                if (isset($this->orderItemVariation[$key])) {
                    $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, null);
                }
            }

            $qty = $this->orderItemQty[$key] ?? 1;
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $menuItem->price;
            $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
            $itemPriceWithModifiers = $basePrice + $modifierPrice;
            $taxes = $menuItem->taxes ?? collect();
            $isInclusive = $this->restaurant->tax_inclusive;
            $taxResult = MenuItem::calculateItemTaxes($itemPriceWithModifiers, $taxes, $isInclusive);
            $this->orderItemTaxDetails[$key] = [
                'tax_amount' => $taxResult['tax_amount'] * $qty,
                'tax_percent' => $taxResult['tax_percentage'],
                'tax_breakup' => $taxResult['tax_breakdown'],
                'tax_type' => $taxResult['inclusive'],
                'base_price' => $itemPriceWithModifiers,
                'display_price' => $isInclusive ? ($itemPriceWithModifiers - ($taxResult['tax_amount'] ?? 0)) : $itemPriceWithModifiers,
                'qty' => $qty,
            ];
        }
    }

    /**
     * Get the display price for an item (base price without tax for inclusive items)
     */
    public function getItemDisplayPrice($key)
    {
        if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
            return $this->orderItemTaxDetails[$key]['display_price'] ?? 0;
        }

        // Set price context before using price
        if ($this->orderTypeId) {
            if (isset($this->orderItemVariation[$key])) {
                $this->orderItemVariation[$key]->setPriceContext($this->orderTypeId, null);
            }
            if (isset($this->orderItemList[$key])) {
                $this->orderItemList[$key]->setPriceContext($this->orderTypeId, null);
            }
        }

        // For non-item tax mode, return the original price
        $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $this->orderItemList[$key]->price;
        $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
        return $basePrice + $modifierPrice;
    }

    #[Computed]
    public function getMenuItemsProperty()
    {
        $locale = session('locale', app()->getLocale());

        $query = MenuItem::select('menu_items.*', 'item_categories.category_name')
            ->join('item_categories', 'menu_items.item_category_id', '=', 'item_categories.id')
            ->where('menu_items.branch_id', $this->shopBranch->id)
            ->where('show_on_customer_site', true);

        if (!empty($this->filterCategories)) {
            $query->where('menu_items.item_category_id', $this->filterCategories);
        }

        if (!empty($this->menuId)) {
            $query->where('menu_items.menu_id', $this->menuId);
        }

        if ($this->showVeg == 1) {
            $query->where('menu_items.type', 'veg');
        }

        if ($this->showHalal == 1) {
            $query->where('menu_items.type', 'halal');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('translations', function ($q) {
                        $q->where('item_name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply limit BEFORE loading heavy relationships
        $items = $query->orderBy('item_categories.sort_order')
            ->orderBy('menu_items.item_category_id')
            ->orderBy('menu_items.sort_order')
            ->limit($this->menuItemsLoaded)
            ->get();

        // Load relationships and counts only on the limited items
        $items->load('category');
        $items->loadCount(['variations', 'modifierGroups']);

        // Group by category
        $groupedItems = $items->groupBy(function ($item) use ($locale) {
            return $item->category->getTranslation('category_name', $locale);
        });

        // Set price context on menu items in the query results
        if ($this->orderTypeId) {
            foreach ($groupedItems as $categoryItems) {
                foreach ($categoryItems as $item) {
                    $item->setPriceContext($this->orderTypeId, null);
                    // Set price context on variations
                    if ($item->relationLoaded('variations')) {
                        foreach ($item->variations as $variation) {
                            $variation->setPriceContext($this->orderTypeId, null);
                        }
                    }
                }
            }
        }

        return $groupedItems;
    }

    #[Computed]
    public function getTotalMenuItemsCountProperty()
    {
        $query = MenuItem::where('branch_id', $this->shopBranch->id)
            ->where('show_on_customer_site', true);

        if (!empty($this->filterCategories)) {
            $query->where('item_category_id', $this->filterCategories);
        }

        if (!empty($this->menuId)) {
            $query->where('menu_id', $this->menuId);
        }

        if ($this->showVeg == 1) {
            $query->where('type', 'veg');
        }

        if ($this->showHalal == 1) {
            $query->where('type', 'halal');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('translations', function ($q) {
                        $q->where('item_name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->count();
    }

    #[Computed]
    public function getAllItemsLoadedProperty()
    {
        return $this->menuItemsLoaded >= $this->totalMenuItemsCount;
    }

    public function loadMoreMenuItems()
    {
        if ($this->allItemsLoaded) {
            return;
        }

        $this->menuItemsLoaded += $this->menuItemsPerLoad;
    }

    #[Computed]
    public function getCategoryListProperty()
    {
        return ItemCategory::withoutGlobalScopes()
            ->withCount(['items as items_count' => function ($query) {
                $query->where('menu_items.is_available', 1);

                if (!empty($this->menuId)) {
                    $query->where('menu_items.menu_id', $this->menuId);
                }

                if ($this->showVeg == 1) {
                    $query->where('menu_items.type', 'veg');
                }

                if ($this->showHalal == 1) {
                    $query->where('menu_items.type', 'halal');
                }
            }])
            ->where('branch_id', $this->shopBranch->id)
            ->having('items_count', '>', 0)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function getMenuListProperty()
    {
        return Menu::withoutGlobalScopes()
            ->where('branch_id', $this->shopBranch->id)
            ->withCount('items')
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function getOrderTypesProperty()
    {
        return OrderType::where('branch_id', $this->shopBranch->id)
            ->where('is_active', true)
            ->where('enable_from_customer_site', true)
            ->get();
    }

    public function render()
    {
        return view('livewire.shop.cart', [
            'orderTypes' => $this->orderTypes,
            'phonecodes' => $this->filteredPhoneCodes,
        ]);
    }


    public function printKot($order, $kot = null, $kotIds = [])
    {
        // Check if the 'kitchen' package is enabled
        if (in_array('Kitchen', restaurant_modules()) && in_array('kitchen', custom_module_plugins())) {
            // Get all KOTs for this order (created above)

            if ($kotIds) {
                $kots = $order->kot()->whereIn('id', $kotIds)->with('items')->get();
            } else {
                $kots = $order->kot()->with('items')->get();
            }

            foreach ($kots as $kot) {
                $kotPlaceItems = [];

                foreach ($kot->items as $kotItem) {
                    if ($kotItem->menuItem && $kotItem->menuItem->kot_place_id) {
                        $kotPlaceId = $kotItem->menuItem->kot_place_id;

                        if (!isset($kotPlaceItems[$kotPlaceId])) {
                            $kotPlaceItems[$kotPlaceId] = [];
                        }

                        $kotPlaceItems[$kotPlaceId][] = $kotItem;
                    }
                }

                // Get the kot places and their printer settings
                $kotPlaceIds = array_keys($kotPlaceItems);
                $kotPlaces = KotPlace::with('printerSetting')->whereIn('id', $kotPlaceIds)->get();

                foreach ($kotPlaces as $kotPlace) {
                    $printerSetting = $kotPlace->printerSetting;

                    if ($printerSetting && $printerSetting->is_active == 0) {
                        $printerSetting = Printer::where('is_default', true)->first();
                    }

                    // If no printer is set, fallback to print URL dispatch
                    if (!$printerSetting) {
                        $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                        $this->dispatch('print_location', $url);
                        continue;
                    }

                    try {
                        switch ($printerSetting->printing_choice) {
                            case 'directPrint':
                                $this->handleKotPrint($kot->id, $kotPlace->id);
                                break;
                            default:
                        }
                    } catch (\Throwable $e) {
                        $this->alert('error', __('messages.printerNotConnected') . ' ' . $e->getMessage(), [
                            'toast' => true,
                            'position' => 'top-end',
                            'showCancelButton' => false,
                            'cancelButtonText' => __('app.close')
                        ]);
                    }
                }
            }
        } else {
            $kotPlace = KotPlace::where('is_default', 1)->first();
            $printerSetting = $kotPlace->printerSetting;

            // Get the KOT for this order
            $kot = $kot ?? $order->kot()->first();

            // If no printer is set, fallback to print URL dispatch
            if (!$printerSetting) {
                $url = route('kot.print', [$kot->id, $kotPlace?->id]);
                $this->dispatch('print_location', $url);
            }

            try {
                switch ($printerSetting->printing_choice) {
                    case 'directPrint':
                        $this->handleKotPrint($kot->id, $kotPlace->id);
                        break;

                    default:
                }
            } catch (\Throwable $e) {
                $this->alert('error', __('messages.printerNotConnected') . ' ' . $e->getMessage(), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
            }
        }
    }

}
