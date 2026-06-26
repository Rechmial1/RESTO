<template>
    <div
        v-if="show"
        class="jetstream-modal fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
        @click.self="handleClose"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 transform transition-all bg-gray-500 dark:bg-gray-900 opacity-75"
            @click="handleClose"
        ></div>

        <!-- Modal Content -->
        <div
            class="mb-6 bg-white dark:bg-gray-900 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-4xl sm:mx-auto overflow-y-auto max-h-[90vh]"
        >
            <div class="px-6 py-4">
                <div class="flex flex-col gap-1">
                    <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Tables
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Click a table to start a sale, or open an active order from that table.
                    </p>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="my-4 grid gap-6 grid-cols-3">
                        <!-- Card Section -->
                        <div class="space-y-8 col-span-2">
                            <!-- Areas with Tables -->
                            <div
                                v-for="area in areasWithTables"
                                :key="area.id"
                                class="flex flex-col gap-3 sm:gap-4 space-y-3"
                            >
                                <h3
                                    class="text-sm font-medium inline-flex gap-2 items-center dark:text-neutral-200"
                                >
                                    {{ area.name }}
                                    <span
                                        class="px-2 py-1 text-sm rounded bg-slate-100 border-gray-300 border text-gray-800 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    >
                                        {{ area.tables.length }} Table{{
                                            area.tables.length !== 1 ? "s" : ""
                                        }}
                                    </span>
                                </h3>

                                <div class="grid sm:grid-cols-3 gap-3 sm:gap-4">
                                    <!-- Table Cards -->
                                    <div
                                        v-for="table in area.tables"
                                        :key="table.id"
                                        role="button"
                                        tabindex="0"
                                        @click="handleSelectTable(table)"
                                        @keydown.enter.prevent="handleSelectTable(table)"
                                        @keydown.space.prevent="handleSelectTable(table)"
                                        :aria-disabled="selectingTableId === table.id || table.is_locked_by_other_user"
                                        class="relative w-full group flex items-center justify-center border shadow-sm rounded-lg hover:shadow-md transition-all duration-200 dark:border-gray-600"
                                        :class="{
                                            'bg-red-50 dark:bg-red-900/20': table.status === 'inactive',
                                            'bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600': table.status === 'active' && !table.is_locked,
                                            'bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:border-orange-700': table.is_locked_by_other_user,
                                            'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700': table.is_locked_by_current_user,
                                            'opacity-50 cursor-not-allowed': selectingTableId === table.id || table.is_locked_by_other_user,
                                            'cursor-pointer': selectingTableId !== table.id && !table.is_locked_by_other_user,
                                        }"
                                    >
                                        <!-- Lock indicator for locked tables -->
                                        <div
                                            v-if="table.is_locked"
                                            class="absolute top-2 right-2 z-10 transition-transform hover:scale-110"
                                            @click.stop="handleUnlockTable(table.id, table.is_locked_by_current_user)"
                                        >
                                            <button
                                                v-if="canUnlockTable(table)"
                                                class="relative group p-1 rounded-full shadow-sm hover:shadow-md transition-all duration-200 text-white"
                                                :class="{
                                                    'bg-blue-500 hover:bg-blue-600': table.is_locked_by_current_user,
                                                    'bg-red-500 hover:bg-red-600': !table.is_locked_by_current_user,
                                                }"
                                                :title="
                                                    table.is_locked_by_current_user
                                                        ? `Locked by you at ${table.locked_at}`
                                                        : `Force unlock (Locked by ${table.locked_by_user_name || 'Unknown'} at ${table.locked_at})`
                                                "
                                            >
                                                <!-- Locked icon -->
                                                <svg
                                                    class="w-3.5 h-3.5 group-hover:opacity-0 group-hover:scale-0 transition-all duration-200"
                                                    aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke="currentColor"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 14v3m-3-6V7a3 3 0 1 1 6 0v4m-8 0h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1"
                                                    />
                                                </svg>
                                                <!-- Unlock icon (shows on hover) -->
                                                <svg
                                                    class="w-3 h-3 absolute inset-0 m-auto opacity-0 scale-0 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                                                    aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke="currentColor"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 14v3m4-6V7a3 3 0 1 1 6 0v4M5 11h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Z"
                                                    />
                                                </svg>
                                            </button>
                                            <div
                                                v-else
                                                class="bg-orange-500 text-white p-1 rounded-full shadow cursor-help hover:shadow-md transition-all duration-200"
                                                :title="`Locked by ${table.locked_by_user_name || 'Unknown'} at ${table.locked_at}`"
                                            >
                                                <svg
                                                    class="w-3.5 h-3.5"
                                                    aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke="currentColor"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 14v3m-3-6V7a3 3 0 1 1 6 0v4m-8 0h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1"
                                                    />
                                                </svg>
                                            </div>
                                        </div>

                                        <div class="p-3 w-full">
                                            <div
                                                class="flex flex-col space-y-2 items-center justify-center"
                                            >
                                                <!-- Inactive indicator -->
                                                <div
                                                    v-if="table.status === 'inactive'"
                                                    class="inline-flex text-xs gap-1 text-red-600 font-semibold"
                                                >
                                                    Inactive
                                                </div>

                                                <!-- Table code badge -->
                                                <div
                                                    class="p-2 rounded-lg tracking-wide"
                                                    :class="{
                                                        'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': table.available_status === 'available' && !table.is_locked,
                                                        'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': table.available_status === 'reserved',
                                                        'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400': table.available_status === 'running',
                                                        'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400': table.is_locked_by_other_user,
                                                        'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400': table.is_locked_by_current_user,
                                                    }"
                                                >
                                                    <h3
                                                        class="font-semibold"
                                                        :class="{
                                                            'opacity-50': selectingTableId === table.id,
                                                        }"
                                                    >
                                                        {{ table.table_code }}
                                                    </h3>
                                                </div>
                                                <p
                                                    class="text-xs font-medium dark:text-neutral-200 text-gray-500"
                                                >
                                                    {{ table.seating_capacity }} Seat{{
                                                        table.seating_capacity !== 1 ? "s" : ""
                                                    }}
                                                </p>

                                                <div
                                                    v-if="table.active_orders_count > 0"
                                                    class="w-full space-y-2 pt-2 border-t border-gray-100 dark:border-gray-600"
                                                >
                                                    <div
                                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                                    >
                                                        {{ table.active_orders_count }} active order{{
                                                            table.active_orders_count !== 1 ? "s" : ""
                                                        }}
                                                    </div>

                                                    <div class="space-y-1">
                                                        <button
                                                            v-for="order in table.active_orders"
                                                            :key="order.id"
                                                            type="button"
                                                            class="w-full rounded-md border border-blue-200 bg-white px-2 py-1 text-left text-xs text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:bg-gray-800 dark:text-blue-300 dark:hover:bg-blue-900/30"
                                                            @click.stop="handleOpenOrder(order.id)"
                                                        >
                                                            <div class="flex items-center justify-between gap-2">
                                                                <span class="font-semibold truncate">
                                                                    {{ order.formatted_order_number || `Order #${order.id}` }}
                                                                </span>
                                                                <span class="capitalize text-[11px]">
                                                                    {{ order.status }}
                                                                </span>
                                                            </div>
                                                            <div
                                                                v-if="order.items && order.items.length"
                                                                class="mt-0.5 truncate text-gray-500 dark:text-gray-400"
                                                            >
                                                                {{ summarizeOrderItems(order.items) }}
                                                            </div>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div
                                                    v-else
                                                    class="text-[11px] text-gray-400 dark:text-gray-500"
                                                >
                                                    Click to start sale
                                                </div>
                                            </div>

                                            <!-- Loading Spinner -->
                                            <div
                                                v-if="selectingTableId === table.id"
                                                class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50 rounded-lg"
                                            >
                                                <svg
                                                    class="animate-spin h-5 w-5 text-skin-base"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <circle
                                                        class="opacity-25"
                                                        cx="12"
                                                        cy="12"
                                                        r="10"
                                                        stroke="currentColor"
                                                        stroke-width="3"
                                                        fill="none"
                                                    ></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Card Section -->

                        <!-- Reservations Sidebar -->
                        <div class="col-span-1 space-y-3">
                            <h4 class="text-base font-medium dark:text-gray-200">
                                Today Reservations
                            </h4>
                            <div
                                v-if="reservations.length === 0"
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                No table is reserved.
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="reservation in reservations"
                                    :key="reservation.id"
                                    class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70 p-2"
                                >
                                    <div class="flex justify-between">
                                        <div class="text-base font-semibold text-gray-800 dark:text-white">
                                            <div
                                                class="p-2 rounded-md tracking-wide bg-skin-base/[0.2] text-skin-base"
                                            >
                                                <h3 class="font-semibold">
                                                    {{ reservation.table_code }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div
                                            class="text-gray-700 dark:text-neutral-400 flex flex-col space-y-1"
                                        >
                                            <div class="inline-flex gap-2 items-center text-xs">
                                                {{ reservation.party_size }} Guest{{
                                                    reservation.party_size !== 1 ? "s" : ""
                                                }}
                                            </div>
                                            <div class="inline-flex gap-2 items-center text-xs">
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="16"
                                                    height="16"
                                                    fill="currentColor"
                                                    class="bi bi-clock"
                                                    viewBox="0 0 16 16"
                                                >
                                                    <path
                                                        d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"
                                                    />
                                                    <path
                                                        d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"
                                                    />
                                                </svg>
                                                {{ reservation.time }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end"
            >
                <button
                    type="button"
                    class="button-cancel inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                    @click="handleClose"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "select", "open-order"]);

const tables = ref([]);
const reservations = ref([]);
const selectingTableId = ref(null);
const unlockingTableId = ref(null);
const loading = ref(false);
const isAdmin = ref(false);

// Group tables by area
const areasWithTables = computed(() => {
    const areasMap = {};
    
    tables.value.forEach((table) => {
        const areaId = table.area_id || "unknown";
        const areaName = table.area_name || "Unknown Area";
        
        if (!areasMap[areaId]) {
            areasMap[areaId] = {
                id: areaId,
                name: areaName,
                tables: [],
            };
        }
        
        areasMap[areaId].tables.push(table);
    });
    
    return Object.values(areasMap);
});

// Fetch tables when modal opens
const fetchTables = async () => {
    if (loading.value) return;
    
    loading.value = true;
    try {
        const response = await axios.get("/api/pos/tables");
        if (response.data) {
            // Handle both old format (array) and new format (object with tables and is_admin)
            if (Array.isArray(response.data)) {
                tables.value = response.data;
            } else {
                tables.value = response.data.tables || [];
                isAdmin.value = response.data.is_admin || false;
            }
        }
    } catch (error) {
        console.error("Error fetching tables:", error);
        tables.value = [];
    } finally {
        loading.value = false;
    }
};

// Fetch reservations when modal opens
const fetchReservations = async () => {
    try {
        const response = await axios.get("/api/pos/reservations/today");
        if (response.data) {
            reservations.value = response.data;
        }
    } catch (error) {
        console.error("Error fetching reservations:", error);
        reservations.value = [];
    }
};

// Watch for modal opening
watch(
    () => props.show,
    (isShowing) => {
        if (isShowing) {
            fetchTables();
            fetchReservations();
        }
    }
);

const handleClose = () => {
    emit("close");
};

// Check if user can unlock a table
const canUnlockTable = (table) => {
    return isAdmin.value || table.is_locked_by_current_user;
};

const summarizeOrderItems = (items) => {
    return items
        .slice(0, 3)
        .map((item) => `${item.quantity || 1}x ${item.name}`)
        .join(", ");
};

// Handle table unlock
const handleUnlockTable = async (tableId, isLockedByCurrentUser) => {
    if (unlockingTableId.value === tableId) return;

    // Don't allow unlocking if not authorized
    const table = tables.value.find((t) => t.id === tableId);
    if (!canUnlockTable(table)) {
        return;
    }

    if (
        !confirm(
                            isLockedByCurrentUser
                                ? "Do you want to unlock this table?"
                                : `This table is locked by ${table.locked_by_user_name || "another user"}. Do you want to force unlock it?`
                        )
    ) {
        return;
    }

    unlockingTableId.value = tableId;
    try {
        const response = await axios.post(`/api/pos/tables/${tableId}/unlock`);
        if (response.data.success) {
            // Refresh tables after unlock
            await fetchTables();
        } else {
            alert(response.data.message || "Failed to unlock table");
        }
    } catch (error) {
        console.error("Error unlocking table:", error);
        alert(
            error.response?.data?.message ||
                "Failed to unlock table. Please try again."
        );
    } finally {
        unlockingTableId.value = null;
    }
};

// Handle table selection
const handleSelectTable = async (table) => {
    // Check if table is locked by another user
    if (table.is_locked_by_other_user) {
        alert(
            `This table is locked by ${table.locked_by_user_name || "another user"}. Please unlock it first or choose another table.`
        );
        return;
    }

    selectingTableId.value = table.id;
    try {
        emit("select", table);
        handleClose();
    } catch (error) {
        console.error("Error selecting table:", error);
    } finally {
        selectingTableId.value = null;
    }
};

const handleOpenOrder = (orderId) => {
    emit("open-order", orderId);
    handleClose();
};

// Close on Escape key
if (typeof window !== "undefined") {
    watch(
        () => props.show,
        (isShowing) => {
            if (isShowing) {
                const handleEscape = (e) => {
                    if (e.key === "Escape") {
                        handleClose();
                    }
                };
                window.addEventListener("keydown", handleEscape);
                return () => {
                    window.removeEventListener("keydown", handleEscape);
                };
            }
        }
    );
}
</script>

<style scoped></style>
