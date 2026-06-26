import { ref, onMounted, onUnmounted } from "vue";

// Storage keys
const STORAGE_KEY = "pos_offline_queue";
const CART_STORAGE_KEY = "pos_offline_cart";
const CUSTOMER_STORAGE_KEY = "pos_offline_customer";

// Shared state - refs defined at module level so they're shared across all instances
const isOnline = ref(navigator.onLine);
const pendingOperations = ref([]);
let eventListenersSetup = false;
let beforeUnloadHandler = null;
let navigationInterceptionSetup = false;

export function useOfflineMode() {
    // Load pending operations from localStorage
    const loadPendingOperations = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                pendingOperations.value = JSON.parse(stored);
            }
        } catch (error) {
            console.error("Error loading pending operations:", error);
        }
    };

    // Save pending operations to localStorage
    const savePendingOperations = () => {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(pendingOperations.value)
            );
        } catch (error) {
            console.error("Error saving pending operations:", error);
        }
    };

    // Add operation to queue
    const queueOperation = (operation) => {
        const operationWithId = {
            id: Date.now() + Math.random(),
            timestamp: new Date().toISOString(),
            ...operation,
        };
        pendingOperations.value.push(operationWithId);
        savePendingOperations();
        console.log("Queued operation:", operationWithId);
        return operationWithId.id;
    };

    // Remove operation from queue
    const removeOperation = (operationId) => {
        pendingOperations.value = pendingOperations.value.filter(
            (op) => op.id !== operationId
        );
        savePendingOperations();
    };

    // Save cart to localStorage
    const saveCart = (cartItems) => {
        try {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cartItems));
        } catch (error) {
            console.error("Error saving cart:", error);
        }
    };

    // Load cart from localStorage
    const loadCart = () => {
        try {
            const stored = localStorage.getItem(CART_STORAGE_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error("Error loading cart:", error);
            return [];
        }
    };

    // Save customer to localStorage
    const saveCustomer = (customer) => {
        try {
            localStorage.setItem(
                CUSTOMER_STORAGE_KEY,
                JSON.stringify(customer)
            );
        } catch (error) {
            console.error("Error saving customer:", error);
        }
    };

    // Load customer from localStorage
    const loadCustomer = () => {
        try {
            const stored = localStorage.getItem(CUSTOMER_STORAGE_KEY);
            return stored ? JSON.parse(stored) : null;
        } catch (error) {
            console.error("Error loading customer:", error);
            return null;
        }
    };

    // Clear all offline data
    const clearOfflineData = () => {
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(CART_STORAGE_KEY);
        localStorage.removeItem(CUSTOMER_STORAGE_KEY);
        pendingOperations.value = [];
    };

    // Sync pending operations when online
    const syncPendingOperations = async (syncHandler) => {
        if (!isOnline.value || pendingOperations.value.length === 0) {
            return;
        }

        console.log(
            `Syncing ${pendingOperations.value.length} pending operations...`
        );
        const operationsToSync = [...pendingOperations.value];

        for (const operation of operationsToSync) {
            try {
                await syncHandler(operation);
                removeOperation(operation.id);
                console.log("Synced operation:", operation.id);
            } catch (error) {
                console.error("Error syncing operation:", error);
                // Keep operation in queue if sync fails
            }
        }

        console.log("Sync complete");
    };

    // Handle online/offline events
    const handleOnline = () => {
        isOnline.value = true;
        console.log("App is now online");
    };

    const handleOffline = () => {
        isOnline.value = false;
        console.log("App is now offline");
    };

    // Setup navigation protection when offline
    const setupNavigationProtection = () => {
        if (navigationInterceptionSetup) return;
        navigationInterceptionSetup = true;

        // Handle beforeunload (page close/refresh)
        beforeUnloadHandler = (e) => {
            // Only warn if offline AND there are pending operations or unsaved data
            if (!isOnline.value && pendingOperations.value.length > 0) {
                const message =
                    "You are currently offline with pending operations that haven't been synced. " +
                    "If you leave now, these operations may be lost. Are you sure you want to leave?";

                // Modern browsers
                e.preventDefault();
                // Chrome requires returnValue to be set
                e.returnValue = message;
                return message;
            }

            // Also check for unsaved cart data
            try {
                const savedCart = localStorage.getItem(CART_STORAGE_KEY);
                if (!isOnline.value && savedCart) {
                    const cart = JSON.parse(savedCart);
                    if (cart && cart.length > 0) {
                        const message =
                            "You are currently offline with items in your cart. " +
                            "If you leave now, your cart may be lost. Are you sure you want to leave?";
                        e.preventDefault();
                        e.returnValue = message;
                        return message;
                    }
                }
            } catch (error) {
                console.error("Error checking cart data:", error);
            }
        };

        // Intercept link clicks
        const interceptLinkClicks = (e) => {
            // Only intercept if offline
            if (!isOnline.value) {
                const target = e.target.closest("a");
                if (
                    target &&
                    target.href &&
                    !target.hasAttribute("data-allow-offline")
                ) {
                    // Check if it's an external link or internal navigation
                    const isExternal =
                        target.hostname !== window.location.hostname;
                    const isSameOrigin =
                        target.origin === window.location.origin;

                    // Only warn for same-origin navigation (going to another page on the site)
                    if (isSameOrigin && !isExternal) {
                        const hasPendingOps =
                            pendingOperations.value.length > 0;

                        // Check for cart data
                        let hasCartData = false;
                        try {
                            const savedCart =
                                localStorage.getItem(CART_STORAGE_KEY);
                            if (savedCart) {
                                const cart = JSON.parse(savedCart);
                                hasCartData = cart && cart.length > 0;
                            }
                        } catch (error) {
                            console.error("Error checking cart:", error);
                        }

                        if (hasPendingOps || hasCartData) {
                            e.preventDefault();
                            const message = hasPendingOps
                                ? `You are offline with ${pendingOperations.value.length} pending operation(s). They will be synced when you're back online. Do you want to leave this page?`
                                : "You are offline with items in your cart. Your cart will be saved locally. Do you want to leave this page?";

                            if (confirm(message)) {
                                // User confirmed, allow navigation
                                window.location.href = target.href;
                            }
                            // If user cancels, navigation is prevented
                            return false;
                        }
                    }
                }
            }
        };

        // Intercept form submissions
        const interceptFormSubmissions = (e) => {
            if (!isOnline.value) {
                const form = e.target.closest("form");
                if (form && !form.hasAttribute("data-allow-offline")) {
                    const hasPendingOps = pendingOperations.value.length > 0;

                    let hasCartData = false;
                    try {
                        const savedCart =
                            localStorage.getItem(CART_STORAGE_KEY);
                        if (savedCart) {
                            const cart = JSON.parse(savedCart);
                            hasCartData = cart && cart.length > 0;
                        }
                    } catch (error) {
                        console.error("Error checking cart:", error);
                    }

                    if (hasPendingOps || hasCartData) {
                        e.preventDefault();
                        const message = hasPendingOps
                            ? `You are offline with ${pendingOperations.value.length} pending operation(s). They will be synced automatically when you're back online. Do you want to proceed?`
                            : "You are offline. Your data will be saved locally and synced when online. Do you want to proceed?";

                        if (!confirm(message)) {
                            return false;
                        }
                        // If confirmed, re-submit the form
                        setTimeout(() => {
                            form.submit();
                        }, 100);
                    }
                }
            }
        };

        // Attach event listeners
        window.addEventListener("beforeunload", beforeUnloadHandler);
        document.addEventListener("click", interceptLinkClicks, true); // Use capture phase
        document.addEventListener("submit", interceptFormSubmissions, true);

        console.log("Navigation protection setup complete");
    };

    // Setup event listeners only once
    if (!eventListenersSetup) {
        eventListenersSetup = true;

        // Set initial state
        isOnline.value = navigator.onLine;
        loadPendingOperations();

        // Setup global event listeners
        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);

        // Setup navigation protection
        setupNavigationProtection();

        console.log("Offline mode initialized. Online status:", isOnline.value);
    }

    // Wrapper for API calls that handles offline mode
    const offlineApiCall = async (apiCall, queueOperationData) => {
        if (isOnline.value) {
            try {
                const result = await apiCall();
                return { success: true, data: result, offline: false };
            } catch (error) {
                // If online but request fails, queue it for retry
                if (queueOperationData) {
                    queueOperation(queueOperationData);
                }
                throw error;
            }
        } else {
            // Offline mode - queue the operation
            if (queueOperationData) {
                const operationId = queueOperation(queueOperationData);
                return { success: true, offline: true, operationId };
            }
            throw new Error("Offline mode: Operation queued");
        }
    };

    return {
        isOnline,
        pendingOperations,
        queueOperation,
        removeOperation,
        saveCart,
        loadCart,
        saveCustomer,
        loadCustomer,
        clearOfflineData,
        syncPendingOperations,
        offlineApiCall,
        loadPendingOperations,
    };
}
