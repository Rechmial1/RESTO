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
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-md sm:max-h-2xl sm:mx-auto overflow-y-auto">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Change Table
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="space-y-4">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-amber-100" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Confirm Table Change
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <p>Current Table: <strong>{{ currentTable }}</strong></p>
                                <p>New Table: <strong>{{ newTable }}</strong></p>
                            </div>
                        </div>

                        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                            <p class="text-sm text-amber-700 dark:text-amber-300 text-center">
                                This action will change the table assignment for the current order.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
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
                        class="text-white justify-center bg-skin-base hover:bg-skin-base/[.8] sm:w-auto dark:bg-skin-base dark:hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm px-3 py-2 text-center rtl:space-x-reverse bg-amber-600 hover:bg-amber-700" 
                        @click="handleConfirm"
                    >
                        Change Table
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    currentTable: {
        type: [String, Number],
        default: ''
    },
    newTable: {
        type: [String, Number],
        default: ''
    }
})

const emit = defineEmits(['close', 'confirm'])

const handleClose = () => {
    emit('close')
}

const handleConfirm = () => {
    emit('confirm')
}

// Close on Escape key
if (typeof window !== 'undefined') {
    watch(() => props.show, (isShowing) => {
        if (isShowing) {
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    handleClose()
                }
            }
            window.addEventListener('keydown', handleEscape)
            return () => {
                window.removeEventListener('keydown', handleEscape)
            }
        }
    })
}
</script>

<style scoped></style>

