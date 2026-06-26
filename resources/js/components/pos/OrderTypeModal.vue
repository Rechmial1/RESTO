<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-50 p-4"
        @click.self="handleClose"
    >
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden"
        >
            <!-- Header -->
            <div
                class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-900"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h2
                            class="text-2xl font-bold text-gray-900 dark:text-white mb-1 truncate"
                        >
                            {{
                                selectionStage === "order_type"
                                    ? "Select Order Type"
                                    : "Select Delivery Platform"
                            }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{
                                selectionStage === "order_type"
                                    ? "Choose your order type to proceed"
                                    : "Choose a delivery platform for your order"
                            }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Back Button (only when in delivery platform stage) -->
                        <button
                            v-if="selectionStage === 'delivery_platform'"
                            @click="goBackToOrderTypes"
                            class="p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg transition-all"
                            title="Back to Order Types"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                fill="currentColor"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"
                                />
                            </svg>
                        </button>

                        <!-- Reset Button (only when something is selected) -->
                        <button
                            v-if="selectedOrderTypeSlug"
                            @click="resetSelection"
                            class="p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg transition-all"
                            title="Reset Selection"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                fill="currentColor"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"
                                />
                                <path
                                    d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"
                                />
                            </svg>
                        </button>

                        <!-- Dashboard Link -->
                        <a
                            href="/dashboard"
                            class="inline-flex items-center gap-2 p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg transition-all"
                            title="Dashboard"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                fill="currentColor"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"
                                />
                            </svg>
                            Dashboard
                        </a>

                        <!-- Orders Link -->
                        <a
                            href="/orders"
                            class="inline-flex items-center gap-2 p-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg transition-all"
                            title="Orders"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                fill="currentColor"
                                viewBox="0 0 512.005 512.005"
                            >
                                <g>
                                    <g>
                                        <rect
                                            y="389.705"
                                            width="512.005"
                                            height="66.607"
                                        ></rect>
                                    </g>
                                </g>
                                <g>
                                    <g>
                                        <path
                                            d="M297.643,131.433c4.862-7.641,7.693-16.696,7.693-26.404c0-27.204-22.132-49.336-49.336-49.336 c-27.204,0-49.336,22.132-49.336,49.337c0,9.708,2.831,18.763,7.693,26.404C102.739,149.772,15.208,240.563,1.801,353.747h508.398 C496.792,240.563,409.261,149.772,297.643,131.433z M256,118.415c-7.38,0-13.384-6.005-13.384-13.385S248.62,91.646,256,91.646 s13.384,6.004,13.384,13.384S263.38,118.415,256,118.415z"
                                        ></path>
                                    </g>
                                </g>
                            </svg>
                            Orders
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Order Types Selection -->
                <div v-if="selectionStage === 'order_type'">
                    <div
                        v-if="loading"
                        class="flex items-center justify-center py-12"
                    >
                        <svg
                            class="animate-spin h-8 w-8 text-gray-400"
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

                    <div
                        v-else-if="orderTypes.length === 0"
                        class="col-span-full text-center py-12"
                    >
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-3"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="28"
                                height="28"
                                fill="currentColor"
                                class="text-gray-400"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374z"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-sm text-gray-600 dark:text-gray-400 font-medium"
                        >
                            No order types available
                        </p>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4"
                    >
                        <button
                            v-for="orderType in orderTypes"
                            :key="orderType.id"
                            @click="
                                handleSelectOrderType(
                                    orderType.id,
                                    orderType.slug
                                )
                            "
                            class="group relative flex flex-col items-center justify-center p-6 rounded-xl border-2 transition-all duration-200 cursor-pointer"
                            :class="{
                                'border-skin-base bg-skin-base/5 dark:bg-skin-base/10':
                                    selectedOrderTypeSlug === orderType.slug,
                                'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-skin-base/50 hover:shadow-lg hover:scale-[1.02]':
                                    selectedOrderTypeSlug !== orderType.slug,
                            }"
                        >
                            <!-- Checkmark indicator when selected -->
                            <div
                                v-if="selectedOrderTypeSlug === orderType.slug"
                                class="absolute top-2 right-2"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    fill="currentColor"
                                    class="text-skin-base"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"
                                    />
                                </svg>
                            </div>

                            <!-- Icon Container -->
                            <div
                                class="mb-3 p-3 rounded-lg transition-colors duration-200"
                                :class="{
                                    'bg-skin-base/10':
                                        selectedOrderTypeSlug ===
                                        orderType.slug,
                                    'bg-gray-50 dark:bg-gray-900 group-hover:bg-skin-base/5':
                                        selectedOrderTypeSlug !==
                                        orderType.slug,
                                }"
                            >
                                <!-- Dine In Icon -->
                                <svg
                                    v-if="orderType.slug === 'dine_in'"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="32"
                                    height="32"
                                    fill="currentColor"
                                    class="text-skin-base"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.37 2.37 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045A.5.5 0 0 0 1 5.37v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0M1.5 8.5A.5.5 0 0 1 2 9v6h1v-5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5h6V9a.5.5 0 0 1 1 0v6h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1V9a.5.5 0 0 1 .5-.5M4 15h3v-5H4zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"
                                    />
                                </svg>
                                <!-- Delivery Icon -->
                                <svg
                                    v-else-if="orderType.slug === 'delivery'"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="32"
                                    height="32"
                                    fill="currentColor"
                                    class="text-skin-base"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"
                                    />
                                </svg>
                                <!-- Pickup Icon -->
                                <svg
                                    v-else-if="orderType.slug === 'pickup'"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="32"
                                    height="32"
                                    fill="currentColor"
                                    class="text-skin-base"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0"
                                    />
                                    <path
                                        d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"
                                    />
                                </svg>
                                <!-- Default Icon for other order types -->
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 48 48"
                                    width="32"
                                    height="32"
                                    fill="currentColor"
                                    class="text-skin-base"
                                >
                                    <path
                                        d="M24 46a21.9 21.9 0 0 1-6.124-.865 1 1 0 0 1-.718-.889l-.92-12.915a1 1 0 0 1 .731-1.035A5.51 5.51 0 0 0 21 25c0-3.263-1.345-10-5.5-10S10 21.737 10 25a5.51 5.51 0 0 0 4.031 5.3 1 1 0 0 1 .731 1.035L14 41.966a1 1 0 0 1-1.522.781A22 22 0 1 1 46 24a21.87 21.87 0 0 1-10.48 18.747 1 1 0 0 1-1.52-.781l-.86-12.029a1 1 0 0 1 .77-1.046A3.98 3.98 0 0 0 37 25V15a1 1 0 0 1 2 0v10a5.97 5.97 0 0 1-3.812 5.584l.681 9.518A20 20 0 1 0 4 24a19.86 19.86 0 0 0 8.131 16.1l.581-8.144A7.52 7.52 0 0 1 8 25c0-4.64 2.036-12 7.5-12S23 20.36 23 25a7.52 7.52 0 0 1-4.712 6.958L19.1 43.4a20.24 20.24 0 0 0 9.794 0l.915-12.812A5.97 5.97 0 0 1 26 25V15a1 1 0 0 1 2 0v10a3.98 3.98 0 0 0 3.092 3.891 1 1 0 0 1 .77 1.046l-1.02 14.309a1 1 0 0 1-.718.889A21.9 21.9 0 0 1 24 46"
                                    />
                                    <path
                                        d="M34.25 22a1 1 0 0 1-1-1v-6a1 1 0 0 1 2 0v6a1 1 0 0 1-1 1m-3.5 0a1 1 0 0 1-1-1v-6a1 1 0 0 1 2 0v6a1 1 0 0 1-1 1"
                                    />
                                </svg>
                            </div>

                            <span
                                class="text-sm font-semibold text-gray-900 dark:text-white text-center"
                            >
                                {{ orderType.order_type_name }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Delivery Platform Selection -->
                <div v-else-if="selectionStage === 'delivery_platform'">
                    <div
                        v-if="loadingPlatforms"
                        class="flex items-center justify-center py-12"
                    >
                        <svg
                            class="animate-spin h-8 w-8 text-gray-400"
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

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Default Delivery Platform -->
                        <button
                            @click="handleSelectDeliveryPlatform('default')"
                            class="group text-left p-4 rounded-xl border-2 transition-all duration-200 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-skin-base/50 hover:shadow-lg hover:scale-[1.02]"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-12 w-12 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center flex-shrink-0"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        fill="currentColor"
                                        class="text-skin-base"
                                        viewBox="0 0 16 16"
                                    >
                                        <path
                                            d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"
                                        />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        Default Delivery Platform
                                    </p>
                                    <p
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Standard delivery pricing
                                    </p>
                                </div>
                            </div>
                        </button>

                        <!-- Delivery Platforms -->
                        <button
                            v-for="platform in deliveryPlatforms"
                            :key="platform.id"
                            @click="handleSelectDeliveryPlatform(platform.id)"
                            class="group text-left p-4 rounded-xl border-2 transition-all duration-200 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-skin-base/50 hover:shadow-lg hover:scale-[1.02]"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-12 w-12 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center overflow-hidden flex-shrink-0"
                                >
                                    <img
                                        v-if="platform.logo_url"
                                        :src="platform.logo_url"
                                        :alt="platform.name"
                                        class="h-12 w-12 object-contain"
                                    />
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        fill="currentColor"
                                        class="text-skin-base"
                                        viewBox="0 0 16 16"
                                    >
                                        <path
                                            d="M0 3a2 2 0 0 1 2-2h7.5a2 2 0 0 1 1.6.8l3 4A2 2 0 0 1 15.5 7H14v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm11 0H2a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7h2.5a1 1 0 0 0 .8-1.6l-3-4A1 1 0 0 0 11 3"
                                        />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                                    >
                                        {{ platform.name }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "select"]);

const orderTypes = ref([]);
const deliveryPlatforms = ref([]);
const loading = ref(false);
const loadingPlatforms = ref(false);
const selectionStage = ref("order_type"); // 'order_type' or 'delivery_platform'
const selectedOrderTypeSlug = ref(null);
const selectedOrderTypeId = ref(null);
const selectedDeliveryPlatform = ref(null);

// Fetch order types
const fetchOrderTypes = async () => {
    if (loading.value) return;
    loading.value = true;
    try {
        const response = await axios.get("/api/pos/order-types");
        if (response.data) {
            orderTypes.value = response.data;
        }
    } catch (error) {
        console.error("Error fetching order types:", error);
    } finally {
        loading.value = false;
    }
};

// Fetch delivery platforms
const fetchDeliveryPlatforms = async () => {
    if (loadingPlatforms.value) return;
    loadingPlatforms.value = true;
    try {
        const response = await axios.get("/api/pos/delivery-platforms");
        if (response.data) {
            deliveryPlatforms.value = response.data;
        }
    } catch (error) {
        console.error("Error fetching delivery platforms:", error);
    } finally {
        loadingPlatforms.value = false;
    }
};

// Handle order type selection
const handleSelectOrderType = (id, slug) => {
    selectedOrderTypeId.value = id;
    selectedOrderTypeSlug.value = slug;

    // If it's delivery, move to delivery platform selection stage
    if (slug === "delivery") {
        selectionStage.value = "delivery_platform";
        fetchDeliveryPlatforms();
    } else {
        // For non-delivery types, proceed directly
        proceedToPOS(id, slug, null);
    }
};

// Handle delivery platform selection
const handleSelectDeliveryPlatform = (platformId) => {
    selectedDeliveryPlatform.value = platformId;
    proceedToPOS(
        selectedOrderTypeId.value,
        selectedOrderTypeSlug.value,
        platformId
    );
};

// Proceed to POS
const proceedToPOS = (id, slug, deliveryPlatform) => {
    // Convert slug to display format
    let displayType = slug;
    if (slug === "dine_in") {
        displayType = "Dine In";
    } else if (slug === "delivery") {
        displayType = "Delivery";
    } else if (slug === "pickup") {
        displayType = "Pickup";
    }

    emit("select", {
        id,
        type: slug,
        displayType,
        deliveryPlatform,
    });
    handleClose();
};

// Go back to order types
const goBackToOrderTypes = () => {
    selectionStage.value = "order_type";
    selectedDeliveryPlatform.value = null;
};

// Reset selection
const resetSelection = () => {
    selectedOrderTypeId.value = null;
    selectedOrderTypeSlug.value = null;
    selectedDeliveryPlatform.value = null;
    selectionStage.value = "order_type";
};

// Handle close
const handleClose = () => {
    // Reset state
    resetSelection();
    emit("close");
};

// Watch for modal opening
watch(
    () => props.show,
    (isShowing) => {
        if (isShowing) {
            fetchOrderTypes();
            resetSelection();
        }
    }
);

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
