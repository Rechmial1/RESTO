<template>
    <div>
        <!-- Offline Indicator -->
        <OfflineIndicator
            :is-online="isOnline"
            :pending-operations="pendingOperations"
            @clear-cache="handleShowClearCacheModal"
        />

        <div class="flex-grow lg:flex h-auto pt-6">
            <!-- Menu Panel -->
            <MenuPanel
                :search="search"
                :menu-id="menuId"
                :filter-categories="filterCategories"
                :menus="menus"
                :categories="categories"
                :items="menuItems"
                :currency-symbol="currencySymbol"
                @update:search="search = $event"
                @update:menuId="menuId = $event"
                @update:filterCategories="filterCategories = $event"
                @add-to-cart="handleAddToCart"
                @reset="handleReset"
            />

            <!-- Order Panel -->
            <OrderPanel
                :order-type="orderType"
                :order-number="orderNumber"
                :current-table="currentTable"
                :pax="pax"
                :waiter-id="waiterId"
                :waiters="waiters"
                :cart-items="cartItems"
                :taxes="taxes"
                :saving="saving"
                :extra-charges="extraCharges"
                :discount-amount="discountAmount"
                :discount-type="discountType"
                :discount-value="discountValue"
                :is-online="isOnline"
                :total-tax-amount="totalTaxAmount"
                :is-inclusive="false"
                :currency-symbol="currencySymbol"
                @update:orderType="orderType = $event"
                @show-add-customer="showAddCustomerModal = true"
                @select-table="handleSelectTable"
                @open-order="handleOpenOrder"
                @update:pax="pax = $event"
                @update:waiterId="waiterId = $event"
                @add-note="handleAddNote"
                @increase-quantity="handleIncreaseQuantity"
                @decrease-quantity="handleDecreaseQuantity"
                @remove-item="handleRemoveItem"
                @save-order="handleSaveOrder"
                @update:extraCharges="extraCharges = $event"
                @apply-discount="handleApplyDiscount"
                @remove-discount="handleRemoveDiscount"
                :order="order"
            />
        </div>

        <!-- Modals -->
        <ReservationModal
            :show="showReservationModal"
            :reservation="reservation"
            @close="showReservationModal = false"
            @confirm-same="handleConfirmSameCustomer"
            @confirm-different="handleConfirmDifferentCustomer"
        />

        <TableChangeModal
            :show="showTableChangeConfirmationModal"
            :current-table="currentTable"
            :new-table="newTable"
            @close="showTableChangeConfirmationModal = false"
            @confirm="handleConfirmTableChange"
        />

        <AddCustomerModal
            :show="showAddCustomerModal"
            :customer="customer"
            @close="showAddCustomerModal = false"
            @save="handleSaveCustomer"
        />

        <AddNoteModal
            :show="showAddNoteModal"
            :note="orderNote"
            @close="showAddNoteModal = false"
            @save="handleSaveNote"
        />

        <!-- Clear Cache Confirmation Modal -->
        <div
            v-if="showClearCacheModal"
            class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
            @click.self="showClearCacheModal = false"
        >
            <!-- Backdrop -->
            <div
                class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75"
                @click="showClearCacheModal = false"
            ></div>

            <!-- Modal Content -->
            <div
                class="mb-6 bg-white dark:bg-gray-900 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-md sm:mx-auto"
            >
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                >
                    <h3
                        class="text-lg font-medium text-gray-900 dark:text-white"
                    >
                        Clear Cache
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Are you sure you want to clear all cached menu data and
                        reset the order panel? This will:
                    </p>
                    <ul
                        class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 mb-4 space-y-1"
                    >
                        <li>
                            Clear local cache ({{
                                cacheInfo?.menus || 0
                            }}
                            Menus, {{ cacheInfo?.categories || 0 }} Categories,
                            {{ cacheInfo?.menuItems || 0 }} Menu Items,
                            {{ cacheInfo?.waiters || 0 }} Waiters)
                        </li>
                        <li>
                            Clear all OrderPanel data (cart, customer, notes,
                            discounts, etc.)
                        </li>
                        <li>Fetch latest records from database</li>
                    </ul>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mb-4">
                        <strong>Note:</strong> This only works in online mode.
                        Your pending orders will not be affected. All cart items
                        and order data will be reset. Data will be refreshed
                        from the server.
                    </p>
                    <div
                        v-if="!isOnline"
                        class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md"
                    >
                        <p class="text-xs text-yellow-800 dark:text-yellow-200">
                            ⚠️ You are currently offline. This feature only
                            works when online.
                        </p>
                    </div>
                </div>
                <div
                    class="px-6 py-4 bg-gray-100 dark:bg-gray-800 flex justify-end gap-3"
                >
                    <button
                        @click="showClearCacheModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Cancel
                    </button>
                    <button
                        @click="handleClearCache"
                        :disabled="!isOnline"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Clear Cache
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import MenuPanel from "./components/pos/MenuPanel.vue";
import OrderPanel from "./components/pos/OrderPanel.vue";
import ReservationModal from "./components/pos/ReservationModal.vue";
import TableChangeModal from "./components/pos/TableChangeModal.vue";
import AddCustomerModal from "./components/pos/AddCustomerModal.vue";
import AddNoteModal from "./components/pos/AddNoteModal.vue";
import OfflineIndicator from "./components/pos/OfflineIndicator.vue";
import { useOfflineMode } from "./composables/useOfflineMode.js";
import {
    useCacheStorage,
    getCacheInfo,
} from "./composables/useCacheStorage.js";

// Offline mode setup
const {
    isOnline,
    pendingOperations,
    queueOperation,
    saveCart: saveCartToStorage,
    loadCart: loadCartFromStorage,
    saveCustomer: saveCustomerToStorage,
    loadCustomer: loadCustomerFromStorage,
    syncPendingOperations,
    offlineApiCall,
} = useOfflineMode();

// Cache storage setup
const {
    loadMenus,
    loadCategories,
    loadMenuItems,
    loadWaiters,
    saveMenus,
    saveCategories,
    saveMenuItems,
    saveWaiters,
    clearCache,
    cacheInfo: cacheInfoRef,
    refreshCacheInfo,
} = useCacheStorage();

// Local reactive cache info for modal
const cacheInfo = ref(getCacheInfo());

// Get route parameters from URL if needed
const getUrlParams = () => {
    const pathname = window.location.pathname;
    const pathParts = pathname.split("/").filter((p) => p);

    // Check if we're on order or kot route
    // Routes: /posvue/order/{id} or /posvue/kot/{id}
    const orderIndex = pathParts.indexOf("order");
    const kotIndex = pathParts.indexOf("kot");
    const routeIndex = orderIndex !== -1 ? orderIndex : kotIndex;

    return {
        orderId:
            routeIndex !== -1 && pathParts[routeIndex + 1]
                ? pathParts[routeIndex + 1]
                : null,
    };
};

const params = getUrlParams();
const orderId = ref(params.orderId);
console.log("orderId:", orderId.value);

// State
const search = ref("");
const menuId = ref(null);
const filterCategories = ref(null);

// Menu data (to be fetched from API)
const menus = ref([]);
const categories = ref([]);
const menuItems = ref([]);
const availableTaxes = ref([]);
const restaurant = ref(null);
const currencySymbol = ref("$");

// Order data
const orderType = ref("Dine In");
const orderNumber = ref("");
const pax = ref(1);
const waiterId = ref("");
const waiters = ref([]);
const cartItems = ref([]);
const taxes = ref([]);
const saving = ref(false);
const extraCharges = ref([]);
const discountAmount = ref(0);
const discountType = ref("");
const discountValue = ref(0);
const order = ref(null);
// Modal state
const showReservationModal = ref(false);
const showTableChangeConfirmationModal = ref(false);
const showAddCustomerModal = ref(false);
const reservation = ref({
    customerName: null,
    time: null,
});
const customer = ref({
    name: "",
    email: "",
    phone: "",
    phone_code: "",
    address: "",
});
const currentTable = ref("");
const currentTableId = ref(null);
const newTable = ref("");
const newTableId = ref(null);
const showClearCacheModal = ref(false);
const showAddNoteModal = ref(false);
const orderNote = ref("");

// Play beep sound when item is added to cart
const playBeepSound = () => {
    try {
        const audio = new Audio("/sound/sound_beep-29.mp3");
        audio.volume = 0.5;
        audio.play().catch((error) => {
            console.log("Audio play failed:", error);
        });
    } catch (error) {
        console.log("Error playing beep sound:", error);
    }
};

// Methods
const handleAddToCart = async (itemId, variantId = 0, modifierId = 0) => {
    // TODO: Implement API call to add items to cart
    console.log("addCartItems:", itemId, variantId, modifierId);

    // Example: Find item and add to cart
    const item = menuItems.value.find((i) => i.id === itemId);
    console.log("Found item:", item);

    if (item) {
        const existingItem = cartItems.value.find(
            (ci) =>
                ci.id === itemId &&
                ci.variant_id === variantId &&
                ci.modifier_id === modifierId
        );
        if (existingItem) {
            existingItem.quantity++;
            console.log("Updated existing item:", existingItem);
        } else {
            const newCartItem = {
                id: itemId,
                name: item.item_name || item.name || "Unknown Item",
                price: item.price || item.contextual_price || 0,
                quantity: 1,
                variant_id: variantId,
                modifier_id: modifierId,
            };
            cartItems.value.push(newCartItem);
            console.log("Added new cart item:", newCartItem);
            console.log("All cart items:", cartItems.value);
        }
        // Save cart to localStorage
        saveCartToStorage(cartItems.value);

        // Play beep sound when item is added to cart
        playBeepSound();
    } else {
        console.error("Item not found with id:", itemId);
        console.log("Available menu items:", menuItems.value);
    }
};

const handleReset = () => {
    search.value = "";
    menuId.value = null;
    filterCategories.value = null;
};

const handleChangeOrderType = () => {
    // TODO: Implement order type change
    console.log("changeOrderType");
};

const handleSelectTable = (table) => {
    const selectedTableCode = table.table_code;
    const selectedTableId = table.id;

    // Show confirmation modal if there's an existing table that's different
    if (currentTable.value && currentTable.value !== selectedTableCode) {
        newTable.value = selectedTableCode;
        newTableId.value = selectedTableId;
        showTableChangeConfirmationModal.value = true;
    } else {
        // Just update the table directly
        currentTable.value = selectedTableCode;
        currentTableId.value = selectedTableId;
    }
};

const handleOpenOrder = (selectedOrderId) => {
    if (!selectedOrderId) {
        return;
    }

    window.location.href = `/posvue/order/${selectedOrderId}`;
};

const handleAddNote = (noteData) => {
    // If noteData is an object with id and note, it's for a cart item
    if (noteData && typeof noteData === "object" && noteData.id) {
        // Find the cart item and update its note
        const cartItem = cartItems.value.find(
            (item) => item.id === noteData.id
        );
        if (cartItem) {
            cartItem.note = noteData.note || "";
            // Save cart to localStorage
            saveCartToStorage(cartItems.value);
            console.log("Note added to cart item:", cartItem.id, cartItem.note);
        }
    } else {
        // Otherwise, it's for the order note (the button at the top)
        showAddNoteModal.value = true;
    }
};

const handleSaveNote = (note) => {
    orderNote.value = note;
    console.log("Order note saved:", note);
};

const handleIncreaseQuantity = (itemId) => {
    const item = cartItems.value.find((i) => i.id === itemId);
    if (item) {
        item.quantity++;
        // Save cart to localStorage
        saveCartToStorage(cartItems.value);
    }
};

const handleDecreaseQuantity = (itemId) => {
    const item = cartItems.value.find((i) => i.id === itemId);
    if (item && item.quantity > 1) {
        item.quantity--;
    } else if (item) {
        cartItems.value = cartItems.value.filter((i) => i.id !== itemId);
    }
    // Save cart to localStorage
    saveCartToStorage(cartItems.value);
};

const handleRemoveItem = (itemId) => {
    cartItems.value = cartItems.value.filter((i) => i.id !== itemId);
    // Save cart to localStorage
    saveCartToStorage(cartItems.value);
};

const handleApplyDiscount = (discountData) => {
    discountType.value = discountData.type;
    discountValue.value = discountData.value;
    calculateDiscountAmount();
};

const calculateDiscountAmount = () => {
    if (discountType.value === "fixed") {
        // Fixed amount discount
        discountAmount.value = discountValue.value;
    } else if (discountType.value === "percent") {
        // Percentage discount - calculate from subtotal
        const subTotal = cartItems.value.reduce(
            (sum, item) => sum + (item.price || 0) * (item.quantity || 1),
            0
        );
        discountAmount.value = (subTotal * discountValue.value) / 100;
    }
};

// Calculate total tax amount
const totalTaxAmount = computed(() => {
    return taxes.value.reduce((sum, tax) => sum + (tax.amount || 0), 0);
});

// Calculate taxes based on subtotal (after discount)
const calculateTaxes = () => {
    if (availableTaxes.value.length === 0) {
        taxes.value = [];
        return;
    }

    // Calculate subtotal from cart items
    const subTotal = cartItems.value.reduce(
        (sum, item) => sum + (item.price || 0) * (item.quantity || 1),
        0
    );

    // Calculate subtotal after discount
    const subTotalAfterDiscount = subTotal - discountAmount.value;

    // Calculate tax amounts for each tax
    const calculatedTaxes = availableTaxes.value.map((tax) => {
        const taxPercent = parseFloat(tax.tax_percent) || 0;
        const taxAmount = (subTotalAfterDiscount * taxPercent) / 100;

        return {
            id: tax.id,
            name: tax.tax_name || tax.name,
            tax_name: tax.tax_name || tax.name,
            rate: taxPercent,
            tax_percent: taxPercent,
            amount: taxAmount,
        };
    });

    taxes.value = calculatedTaxes;
};

// Recalculate percentage discount when cart items change
watch(
    cartItems,
    () => {
        if (discountType.value === "percent" && discountValue.value > 0) {
            calculateDiscountAmount();
        }
        calculateTaxes();
    },
    { deep: true }
);

// Recalculate taxes when discount changes
watch(
    [discountAmount, availableTaxes],
    () => {
        calculateTaxes();
    },
    { deep: true }
);

const handleRemoveDiscount = () => {
    discountAmount.value = 0;
    discountType.value = "";
    discountValue.value = 0;
};

// Save order number to localStorage
const saveOrderNumberToStorage = (orderNum) => {
    try {
        if (orderNum) {
            localStorage.setItem("pos_last_order_number", orderNum);
            console.log("Saved order number to localStorage:", orderNum);
        }
    } catch (error) {
        console.error("Error saving order number to localStorage:", error);
    }
};

// Load last order number from localStorage
const loadOrderNumberFromStorage = () => {
    try {
        const saved = localStorage.getItem("pos_last_order_number");
        return saved || null;
    } catch (error) {
        console.error("Error loading order number from localStorage:", error);
        return null;
    }
};

// Extract last numeric value and its format from formatted order number
const extractLastNumericSegment = (formattedNumber) => {
    if (!formattedNumber) return null;

    // Remove any existing "(Offline)" suffix for processing
    const cleanNumber = formattedNumber.replace(/\s*\(Offline\)\s*$/, "");

    // Find the last sequence of digits (could be padded like 001, 023, etc.)
    // This regex finds the last occurrence of one or more digits
    const matches = cleanNumber.match(/(\d+)(?=[^\d]*$)/);

    if (matches && matches.length > 0) {
        const lastDigits = matches[1];
        const numericValue = parseInt(lastDigits, 10);
        const digitLength = lastDigits.length;
        const prefix = cleanNumber.substring(0, matches.index);
        const suffix = cleanNumber.substring(matches.index + lastDigits.length);

        return {
            numericValue,
            digitLength,
            prefix,
            suffix,
            fullNumber: cleanNumber,
        };
    }

    return null;
};

// Increment order number offline
const incrementOrderNumberOffline = () => {
    const lastOrderNumber = loadOrderNumberFromStorage();
    if (!lastOrderNumber) {
        // If no last order number, use a default
        orderNumber.value = "Order #001";
        saveOrderNumberToStorage(orderNumber.value);
        return;
    }

    const extracted = extractLastNumericSegment(lastOrderNumber);
    if (extracted) {
        const newNumericValue = extracted.numericValue + 1;
        // Preserve the digit length (padding) from the original
        const paddedNewValue = String(newNumericValue).padStart(
            extracted.digitLength,
            "0"
        );

        // Reconstruct the order number with incremented value
        orderNumber.value = `${extracted.prefix}${paddedNewValue}${extracted.suffix}`;
        saveOrderNumberToStorage(orderNumber.value);
        console.log("Incremented order number offline:", orderNumber.value);
    } else {
        // If we can't extract a number, try to append digits
        // Remove "(Offline)" if present
        const cleanNumber = lastOrderNumber.replace(/\s*\(Offline\)\s*$/, "");
        orderNumber.value = `${cleanNumber}-001`;
        saveOrderNumberToStorage(orderNumber.value);
        console.log(
            "Could not extract number, using fallback:",
            orderNumber.value
        );
    }
};

// Fetch new order number from API
const fetchNewOrderNumber = async () => {
    try {
        const response = await axios.get("/api/pos/get-order-number");
        // API returns array format: [order_number, formatted_order_number]
        if (Array.isArray(response.data) && response.data.length >= 2) {
            orderNumber.value = response.data[1] || response.data[0] || "";
        } else if (response.data?.formatted_order_number) {
            orderNumber.value = response.data.formatted_order_number;
        } else if (response.data?.order_number) {
            orderNumber.value = response.data.order_number;
        }

        // Save to localStorage when online
        if (orderNumber.value) {
            saveOrderNumberToStorage(orderNumber.value);
        }

        console.log("Fetched new order number:", orderNumber.value);
    } catch (error) {
        console.error("Error fetching order number:", error);
        orderNumber.value = "";
    }
};

const getOrder = async () => {
    try {
        const response = await axios.get(`/api/pos/orders/${orderId.value}`);
        order.value = response.data;
        console.log("Order fetched:", order.value);
    } catch (error) {
        console.error("Error fetching order:", error);
        order.value = null;
    }
};

const handleSaveOrder = async (...actions) => {
    saving.value = true;
    try {
        const orderData = {
            order_type: orderType.value,
            order_number: orderNumber.value,
            pax: pax.value,
            waiter_id: waiterId.value,
            customer: customer.value,
            items: cartItems.value,
            taxes: taxes.value,
            actions: actions,
            note: orderNote.value,
            table_id: currentTableId.value,
            discount_type: discountType.value || null,
            discount_value: discountValue.value || 0,
            discount_amount: discountAmount.value || 0,
            timestamp: new Date().toISOString(),
        };

        // Use offline API call wrapper
        const result = await offlineApiCall(
            async () => {
                // API call when online
                const response = await axios.post("/api/pos/orders", orderData);
                return response.data;
            },
            {
                type: "save_order",
                data: orderData,
            }
        );

        if (result.offline) {
            console.log("Order queued for sync:", result.operationId);
            // Clear cart after queueing
            cartItems.value = [];
            saveCartToStorage([]);

            // Increment order number for next offline order
            incrementOrderNumberOffline();
        } else {
            console.log("Order saved successfully:", result.data);
            // Clear cart after successful save
            cartItems.value = [];
            saveCartToStorage([]);

            // Fetch new order number when online
            if (isOnline.value) {
                await fetchNewOrderNumber();
            } else {
                // If somehow we're offline but order was saved, increment offline
                incrementOrderNumberOffline();
            }
        }
    } catch (error) {
        console.error("Error saving order:", error);
    } finally {
        saving.value = false;
    }
};

const handleConfirmSameCustomer = () => {
    // TODO: Implement API call to confirm same customer
    console.log("confirmSameCustomer");
    showReservationModal.value = false;
};

const handleConfirmDifferentCustomer = () => {
    // TODO: Implement API call to confirm different customer
    console.log("confirmDifferentCustomer");
    showReservationModal.value = false;
};

const handleConfirmTableChange = () => {
    // TODO: Implement API call to confirm table change
    console.log("confirmTableChange");
    currentTable.value = newTable.value;
    currentTableId.value = newTableId.value;
    showTableChangeConfirmationModal.value = false;
};

const handleSaveCustomer = async (customerData) => {
    console.log("saveCustomer:", customerData);
    try {
        // Use offline API call wrapper
        const result = await offlineApiCall(
            async () => {
                // API call when online
                const response = await axios.post(
                    "/api/pos/customers",
                    customerData
                );
                return response.data;
            },
            {
                type: "save_customer",
                data: customerData,
            }
        );

        // Save customer locally regardless of online/offline
        customer.value = { ...customerData };
        saveCustomerToStorage(customer.value);

        if (result.offline) {
            console.log("Customer queued for sync:", result.operationId);
        } else {
            console.log("Customer saved successfully:", result.data);
        }

        showAddCustomerModal.value = false;
    } catch (error) {
        console.error("Error saving customer:", error);
        // Still save locally even if API fails
        customer.value = { ...customerData };
        saveCustomerToStorage(customer.value);
    }
};

// Sync handler for pending operations
const syncHandler = async (operation) => {
    try {
        switch (operation.type) {
            case "save_order":
                const orderResponse = await axios.post(
                    "/api/pos/orders",
                    operation.data
                );
                console.log("Synced order:", orderResponse.data);
                return orderResponse.data;

            case "save_customer":
                const customerResponse = await axios.post(
                    "/api/pos/customers",
                    operation.data
                );
                console.log("Synced customer:", customerResponse.data);
                return customerResponse.data;

            default:
                console.warn("Unknown operation type:", operation.type);
        }
    } catch (error) {
        console.error("Error syncing operation:", error);
        throw error;
    }
};

// Watch for online status changes and sync when coming back online
watch(isOnline, (online) => {
    if (online && pendingOperations.value.length > 0) {
        console.log("Connection restored, syncing pending operations...");
        syncPendingOperations(syncHandler);
    }
});

// Load restaurant data
const loadRestaurantData = async () => {
    if (isOnline.value) {
        try {
            const response = await axios
                .get("/api/pos/restaurants")
                .catch(() => ({ data: null }));

            if (response.data) {
                restaurant.value = response.data;
                // Extract currency symbol
                if (
                    response.data.currency &&
                    response.data.currency.currency_symbol
                ) {
                    currencySymbol.value =
                        response.data.currency.currency_symbol;
                }
                console.log(
                    "Loaded restaurant data, currency symbol:",
                    currencySymbol.value
                );
            }
        } catch (error) {
            console.error("Error loading restaurant data from API:", error);
        }
    }
};

// Load menu data from cache or API
const loadMenuData = async () => {
    // First, try to load from cache (works offline)
    const cachedMenus = loadMenus();
    const cachedCategories = loadCategories();
    const cachedMenuItems = loadMenuItems();
    const cachedWaiters = loadWaiters();

    if (cachedMenus.length > 0) {
        menus.value = cachedMenus;
        console.log("Loaded menus from cache:", cachedMenus.length);
    }
    if (cachedCategories.length > 0) {
        categories.value = cachedCategories;
        console.log("Loaded categories from cache:", cachedCategories.length);
    }
    if (cachedMenuItems.length > 0) {
        menuItems.value = cachedMenuItems;
        console.log("Loaded menu items from cache:", cachedMenuItems.length);
    }
    if (cachedWaiters.length > 0) {
        waiters.value = cachedWaiters;
        console.log("Loaded waiters from cache:", cachedWaiters.length);
    }

    // If online, fetch fresh data from API and update cache
    if (isOnline.value) {
        try {
            const [menusRes, categoriesRes, itemsRes, waitersRes, taxesRes] =
                await Promise.all([
                    axios
                        .get("/api/pos/menus")
                        .catch(() => ({ data: menus.value })),
                    axios
                        .get("/api/pos/categories")
                        .catch(() => ({ data: categories.value })),
                    axios
                        .get("/api/pos/items")
                        .catch(() => ({ data: menuItems.value })),
                    axios
                        .get("/api/pos/waiters")
                        .catch(() => ({ data: waiters.value })),
                    axios
                        .get("/api/pos/taxes")
                        .catch(() => ({ data: availableTaxes.value })),
                ]);

            // Update with fresh data
            if (menusRes.data && menusRes.data.length > 0) {
                menus.value = menusRes.data;
                saveMenus(menusRes.data);
                console.log("Updated menus from API:", menusRes.data.length);
            }
            if (categoriesRes.data && categoriesRes.data.length > 0) {
                categories.value = categoriesRes.data;
                saveCategories(categoriesRes.data);
                console.log(
                    "Updated categories from API:",
                    categoriesRes.data.length
                );
            }
            if (itemsRes.data && itemsRes.data.length > 0) {
                menuItems.value = itemsRes.data;
                saveMenuItems(itemsRes.data);
                console.log(
                    "Updated menu items from API:",
                    itemsRes.data.length
                );
            }
            if (waitersRes.data && waitersRes.data.length > 0) {
                waiters.value = waitersRes.data;
                saveWaiters(waitersRes.data);
                console.log(
                    "Updated waiters from API:",
                    waitersRes.data.length
                );
            }
            if (taxesRes.data && taxesRes.data.length > 0) {
                availableTaxes.value = taxesRes.data;
                console.log("Updated taxes from API:", taxesRes.data.length);
                // Calculate taxes after loading
                calculateTaxes();
            }
        } catch (error) {
            console.error("Error loading data from API:", error);
            // Continue with cached data if API fails
        }
    }
};

// Handle show clear cache modal
const handleShowClearCacheModal = () => {
    // Refresh cache info before showing modal
    refreshCacheInfo();
    cacheInfo.value = getCacheInfo();
    showClearCacheModal.value = true;
};

// Handle clear cache
const handleClearCache = () => {
    const cleared = clearCache();
    if (cleared) {
        // Clear the menu data from state
        menus.value = [];
        categories.value = [];
        menuItems.value = [];
        waiters.value = [];

        // Clear OrderPanel data
        cartItems.value = [];
        orderType.value = "Dine In";
        orderNumber.value = "";
        currentTable.value = "";
        currentTableId.value = null;
        pax.value = 1;
        waiterId.value = "";
        customer.value = {
            name: "",
            email: "",
            phone: "",
            phone_code: "",
            address: "",
        };
        orderNote.value = "";
        discountAmount.value = 0;
        discountType.value = "";
        discountValue.value = 0;
        taxes.value = [];
        extraCharges.value = [];

        // Clear from localStorage
        saveCartToStorage([]);
        saveCustomerToStorage(null);

        // Try to reload if online
        if (isOnline.value) {
            loadMenuData();
        }

        showClearCacheModal.value = false;
        console.log("Cache and OrderPanel data cleared successfully");
    }
};

// Fetch order and load KOT items into cart
const loadOrderData = async () => {
    if (!orderId.value) {
        return; // No order ID, skip loading order data
    }

    try {
        const response = await axios.get(`/api/pos/orders/${orderId.value}`);

        if (response.data.success && response.data.order) {
            const loadedOrder = response.data.order;
            order.value = loadedOrder;

            // Load order details
            if (loadedOrder.order_number) {
                orderNumber.value = loadedOrder.order_number;
            }
            if (loadedOrder.number_of_pax) {
                pax.value = loadedOrder.number_of_pax;
            }
            if (loadedOrder.waiter_id) {
                waiterId.value = loadedOrder.waiter_id;
            }
            if (loadedOrder.order_type) {
                orderType.value = loadedOrder.order_type;
            }
            if (loadedOrder.discount_type) {
                discountType.value = loadedOrder.discount_type;
            }
            if (loadedOrder.discount_value) {
                discountValue.value = loadedOrder.discount_value;
            }
            if (loadedOrder.discount_amount) {
                discountAmount.value = loadedOrder.discount_amount;
            }
            if (loadedOrder.note) {
                orderNote.value = loadedOrder.note;
            }
            if (loadedOrder.table_id) {
                currentTableId.value = loadedOrder.table_id;
            }
            if (loadedOrder.table) {
                currentTable.value = loadedOrder.table.table_code || "";
            }

            // Load customer data
            if (loadedOrder.customer) {
                customer.value = {
                    name: loadedOrder.customer.name || "",
                    email: loadedOrder.customer.email || "",
                    phone: loadedOrder.customer.phone || "",
                    phone_code: loadedOrder.customer.phone_code || "",
                    address: loadedOrder.customer.address || "",
                };
            }

            // Load KOT items into cart
            if (loadedOrder.kot && loadedOrder.kot.length > 0) {
                const kotItems = [];

                loadedOrder.kot.forEach((kot) => {
                    if (kot.items && kot.items.length > 0) {
                        kot.items.forEach((kotItem) => {
                            const menuItem =
                                kotItem.menu_item || kotItem.menuItem;

                            if (menuItem) {
                                const cartItem = {
                                    id: menuItem.id,
                                    name:
                                        menuItem.item_name ||
                                        menuItem.name ||
                                        "Unknown Item",
                                    price:
                                        menuItem.price ||
                                        menuItem.contextual_price ||
                                        0,
                                    quantity: kotItem.quantity || 1,
                                    variant_id:
                                        kotItem.menu_item_variation_id || 0,
                                    modifier_id: 0, // KOT items might have multiple modifiers
                                    note: kotItem.note || "",
                                };

                                kotItems.push(cartItem);
                            }
                        });
                    }
                });

                if (kotItems.length > 0) {
                    cartItems.value = kotItems;
                    saveCartToStorage(cartItems.value);
                    console.log("Loaded KOT items into cart:", kotItems.length);
                    // Calculate taxes after loading KOT items
                    calculateTaxes();
                }
            }

            console.log("Order data loaded successfully:", loadedOrder);
        }
    } catch (error) {
        console.error("Error loading order data:", error);
    }
};

// Load initial data
onMounted(async () => {
    // Load order data if orderId is present in URL
    if (orderId.value) {
        await loadOrderData();
    }

    // Load cart from localStorage (only if no orderId was loaded)
    if (!orderId.value) {
        const savedCart = loadCartFromStorage();
        if (savedCart && savedCart.length > 0) {
            cartItems.value = savedCart;
            console.log("Loaded cart from localStorage:", savedCart);
            // Calculate taxes after loading cart
            calculateTaxes();
        }
    }

    // Load customer from localStorage
    const savedCustomer = loadCustomerFromStorage();
    if (savedCustomer) {
        customer.value = savedCustomer;
        console.log("Loaded customer from localStorage:", savedCustomer);
    }

    // Load restaurant data (from API)
    await loadRestaurantData();

    // Load menu data (from cache first, then API if online)
    await loadMenuData();

    // Fetch initial order number
    if (isOnline.value && !orderNumber.value) {
        await fetchNewOrderNumber();
    } else if (!isOnline.value && !orderNumber.value) {
        // If offline and no order number, try to load last one or increment
        const lastOrderNumber = loadOrderNumberFromStorage();
        if (lastOrderNumber) {
            incrementOrderNumberOffline();
        } else {
            // Start with a default offline order number
            orderNumber.value = "Order #001";
            saveOrderNumberToStorage(orderNumber.value);
        }
    }

    // Sync pending operations if online
    if (isOnline.value && pendingOperations.value.length > 0) {
        syncPendingOperations(syncHandler);
    }
});

// Reload menu data when coming back online
watch(isOnline, (newVal) => {
    if (newVal) {
        // When coming back online, refresh menu data
        loadMenuData();
        // Also sync pending operations
        if (pendingOperations.value.length > 0) {
            syncPendingOperations(syncHandler);
        }
    }
});
</script>

<style scoped></style>
