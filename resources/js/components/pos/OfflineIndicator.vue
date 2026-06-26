<template>
    <div
        class="fixed top-0 left-0 right-0 z-[9999] transition-all duration-300"
        style="pointer-events: none"
    >
        <!-- Top Line -->
        <div
            class="w-full h-0.5 transition-all duration-300"
            :class="{
                'bg-red-500': !isOnline,
                'bg-yellow-500': isOnline && pendingOperationsCount > 0,
                'bg-green-500': isOnline && pendingOperationsCount === 0,
            }"
        ></div>

        <!-- Centered Badge with Clear Cache Button -->
        <div class="flex justify-center items-center gap-2 -mt-2 mb-2">
            <div
                @click="
                    pendingOperationsCount > 0
                        ? (showPendingModal = true)
                        : null
                "
                :class="[
                    'flex items-center gap-1.5 px-4 py-1.5 rounded-full shadow-lg transition-all duration-300',
                    pendingOperationsCount > 0
                        ? 'cursor-pointer hover:scale-105'
                        : '',
                    {
                        'bg-red-500 text-white border-2 border-red-600':
                            !isOnline,
                        'bg-yellow-500 text-white border-2 border-yellow-600':
                            isOnline && pendingOperationsCount > 0,
                        'bg-green-500 text-white border-2 border-green-600':
                            isOnline && pendingOperationsCount === 0,
                    },
                ]"
                style="pointer-events: auto"
                :title="
                    pendingOperationsCount > 0
                        ? isOnline
                            ? 'Click to view orders being synced'
                            : 'Click to view pending orders'
                        : ''
                "
            >
                <!-- Online Status Icon -->
                <svg
                    v-if="isOnline && pendingOperationsCount === 0"
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>

                <!-- Offline Icon -->
                <svg
                    v-else-if="!isOnline"
                    class="w-3 h-3 animate-pulse"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"
                    />
                </svg>

                <!-- Syncing Icon -->
                <svg
                    v-else
                    class="w-3 h-3 animate-spin"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>

                <!-- Status Text -->
                <span class="text-xs font-semibold uppercase tracking-wide">
                    <span v-if="!isOnline">Offline</span>
                    <span v-else-if="pendingOperationsCount > 0">
                        Syncing {{ pendingOperationsCount }}
                    </span>
                    <span v-else>Online</span>
                </span>

                <!-- Pending Count Badge -->
                <span
                    v-if="pendingOperationsCount > 0"
                    class="px-1.5 py-0.5 text-[10px] font-bold bg-white/20 rounded-full"
                >
                    {{ pendingOperationsCount }}
                </span>
            </div>

            <!-- Clear Cache Button (Only visible when online) -->
            <button
                v-if="isOnline"
                @click="$emit('clear-cache')"
                style="pointer-events: auto"
                class="group relative inline-flex items-center gap-1.5 px-3 py-1 bg-gray-700 hover:bg-gray-800 text-white rounded-full shadow-lg transition-all duration-200 text-xs"
                title="Clear local cache and fetch latest records from database. Only works in online mode."
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="14"
                    height="14"
                    fill="currentColor"
                    viewBox="0 0 16 16"
                >
                    <path
                        d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"
                    />
                    <path
                        d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3V2h11v1z"
                    />
                </svg>
                <span class="hidden sm:inline">Clear Cache</span>

                <!-- Tooltip on the left side -->
                <div
                    class="absolute right-full top-1/2 transform -translate-y-1/2 mr-2 px-3 py-2 text-xs text-white bg-gray-900 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50"
                >
                    Clear local cache and fetch latest records from database.
                    Only works in online mode.
                    <div
                        class="absolute left-full top-1/2 transform -translate-y-1/2 -ml-1 border-4 border-transparent border-r-gray-900"
                    ></div>
                </div>
            </button>
        </div>

        <!-- Pending Orders Modal -->
        <div
            v-if="showPendingModal && pendingOperationsCount > 0"
            class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="showPendingModal = false"
            style="pointer-events: auto"
        >
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
                @click.stop
            >
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                    :class="
                        isOnline
                            ? 'bg-gradient-to-r from-yellow-50 to-white dark:from-gray-800 dark:to-gray-900'
                            : 'bg-gradient-to-r from-red-50 to-white dark:from-gray-800 dark:to-gray-900'
                    "
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h2
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                <span v-if="isOnline">Syncing Orders</span>
                                <span v-else>Pending Orders (Offline)</span>
                            </h2>
                            <p
                                class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                            >
                                <span v-if="isOnline">
                                    {{ pendingOrders.length }} order(s) being
                                    synced
                                </span>
                                <span v-else>
                                    {{ pendingOrders.length }} order(s) waiting
                                    to be synced when online
                                </span>
                            </p>
                        </div>
                        <button
                            @click="showPendingModal = false"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <svg
                                class="w-6 h-6 text-gray-500 dark:text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div
                        v-if="pendingOrders.length === 0"
                        class="text-center py-12"
                    >
                        <svg
                            class="w-16 h-16 mx-auto text-gray-400 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">
                            No pending orders
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="(order, index) in pendingOrders"
                            :key="order.id || index"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 hover:shadow-md transition-shadow"
                        >
                            <!-- Order Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-semibold rounded',
                                                isOnline
                                                    ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'
                                                    : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
                                            ]"
                                        >
                                            <span
                                                v-if="
                                                    order.data?.order_number ||
                                                    order.data
                                                        ?.formatted_order_number
                                                "
                                            >
                                                {{
                                                    order.data
                                                        .formatted_order_number ||
                                                    order.data.order_number
                                                }}
                                            </span>
                                            <span v-else-if="isOnline">
                                                Syncing #{{ index + 1 }}
                                            </span>
                                            <span v-else>
                                                Order #{{ index + 1 }}
                                            </span>
                                        </span>
                                        <span
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {{ formatDate(order.timestamp) }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ order.data?.order_type || "N/A" }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-lg font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            formatCurrency(
                                                calculateOrderTotal(order.data)
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ order.data?.items?.length || 0 }}
                                        item(s)
                                    </p>
                                </div>
                            </div>

                            <!-- Customer Info -->
                            <div
                                v-if="order.data?.customer"
                                class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700"
                            >
                                <p
                                    class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    Customer:
                                </p>
                                <p
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    {{ order.data.customer.name || "N/A" }}
                                </p>
                                <div
                                    v-if="
                                        order.data.customer.phone ||
                                        order.data.customer.email
                                    "
                                    class="flex gap-4 mt-1"
                                >
                                    <span
                                        v-if="order.data.customer.phone"
                                        class="text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        📞 {{ order.data.customer.phone }}
                                    </span>
                                    <span
                                        v-if="order.data.customer.email"
                                        class="text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        ✉️ {{ order.data.customer.email }}
                                    </span>
                                </div>
                            </div>

                            <!-- Table Info -->
                            <div
                                v-if="
                                    order.data?.table_id ||
                                    order.data?.currentTable
                                "
                                class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700"
                            >
                                <p
                                    class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    Table:
                                </p>
                                <p
                                    class="text-sm text-gray-900 dark:text-white"
                                >
                                    {{
                                        order.data.currentTable ||
                                        `Table ID: ${order.data.table_id}` ||
                                        "N/A"
                                    }}
                                </p>
                            </div>

                            <!-- Order Items -->
                            <div class="mb-3">
                                <p
                                    class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    Items:
                                </p>
                                <div class="space-y-1">
                                    <div
                                        v-for="(item, itemIndex) in order.data
                                            ?.items || []"
                                        :key="itemIndex"
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span
                                            class="text-gray-700 dark:text-gray-300"
                                        >
                                            {{ item.quantity }}x
                                            {{
                                                item.name || `Item #${item.id}`
                                            }}
                                        </span>
                                        <span
                                            class="text-gray-900 dark:text-white font-medium"
                                        >
                                            {{
                                                formatCurrency(
                                                    (item.price || 0) *
                                                        (item.quantity || 1)
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div
                                class="pt-3 border-t border-gray-200 dark:border-gray-700"
                            >
                                <div class="flex justify-between text-sm mb-1">
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Subtotal:</span
                                    >
                                    <span class="text-gray-900 dark:text-white">
                                        {{
                                            formatCurrency(
                                                calculateSubtotal(order.data)
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    v-if="order.data?.discount_amount > 0"
                                    class="flex justify-between text-sm mb-1"
                                >
                                    <span
                                        class="text-gray-600 dark:text-gray-400"
                                        >Discount:</span
                                    >
                                    <span
                                        class="text-red-600 dark:text-red-400"
                                    >
                                        -{{
                                            formatCurrency(
                                                order.data.discount_amount || 0
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-gray-200 dark:border-gray-700"
                                >
                                    <span class="text-gray-900 dark:text-white"
                                        >Total:</span
                                    >
                                    <span class="text-gray-900 dark:text-white">
                                        {{
                                            formatCurrency(
                                                calculateOrderTotal(order.data)
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div
                                v-if="
                                    order.data?.actions &&
                                    order.data.actions.length > 0
                                "
                                class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700"
                            >
                                <p
                                    class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    Actions:
                                </p>
                                <div class="flex gap-2">
                                    <span
                                        v-for="action in order.data.actions"
                                        :key="action"
                                        class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs rounded"
                                    >
                                        {{ action.toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span v-if="isOnline">
                                These orders are being synced automatically
                            </span>
                            <span v-else>
                                These orders will be automatically synced when
                                you're back online
                            </span>
                        </p>
                        <button
                            @click="showPendingModal = false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-colors text-sm font-medium"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    isOnline: {
        type: Boolean,
        required: true,
    },
    pendingOperations: {
        type: [Number, Array],
        default: 0,
    },
});

const emit = defineEmits(["clear-cache"]);

const showPendingModal = ref(false);

// Compute pending operations count
const pendingOperationsCount = computed(() => {
    if (Array.isArray(props.pendingOperations)) {
        return props.pendingOperations.length;
    }
    return props.pendingOperations || 0;
});

// Filter only order operations
const pendingOrders = computed(() => {
    if (Array.isArray(props.pendingOperations)) {
        return props.pendingOperations.filter((op) => op.type === "save_order");
    }
    return [];
});

// Format date
const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    try {
        const date = new Date(dateString);
        return date.toLocaleString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch (error) {
        return dateString;
    }
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount || 0);
};

// Calculate subtotal
const calculateSubtotal = (orderData) => {
    if (!orderData?.items) return 0;
    return orderData.items.reduce((sum, item) => {
        return sum + (item.price || 0) * (item.quantity || 1);
    }, 0);
};

// Calculate order total
const calculateOrderTotal = (orderData) => {
    if (!orderData) return 0;
    const subtotal = calculateSubtotal(orderData);
    const discount = orderData.discount_amount || 0;
    return Math.max(0, subtotal - discount);
};
</script>

<style scoped></style>
