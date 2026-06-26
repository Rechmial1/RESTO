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
            class="mb-6 bg-white dark:bg-gray-900 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-lg sm:mx-auto"
        >
            <div class="px-6 py-4">
                <div
                    class="text-lg font-medium text-gray-900 dark:text-gray-100"
                >
                    Add Note
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <label
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300 ltr:text-left rtl:text-right"
                            for="orderNote"
                        >
                            Order Note
                        </label>

                        <textarea
                            id="orderNote"
                            v-model="localNote"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-600 focus:border-transparent text-sm resize-y"
                            rows="3"
                            data-gramm="false"
                            placeholder="Enter order note..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end gap-3"
            >
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    @click="handleClose"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-800 dark:bg-gray-700 border border-transparent rounded-md hover:bg-gray-900 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    @click="handleSave"
                >
                    Save
                </button>
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
    note: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["close", "save"]);

const localNote = ref(props.note);

// Watch for prop changes
watch(
    () => props.note,
    (newVal) => {
        localNote.value = newVal;
    }
);

// Watch for modal opening to reset note
watch(
    () => props.show,
    (isShowing) => {
        if (isShowing) {
            localNote.value = props.note || "";
        }
    }
);

const handleClose = () => {
    emit("close");
};

const handleSave = () => {
    emit("save", localNote.value);
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
