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
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto overflow-y-auto"
        >
            <div class="px-6 py-4">
                <div
                    class="text-lg font-medium text-gray-900 dark:text-gray-100"
                >
                    Add Discount
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="mt-4 flex gap-2">
                        <!-- Discount Value -->
                        <input
                            type="number"
                            v-model="discountForm.value"
                            class="w-2/3 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:border-transparent text-sm"
                            id="discountValue"
                            step="0.01"
                            placeholder="Enter Discount Value"
                            min="0"
                            autocomplete="off"
                        />

                        <!-- Discount Type -->
                        <select
                            class="w-1/3 px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:border-transparent text-sm"
                            id="discountType"
                            v-model="discountForm.type"
                        >
                            <option value="fixed">Fixed</option>
                            <option value="percent">Percent</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end"
            >
                <div class="flex justify-end gap-2 w-full">
                    <button
                        type="button"
                        class="button-cancel inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="text-white justify-center bg-skin-base hover:bg-skin-base/[.8] sm:w-auto dark:bg-skin-base dark:hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm px-3 py-2 text-center rtl:space-x-reverse"
                        @click="handleSave"
                        :disabled="
                            saving ||
                            !discountForm.value ||
                            discountForm.value <= 0
                        "
                    >
                        <span v-if="!saving">Apply Discount</span>
                        <span v-else class="inline-flex items-center">
                            <svg
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                            Applying...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "save"]);

const saving = ref(false);
const discountForm = ref({
    value: "",
    type: "fixed",
});

// Reset form when modal closes
watch(
    () => props.show,
    (isShowing) => {
        if (!isShowing) {
            discountForm.value = {
                value: "",
                type: "fixed",
            };
        }
    }
);

const handleClose = () => {
    emit("close");
};

const handleSave = async () => {
    if (!discountForm.value.value || discountForm.value.value <= 0) {
        return;
    }

    saving.value = true;
    try {
        await emit("save", {
            value: parseFloat(discountForm.value.value),
            type: discountForm.value.type,
        });
    } finally {
        saving.value = false;
    }
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
