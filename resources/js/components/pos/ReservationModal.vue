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
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Reservation Confirmation
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="space-y-4">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-blue-100" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                This table has an active reservation
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <p>Reservation for: <strong>{{ reservation.customerName || 'N/A' }}</strong></p>
                                <p>Reservation time: <strong>{{ reservation.time || 'N/A' }}</strong></p>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <p class="text-sm text-gray-700 dark:text-gray-300 text-center">
                                Is this the same customer who made the reservation?
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
                <div class="flex justify-between w-full">
                    <button 
                        type="button" 
                        class="button-cancel inline-flex justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-3 py-2 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600" 
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <div class="flex gap-2">
                        <button 
                            type="button" 
                            class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150" 
                            @click="handleDifferentCustomer"
                        >
                            Different Customer
                        </button>
                        <button 
                            type="submit" 
                            class="text-white justify-center bg-skin-base hover:bg-skin-base/[.8] sm:w-auto dark:bg-skin-base dark:hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm px-3 py-2 text-center rtl:space-x-reverse" 
                            @click="handleSameCustomer"
                        >
                            Same Customer
                        </button>
                    </div>
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
    reservation: {
        type: Object,
        default: () => ({
            customerName: null,
            time: null
        })
    }
})

const emit = defineEmits(['close', 'confirm-same', 'confirm-different'])

const handleClose = () => {
    emit('close')
}

const handleSameCustomer = () => {
    emit('confirm-same')
}

const handleDifferentCustomer = () => {
    emit('confirm-different')
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

