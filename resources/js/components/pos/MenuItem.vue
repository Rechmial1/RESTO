<template>
    <li class="group relative">
        <input
            type="checkbox"
            :id="`item-${item.id}`"
            :value="item.id"
            @click="handleClick"
            class="hidden peer"
            :disabled="loading"
        />
        <label
            :for="`item-${item.id}`"
            class="block w-full rounded-lg shadow-sm transition-all duration-100 dark:shadow-gray-700 relative outline-none cursor-pointer hover:shadow-md dark:hover:bg-gray-700/30 peer-checked:ring-2 peer-checked:ring-skin-base active:scale-95 focus-visible:scale-95 focus-visible:ring-2 focus-visible:ring-skin-base bg-white dark:bg-gray-900"
            tabindex="0"
        >
            <!-- Loading Overlay -->
            <div
                v-if="loading"
                class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-lg z-10 flex items-center justify-center"
            >
                <svg
                    class="animate-spin h-6 w-6 text-skin-base"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>

            <!-- Image -->
            <div
                v-if="item.image"
                class="relative aspect-square hidden md:block"
            >
                <img
                    class="w-full h-full object-cover rounded-t-lg"
                    :src="item.item_photo_url"
                    :alt="item.item_name"
                />
                <span
                    v-if="item.type"
                    class="absolute top-1 right-1 bg-white/90 dark:bg-gray-800/90 rounded-full p-1 shadow-sm"
                >
                    <img
                        :src="
                            item.type === 'veg'
                                ? '/img/veg.svg'
                                : '/img/non-veg.svg'
                        "
                        class="h-4 w-4"
                        :title="item.type === 'veg' ? 'Veg' : 'Non Veg'"
                        alt=""
                    />
                </span>
            </div>

            <!-- Content -->
            <div class="p-2">
                <h5
                    class="text-sm font-medium text-gray-900 dark:text-white min-h-[2.5rem]"
                >
                    {{ item.item_name }}
                </h5>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <span
                        class="text-base font-semibold text-gray-900 dark:text-white"
                    >
                        {{ currencySymbol }} {{ formatPrice(item.price) }}
                    </span>
                </div>
            </div>
        </label>
    </li>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
    item: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            name: "",
            price: 0,
            image: null,
            veg_type: null,
            variant_id: 0,
            modifier_id: 0,
        }),
    },
    currencySymbol: {
        type: String,
        default: "$",
    },
});

const emit = defineEmits(["add-to-cart"]);

const loading = ref(false);

const handleClick = async () => {
    loading.value = true;
    try {
        await emit(
            "add-to-cart",
            props.item.id,
            props.item.variant_id || 0,
            props.item.modifier_id || 0
        );
    } finally {
        // Keep loading state for a brief moment for UX
        setTimeout(() => {
            loading.value = false;
        }, 200);
    }
};

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2);
};
</script>

<style scoped></style>
