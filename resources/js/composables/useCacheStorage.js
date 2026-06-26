import { ref } from "vue";

const CACHE_KEYS = {
    MENUS: "pos_menus",
    CATEGORIES: "pos_categories",
    MENU_ITEMS: "pos_menu_items",
    WAITERS: "pos_waiters",
    CACHE_TIMESTAMP: "pos_cache_timestamp",
};

// Cache expiration time (24 hours in milliseconds)
const CACHE_EXPIRATION = 24 * 60 * 60 * 1000;

/**
 * Check if cache exists and is still valid
 */
export function isCacheValid(key) {
    try {
        const timestamp = localStorage.getItem(`${key}_timestamp`);
        if (!timestamp) return false;

        const cacheTime = parseInt(timestamp, 10);
        const now = Date.now();
        return now - cacheTime < CACHE_EXPIRATION;
    } catch (error) {
        console.error("Error checking cache validity:", error);
        return false;
    }
}

/**
 * Get data from cache
 */
export function getFromCache(key) {
    try {
        const cached = localStorage.getItem(key);
        if (!cached) return null;

        if (!isCacheValid(key)) {
            // Cache expired, remove it
            localStorage.removeItem(key);
            localStorage.removeItem(`${key}_timestamp`);
            return null;
        }

        return JSON.parse(cached);
    } catch (error) {
        console.error("Error reading from cache:", error);
        return null;
    }
}

/**
 * Save data to cache
 */
export function saveToCache(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
        localStorage.setItem(`${key}_timestamp`, Date.now().toString());
        return true;
    } catch (error) {
        console.error("Error saving to cache:", error);
        // If storage quota exceeded, try to clear old cache
        if (error.name === "QuotaExceededError") {
            clearExpiredCache();
            try {
                localStorage.setItem(key, JSON.stringify(data));
                localStorage.setItem(`${key}_timestamp`, Date.now().toString());
                return true;
            } catch (retryError) {
                console.error(
                    "Error saving to cache after cleanup:",
                    retryError
                );
                return false;
            }
        }
        return false;
    }
}

/**
 * Clear expired cache entries
 */
export function clearExpiredCache() {
    try {
        Object.values(CACHE_KEYS).forEach((key) => {
            if (key !== CACHE_KEYS.CACHE_TIMESTAMP && !isCacheValid(key)) {
                localStorage.removeItem(key);
                localStorage.removeItem(`${key}_timestamp`);
            }
        });
    } catch (error) {
        console.error("Error clearing expired cache:", error);
    }
}

/**
 * Clear all POS cache
 */
export function clearAllCache() {
    try {
        Object.values(CACHE_KEYS).forEach((key) => {
            localStorage.removeItem(key);
            localStorage.removeItem(`${key}_timestamp`);
        });
        return true;
    } catch (error) {
        console.error("Error clearing all cache:", error);
        return false;
    }
}

/**
 * Get cache size information
 */
export function getCacheInfo() {
    try {
        const info = {
            menus: getFromCache(CACHE_KEYS.MENUS)?.length || 0,
            categories: getFromCache(CACHE_KEYS.CATEGORIES)?.length || 0,
            menuItems: getFromCache(CACHE_KEYS.MENU_ITEMS)?.length || 0,
            waiters: getFromCache(CACHE_KEYS.WAITERS)?.length || 0,
            totalSize: 0,
        };

        // Calculate approximate size in bytes
        Object.values(CACHE_KEYS).forEach((key) => {
            const data = localStorage.getItem(key);
            if (data) {
                info.totalSize += new Blob([data]).size;
            }
        });

        return info;
    } catch (error) {
        console.error("Error getting cache info:", error);
        return null;
    }
}

/**
 * Composable for cache management
 */
export function useCacheStorage() {
    const cacheInfo = ref(getCacheInfo());

    const loadMenus = () => {
        return getFromCache(CACHE_KEYS.MENUS) || [];
    };

    const loadCategories = () => {
        return getFromCache(CACHE_KEYS.CATEGORIES) || [];
    };

    const loadMenuItems = () => {
        return getFromCache(CACHE_KEYS.MENU_ITEMS) || [];
    };

    const loadWaiters = () => {
        return getFromCache(CACHE_KEYS.WAITERS) || [];
    };

    const saveMenus = (data) => {
        const saved = saveToCache(CACHE_KEYS.MENUS, data);
        if (saved) cacheInfo.value = getCacheInfo();
        return saved;
    };

    const saveCategories = (data) => {
        const saved = saveToCache(CACHE_KEYS.CATEGORIES, data);
        if (saved) cacheInfo.value = getCacheInfo();
        return saved;
    };

    const saveMenuItems = (data) => {
        const saved = saveToCache(CACHE_KEYS.MENU_ITEMS, data);
        if (saved) cacheInfo.value = getCacheInfo();
        return saved;
    };

    const saveWaiters = (data) => {
        const saved = saveToCache(CACHE_KEYS.WAITERS, data);
        if (saved) cacheInfo.value = getCacheInfo();
        return saved;
    };

    const clearCache = () => {
        const cleared = clearAllCache();
        if (cleared) cacheInfo.value = getCacheInfo();
        return cleared;
    };

    const refreshCacheInfo = () => {
        cacheInfo.value = getCacheInfo();
    };

    return {
        cacheInfo,
        loadMenus,
        loadCategories,
        loadMenuItems,
        loadWaiters,
        saveMenus,
        saveCategories,
        saveMenuItems,
        saveWaiters,
        clearCache,
        refreshCacheInfo,
        isCacheValid,
        clearExpiredCache,
    };
}
