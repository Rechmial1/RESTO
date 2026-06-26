<template>
    <div
        class="lg:w-6/12 flex flex-col bg-white border-l dark:border-gray-700 min-h-screen h-auto pr-4 px-2 py-4 dark:bg-gray-800"
    >
        <!-- Order Type -->
        <div
            class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center justify-between"
        >
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 dark:text-gray-400"
                    >Order Type:</span
                >
                <span
                    class="text-sm font-semibold text-gray-900 dark:text-white"
                >
                    {{ orderType }}
                </span>
            </div>
            <button
                type="button"
                @click="showOrderTypeModal = true"
                class="text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-full transition-all"
            >
                Change
            </button>
        </div>

        <!-- Order Header -->
        <div>
            <div class="mt-2">
                <a
                    href="javascript:;"
                    @click="$emit('show-add-customer')"
                    class="text-sm underline underline-offset-2 dark:text-gray-300"
                >
                    + Add Customer Details
                </a>
            </div>

            <div class="flex justify-between my-2 items-center">
                <div
                    class="font-medium py-2 inline-flex items-center gap-1 dark:text-neutral-200 relative group"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="currentColor"
                        class="bi bi-receipt w-6 h-6"
                        viewBox="0 0 16 16"
                    >
                        <path
                            d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27m.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0z"
                        ></path>
                        <path
                            d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5"
                        ></path>
                    </svg>
                    <span
                        :class="{
                            'text-yellow-600 dark:text-yellow-400': !isOnline,
                        }"
                    >
                        {{ formattedOrderNumber }}
                    </span>
                    <!-- Offline Warning Tooltip -->
                    <div
                        v-if="!isOnline"
                        class="absolute left-0 top-full mt-1 w-64 p-2 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none"
                    >
                        <div class="flex items-start gap-2">
                            <svg
                                class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <p
                                class="text-xs text-yellow-800 dark:text-yellow-300"
                            >
                                <strong>Offline Mode:</strong> This order number
                                is temporary and will change when the
                                application becomes online. The server will
                                assign the final order number after
                                synchronization.
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Table Display (only for dine_in) -->
                <div
                    v-if="
                        orderType === 'Dine In' ||
                        orderType === 'dine_in' ||
                        orderType?.toLowerCase() === 'dine in'
                    "
                    class="inline-flex items-center gap-2 dark:text-gray-300"
                >
                    <template v-if="currentTable">
                        <svg
                            fill="currentColor"
                            class="w-5 h-5 transition duration-75 group-hover:text-gray-900 dark:text-gray-200 dark:group-hover:text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 44.999 44.999"
                            xml:space="preserve"
                        >
                            <g stroke-width="0" />
                            <g stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="m42.558 23.378 2.406-10.92a1.512 1.512 0 0 0-2.954-.652l-2.145 9.733h-9.647a1.512 1.512 0 0 0 0 3.026h.573l-3.258 7.713a1.51 1.51 0 0 0 1.393 2.102c.59 0 1.15-.348 1.394-.925l2.974-7.038 4.717.001 2.971 7.037a1.512 1.512 0 1 0 2.787-1.177l-3.257-7.713h.573a1.51 1.51 0 0 0 1.473-1.187m-28.35 1.186h.573a1.512 1.512 0 0 0 0-3.026H5.134L2.99 11.806a1.511 1.511 0 1 0-2.954.652l2.406 10.92a1.51 1.51 0 0 0 1.477 1.187h.573L1.234 32.28a1.51 1.51 0 0 0 .805 1.98 1.515 1.515 0 0 0 1.982-.805l2.971-7.037 4.717-.001 2.972 7.038a1.514 1.514 0 0 0 1.982.805 1.51 1.51 0 0 0 .805-1.98z"
                            />
                            <path
                                d="M24.862 31.353h-.852V18.308h8.13a1.513 1.513 0 1 0 0-3.025H12.856a1.514 1.514 0 0 0 0 3.025h8.13v13.045h-.852a1.514 1.514 0 0 0 0 3.027h4.728a1.513 1.513 0 1 0 0-3.027"
                            />
                        </svg>
                        {{ currentTable }}

                        <button
                            type="button"
                            class="inline-flex items-center px-2 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            @click="showTableAssignmentModal = true"
                            title="Change Table"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="16"
                                height="16"
                                fill="currentColor"
                                class="bi bi-gear"
                                viewBox="0 0 16 16"
                            >
                                <path
                                    d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"
                                />
                                <path
                                    d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"
                                />
                            </svg>
                        </button>
                    </template>
                    <button
                        v-else
                        type="button"
                        class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                        @click="showTableAssignmentModal = true"
                    >
                        Set Table
                    </button>
                </div>
            </div>

            <!-- Pax and Waiter -->
            <div class="flex justify-between items-center gap-2">
                <div
                    class="py-2 inline-flex items-center gap-1 text-sm dark:text-gray-300"
                >
                    Pax
                    <input
                        type="number"
                        v-model="localPax"
                        @input="$emit('update:pax', localPax)"
                        class="w-14 px-2 py-1 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 text-sm focus:outline-none focus:ring-1 focus:ring-gray-500 dark:focus:ring-gray-400 focus:border-transparent"
                        step="1"
                        min="1"
                    />
                </div>
                <div class="gap-2 inline-flex items-center">
                    <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 relative"
                        @click="$emit('add-note')"
                        title="Add Note"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            fill="currentColor"
                            class="bi bi-pencil-square"
                            viewBox="0 0 16 16"
                        >
                            <path
                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"
                            ></path>
                            <path
                                fill-rule="evenodd"
                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"
                            ></path>
                        </svg>
                    </button>
                    <div class="inline-flex items-center gap-2">
                        <svg
                            class="w-5 h-5 text-gray-700 dark:text-gray-200 hidden lg:block"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"
                            ></path>
                        </svg>
                        <span class="text-sm text-gray-600 dark:text-gray-300"
                            >Waiter:</span
                        >
                        <div class="relative">
                            <select
                                v-model="localWaiterId"
                                @change="
                                    $emit('update:waiterId', localWaiterId)
                                "
                                class="w-36 pl-2 pr-6 py-1 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 text-sm focus:outline-none focus:ring-1 focus:ring-gray-500 dark:focus:ring-gray-400 focus:border-transparent appearance-none cursor-pointer"
                            >
                                <option value="">Select Waiter</option>
                                <option
                                    v-for="waiter in waiters"
                                    :key="waiter.id"
                                    :value="waiter.id"
                                >
                                    {{ waiter.name }}
                                </option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-1 pointer-events-none"
                            >
                                <svg
                                    class="w-4 h-4 text-gray-400 dark:text-gray-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="props?.order"
            class="flex justify-between p-2 text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-700"
        >
            <div>KOT #{{ props.order }}</div>
        </div>

        <!-- Cart Items Table -->
        <div class="flex flex-col rounded overflow-visible">
            <table
                class="flex-1 min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600"
            >
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th
                            scope="col"
                            class="p-2 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 rtl:text-right ltr:text-left"
                        >
                            Item Name
                        </th>
                        <th
                            scope="col"
                            class="p-2 text-xs font-medium text-center text-gray-500 uppercase dark:text-gray-400"
                        >
                            Qty
                        </th>
                        <th
                            scope="col"
                            class="p-2 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400 hidden lg:table-cell"
                        >
                            Price
                        </th>
                        <th
                            scope="col"
                            class="p-2 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400"
                        >
                            Amount
                        </th>
                        <th
                            scope="col"
                            class="p-2 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right"
                        >
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700"
                >
                    <tr
                        v-if="cartItems.length === 0"
                        class="hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <td class="p-8 text-center" colspan="5">
                            <div
                                class="flex flex-col items-center justify-center space-y-3"
                            >
                                <svg
                                    class="w-12 h-12 text-gray-500 dark:text-gray-300"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                    ></path>
                                </svg>
                                <div
                                    class="text-gray-500 dark:text-gray-400 text-base"
                                >
                                    No record found
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-for="item in cartItems"
                        :key="item.id"
                        class="hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <!-- Item Name, Note, and Add Note UI -->
                        <td
                            class="flex flex-col p-2 mr-12 lg:min-w-20 relative"
                        >
                            <div
                                class="text-xs text-gray-900 dark:text-white inline-flex items-center lg:table-cell"
                            >
                                {{ item.name}}
                            </div>
                            <div
                                class="text-xs text-gray-600 dark:text-white inline-flex items-center"
                            >
                                <!-- Optionally show price/unit or item meta data here -->
                            </div>
                            <!-- Special Instructions (Note) UI for each cart item -->
                            <div
                                class="inline-flex items-center relative group"
                                v-cloak
                            >
                                <template
                                    v-if="
                                        item.note &&
                                        !item._showNoteInput &&
                                        !item._showNotePreview
                                    "
                                >
                                    <div
                                        class="flex items-center gap-2 cursor-pointer text-skin-base text-xs hover:text-skin-base/80 transition-all duration-200"
                                        @click="
                                            () => {
                                                item._showNotePreview = true;
                                            }
                                        "
                                        title="Special Instructions"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            fill="none"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 8h10M7 12h4m1 8-4-4H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3z"
                                            ></path>
                                        </svg>
                                        <span
                                            class="truncate max-w-[70px] md:max-w-64 lg:max-w-[70px]"
                                            >{{ item.note }}</span
                                        >
                                    </div>
                                </template>

                                <template
                                    v-else-if="
                                        !item.note &&
                                        !item._showNoteInput &&
                                        !item._showNotePreview
                                    "
                                >
                                    <button
                                        @click="
                                            () => {
                                                item._showNoteInput = true;
                                                item._activeNote =
                                                    item.note || '';
                                            }
                                        "
                                        class="inline-flex items-center gap-1 text-xs pt-1 text-gray-500 hover:text-skin-base transition-colors duration-200"
                                        title="Add Note"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            class="w-3.5 h-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 4v16m8-8H4"
                                            ></path>
                                        </svg>
                                        Add Note
                                    </button>
                                </template>

                                <!-- Note Preview Modal -->
                                <div
                                    v-if="item._showNotePreview"
                                    class="absolute top-0 left-0 z-10"
                                    @click.away="item._showNotePreview = false"
                                >
                                    <div
                                        class="bg-white dark:bg-gray-700 rounded-md shadow-md border border-gray-300 dark:border-gray-600 p-3 w-64 md:w-96"
                                    >
                                        <div
                                            class="text-sm dark:text-white mb-2 break-all"
                                        >
                                            {{ item.note }}
                                        </div>
                                        <div
                                            class="flex justify-end gap-2 dark:text-white"
                                        >
                                            <button
                                                @click="
                                                    () => {
                                                        item._showNotePreview = false;
                                                        item._showNoteInput = true;
                                                        item._activeNote =
                                                            item.note || '';
                                                    }
                                                "
                                                class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 rounded transition-colors duration-200"
                                            >
                                                <span
                                                    class="flex items-center gap-x-1"
                                                >
                                                    <svg
                                                        class="w-3 h-3"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        fill="none"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                        ></path>
                                                    </svg>
                                                    Update
                                                </span>
                                            </button>
                                            <button
                                                @click="
                                                    () => {
                                                        $emit('add-note', {
                                                            id: item.id,
                                                            note: '',
                                                        });
                                                        item._showNotePreview = false;
                                                    }
                                                "
                                                class="text-xs px-2 py-1 bg-red-50 hover:bg-red-100 dark:bg-red-700 dark:hover:bg-red-600 text-red-500 dark:text-red-300 rounded transition-colors duration-200"
                                                title="Delete"
                                            >
                                                <span
                                                    class="flex items-center gap-x-1"
                                                >
                                                    <svg
                                                        class="w-3 h-3"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        fill="none"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12"
                                                        ></path>
                                                    </svg>
                                                    Delete
                                                </span>
                                            </button>
                                            <button
                                                @click="
                                                    item._showNotePreview = false
                                                "
                                                class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 rounded transition-colors duration-200"
                                            >
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Note Input Modal -->
                                <div
                                    v-if="item._showNoteInput"
                                    class="fixed inset-0 z-40"
                                    @click="item._showNoteInput = false"
                                ></div>
                                <div
                                    v-if="item._showNoteInput"
                                    class="absolute top-0 left-full ml-2 z-50 min-w-[280px]"
                                    @click.stop
                                >
                                    <div
                                        class="flex items-center bg-white dark:bg-gray-700 rounded-md shadow-2xl border-2 border-gray-300 dark:border-gray-600 overflow-hidden"
                                        @click.stop
                                    >
                                        <input
                                            type="text"
                                            v-model="item._activeNote"
                                            class="w-64 md:w-80 p-2 border-none text-base focus:outline-none focus:ring-2 focus:ring-skin-base dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400"
                                            placeholder="Special Instructions? (e.g., no onions, extra spicy)"
                                            @keydown.enter="
                                                () => {
                                                    $emit('add-note', {
                                                        id: item.id,
                                                        note: item._activeNote,
                                                    });
                                                    item._showNoteInput = false;
                                                }
                                            "
                                            @keydown.escape="
                                                item._showNoteInput = false
                                            "
                                            autofocus
                                            :ref="
                                                (el) => {
                                                    if (
                                                        el &&
                                                        item._showNoteInput
                                                    )
                                                        el.focus();
                                                }
                                            "
                                        />
                                        <div
                                            class="flex items-center gap-1 pr-2"
                                        >
                                            <button
                                                @click.stop="
                                                    () => {
                                                        if (
                                                            item._activeNote &&
                                                            item._activeNote.trim()
                                                        ) {
                                                            $emit('add-note', {
                                                                id: item.id,
                                                                note: item._activeNote.trim(),
                                                            });
                                                        }
                                                        item._showNoteInput = false;
                                                    }
                                                "
                                                class="p-1.5 text-white rounded-md bg-skin-base hover:bg-skin-base/90 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-skin-base focus:ring-offset-2"
                                                title="Save"
                                                type="button"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-3 h-3"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="m5 13 4 4L19 7"
                                                    ></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click.stop="
                                                    () => {
                                                        item._showNoteInput = false;
                                                        item._activeNote =
                                                            item.note || '';
                                                    }
                                                "
                                                class="p-1.5 text-gray-500 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                                title="Cancel"
                                                type="button"
                                            >
                                                <svg
                                                    class="w-3 h-3"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6 18 18 6M6 6l12 12"
                                                    ></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Quantity Control -->
                        <td
                            class="p-2 text-base text-gray-900 whitespace-nowrap text-center"
                        >
                            <div
                                class="relative flex items-center max-w-[8rem] mx-auto"
                            >
                                <button
                                    type="button"
                                    @click="$emit('decrease-quantity', item.id)"
                                    class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-s-md p-3 h-8 relative"
                                >
                                    <svg
                                        class="w-2 h-2 text-gray-900 dark:text-white"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 18 2"
                                    >
                                        <path
                                            stroke="currentColor"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M1 1h16"
                                        ></path>
                                    </svg>
                                </button>
                                <input
                                    type="text"
                                    v-model.lazy="item.quantity"
                                    @change="
                                        $emit('add-note', {
                                            id: item.id,
                                            note: item.note,
                                            quantity: item.quantity,
                                        })
                                    "
                                    class="min-w-10 border-b border-t bg-white border-x-0 border-gray-300 h-8 text-center text-gray-900 text-sm block w-full py-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    min="1"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                />
                                <button
                                    type="button"
                                    @click="$emit('increase-quantity', item.id)"
                                    class="bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 border border-gray-300 rounded-e-md p-3 h-8 relative"
                                >
                                    <svg
                                        class="w-2 h-2 text-gray-900 dark:text-white"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 18 18"
                                    >
                                        <path
                                            stroke="currentColor"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 1v16M1 9h16"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <td
                            class="p-2 text-xs font-medium text-gray-700 whitespace-nowrap dark:text-white text-right hidden lg:table-cell"
                        >
                            {{ currencySymbol }} {{ formatPrice(item.price) }}
                        </td>
                        <td
                            class="p-2 text-xs font-medium text-gray-900 whitespace-nowrap dark:text-white text-right"
                        >
                            {{ currencySymbol }}
                            {{ formatPrice(item.price * item.quantity) }}
                        </td>
                        <td class="p-2 whitespace-nowrap text-right">
                            <button
                                class="rounded text-gray-800 dark:text-gray-400 border dark:border-gray-500 hover:bg-gray-200 dark:hover:bg-gray-900/20 p-2 relative"
                                @click="$emit('remove-item', item.id)"
                            >
                                <svg
                                    class="w-4 h-4 text-gray-700 dark:text-gray-200"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9 2a1 1 0 0 0-.894.553L7.382 4H4a1 1 0 0 0 0 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6a1 1 0 1 0 0-2h-3.382l-.724-1.447A1 1 0 0 0 11 2zM7 8a1 1 0 0 1 2 0v6a1 1 0 1 1-2 0zm5-1a1 1 0 0 0-1 1v6a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Order Summary -->
        <div class="lg:min-w-20">
            <div
                class="h-auto p-4 mt-3 select-none text-center bg-gray-50 rounded space-y-4 dark:bg-gray-700"
                v-if="cartItems.length > 0"
            >
                <div class="text-left">
                    <button
                        class="text-left inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg font-semibold text-sm text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        @click="showDiscountModal = true"
                    >
                        <svg
                            class="h-5 w-5 text-current me-1"
                            width="24"
                            height="24"
                            viewBox="0 0 16 16"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                        >
                            <path d="m7.25 14.25-5.5-5.5 7-7h5.5v5.5z" />
                            <circle cx="11" cy="5" r=".5" fill="#000" />
                        </svg>
                        Add Discount
                    </button>
                </div>

                <div
                    class="flex justify-between text-gray-500 text-sm dark:text-neutral-400"
                >
                    <div>Total Items</div>
                    <div>
                        {{ totalItems }}
                    </div>
                </div>
                <div
                    class="flex justify-between text-gray-500 text-sm dark:text-neutral-400"
                >
                    <div>Sub Total</div>
                    <div>{{ currencySymbol }}{{ formatPrice(subTotal) }}</div>
                </div>

                <div v-if="discountAmount && discountAmount > 0">
                    <div
                        class="flex justify-between text-green-500 text-sm dark:text-green-400"
                    >
                        <div class="inline-flex items-center gap-x-1">
                            Discount
                            <span v-if="discountType === 'percent'">
                                ({{ discountValue }}%)
                            </span>
                            <span
                                class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                                @click="$emit('remove-discount')"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </span>
                        </div>
                        <div>
                            -{{ currencySymbol
                            }}{{ formatPrice(discountAmount) }}
                        </div>
                    </div>
                </div>

                <div v-if="orderType === 'delivery'">
                    <div
                        class="flex justify-between items-center text-gray-500 text-sm dark:text-neutral-400"
                    >
                        <div>
                            Delivery Fee
                            <span
                                v-if="deliveryFee === 0"
                                class="text-xs text-gray-400"
                            >
                                (Free Delivery)
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    class="w-16 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-gray-500 dark:focus:border-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 rounded-md shadow-sm"
                                    :value="deliveryFee"
                                    @input="
                                        $emit(
                                            'update:deliveryFee',
                                            parseFloat($event.target.value) || 0
                                        )
                                    "
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="extraCharges && extraCharges.length > 0">
                    <div v-for="charge in extraCharges" :key="charge.id">
                        <div
                            class="flex justify-between text-gray-500 text-sm dark:text-neutral-400"
                        >
                            <div class="inline-flex items-center gap-x-1">
                                {{ charge.name || charge.charge_name }}
                                <span v-if="charge.charge_type === 'percent'">
                                    ({{ charge.value || charge.charge_value }}%)
                                </span>
                                <span
                                    class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                                    @click="
                                        $emit('remove-extra-charge', charge.id)
                                    "
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                            </div>

                            <div>
                                ${{
                                    charge.charge_type === "percent"
                                        ? ((subTotal - discountAmount) *
                                              charge.charge_value) /
                                          100
                                        : subTotal -
                                          discountAmount +
                                          charge.charge_value
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="taxes.length > 0">
                    <div v-for="tax in taxes" :key="tax.id">
                        <div
                            class="flex justify-between text-gray-500 text-sm dark:text-neutral-400"
                        >
                            <div>
                                {{ tax.tax_name }} ({{
                                    tax.rate || tax.tax_percent
                                }}%)
                            </div>
                            <div>
                                {{ currencySymbol }}
                                {{ formatPrice(tax.amount) }}
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="totalTaxAmount > 0"
                        class="flex justify-between text-gray-500 text-sm dark:text-neutral-400 mt-3"
                    >
                        <div>
                            Total Tax
                            <span
                                v-if="isInclusive"
                                class="text-xs text-gray-400"
                            >
                                (Tax Inclusive)
                            </span>
                            <span v-else class="text-xs text-gray-400">
                                (Tax Exclusive)
                            </span>
                        </div>
                        <div>
                            {{ currencySymbol }}
                            {{ formatPrice(totalTaxAmount) }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-between font-medium dark:text-neutral-300"
                >
                    <div>Total</div>
                    <div>{{ currencySymbol }} {{ formatPrice(total) }}</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                class="h-auto pb-4 pt-3 select-none text-center w-full mb-16 md:mb-0"
            >
                <div class="flex gap-3">
                    <button
                        class="rounded bg-gray-700 text-white w-full p-2 relative"
                        @click="handleSaveOrder('kot')"
                        :disabled="saving"
                        :class="{ 'opacity-50 cursor-not-allowed': saving }"
                    >
                        <span v-if="!saving">KOT</span>
                        <span v-else class="inline-flex items-center">
                            <svg
                                class="animate-spin -ml-1 mr-1 h-4 w-4 inline-flex text-white"
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
                            KOT
                        </span>
                    </button>
                    <button
                        class="rounded bg-gray-700 text-white w-full p-2 relative"
                        @click="handleSaveOrder('kot', 'print')"
                        :disabled="saving"
                        :class="{ 'opacity-50 cursor-not-allowed': saving }"
                    >
                        <span v-if="!saving">KOT &amp; Print</span>
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
                            KOT &amp; Print
                        </span>
                    </button>
                    <button
                        class="rounded bg-gray-700 text-white w-full p-2 relative"
                        @click="handleSaveOrder('kot', 'bill', 'payment')"
                        :disabled="saving"
                        :class="{ 'opacity-50 cursor-not-allowed': saving }"
                    >
                        <span v-if="!saving">KOT, Bill &amp; Payment</span>
                        <span v-else class="inline-flex items-center">
                            <svg
                                class="animate-spin inline-flex -ml-1 mr-2 h-4 w-4 text-white"
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
                            KOT, Bill &amp; Payment
                        </span>
                    </button>
                </div>
                <div class="flex gap-3 mt-3">
                    <button
                        class="rounded bg-skin-base text-white w-full p-2 relative"
                        @click="handleSaveOrder('bill')"
                        :disabled="saving"
                        :class="{ 'opacity-50 cursor-not-allowed': saving }"
                    >
                        <span v-if="!saving">BILL</span>
                        <span v-else class="inline-flex items-center">
                            <svg
                                class="animate-spin inline-flex items-center -ml-1 mr-2 h-4 w-4 text-white"
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
                            BILL
                        </span>
                    </button>
                    <button
                        class="rounded bg-green-500 text-white w-full p-2 relative"
                        @click="handleSaveOrder('bill', 'payment')"
                        :disabled="saving"
                        :class="{ 'opacity-50 cursor-not-allowed': saving }"
                    >
                        <span v-if="!saving">Bill &amp; Payment</span>
                        <span v-else class="inline-flex items-center">
                            <svg
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-flex items-center"
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
                            Bill &amp; Payment
                        </span>
                    </button>
                    <button
                        class="rounded bg-blue-500 text-white w-full p-2 relative"
                        @click="handleSaveOrder('bill', 'print')"
                        :disabled="saving"
                        :class="{ 'opacity-50 cursor-not-allowed': saving }"
                    >
                        <span v-if="!saving">Bill &amp; Print</span>
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
                            Bill &amp; Print
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Discount Modal -->
        <DiscountModal
            :show="showDiscountModal"
            @close="showDiscountModal = false"
            @save="handleApplyDiscount"
        />

        <!-- Order Type Modal -->
        <OrderTypeModal
            :show="showOrderTypeModal"
            @close="showOrderTypeModal = false"
            @select="handleSelectOrderType"
        />

        <!-- Table Assignment Modal -->
        <TableAssignmentModal
            :show="showTableAssignmentModal"
            @close="showTableAssignmentModal = false"
            @select="handleSelectTable"
            @open-order="handleOpenOrder"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import DiscountModal from "./DiscountModal.vue";
import OrderTypeModal from "./OrderTypeModal.vue";
import TableAssignmentModal from "./TableAssignmentModal.vue";

const props = defineProps({
    orderType: {
        type: String,
        default: "Dine In",
    },
    orderNumber: {
        type: [String, Number],
        default: "",
    },
    currentTable: {
        type: String,
        default: "",
    },
    pax: {
        type: Number,
        default: 1,
    },
    waiterId: {
        type: [String, Number],
        default: "",
    },
    waiters: {
        type: Array,
        default: () => [],
    },
    cartItems: {
        type: Array,
        default: () => [],
    },
    taxes: {
        type: Array,
        default: () => [],
    },
    saving: {
        type: Boolean,
        default: false,
    },
    discountAmount: {
        type: Number,
        default: 0,
    },
    discountType: {
        type: String,
        default: "",
    },
    discountValue: {
        type: Number,
        default: 0,
    },
    deliveryFee: {
        type: Number,
        default: 0,
    },
    extraCharges: {
        type: Array,
        default: () => [],
    },
    isInclusive: {
        type: Boolean,
        default: false,
    },
    totalTaxAmount: {
        type: Number,
        default: 0,
    },
    currencySymbol: {
        type: String,
        default: "$",
    },
    isOnline: {
        type: Boolean,
        default: true,
    },
    order: {
        type: Object,
        default: () => null,
    },
});

const emit = defineEmits([
    "change-order-type",
    "update:orderType",
    "show-add-customer",
    "assign-table",
    "select-table",
    "open-order",
    "update:pax",
    "update:waiterId",
    "add-note",
    "increase-quantity",
    "decrease-quantity",
    "remove-item",
    "save-order",
    "remove-discount",
    "remove-extra-charge",
    "update:deliveryFee",
    "update:extraCharges",
    "apply-discount",
]);

const localPax = ref(props.pax);
const localWaiterId = ref(props.waiterId);
const showDiscountModal = ref(false);
const showOrderTypeModal = ref(false);
const showTableAssignmentModal = ref(false);
const formattedOrderNumber = ref(props.orderNumber || "");

watch(
    () => props.pax,
    (newVal) => {
        localPax.value = newVal;
    }
);

watch(
    () => props.waiterId,
    (newVal) => {
        localWaiterId.value = newVal;
    }
);

const totalItems = computed(() => {
    return props.cartItems.reduce((sum, item) => sum + item.quantity, 0);
});

const subTotal = computed(() => {
    return props.cartItems.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
    );
});

const total = computed(() => {
    let calculatedTotal = subTotal.value;

    // Subtract discount
    if (props.discountAmount && props.discountAmount > 0) {
        calculatedTotal -= props.discountAmount;
    }

    // Add delivery fee
    if (props.deliveryFee && props.deliveryFee > 0) {
        calculatedTotal += props.deliveryFee;
    }

    // Add extra charges
    if (props.extraCharges && props.extraCharges.length > 0) {
        const extraChargesTotal = props.extraCharges.reduce(
            (sum, charge) => sum + (charge.amount || 0),
            0
        );
        calculatedTotal += extraChargesTotal;
    }

    // Add taxes
    const taxTotal = props.taxes.reduce(
        (sum, tax) => sum + (tax.amount || 0),
        0
    );
    calculatedTotal += taxTotal;

    return Math.max(0, calculatedTotal);
});

const formatPrice = (price) => {
    return parseFloat(price).toFixed(2);
};

// Fetch extra charges based on order type
const fetchExtraCharges = async (orderTypeValue) => {
    try {
        // Normalize order type for API (e.g., "Dine In" -> "dine_in", "Delivery" -> "delivery")
        let normalizedOrderType = orderTypeValue;
        if (orderTypeValue.includes(" ")) {
            normalizedOrderType = orderTypeValue
                .toLowerCase()
                .replace(/\s+/g, "_");
        } else {
            normalizedOrderType = orderTypeValue.toLowerCase();
        }

        // Handle display formats
        if (normalizedOrderType === "dine in") {
            normalizedOrderType = "dine_in";
        }

        const response = await axios.get(
            `/api/pos/extra-charges/${normalizedOrderType}`
        );

        if (response.data) {
            emit("update:extraCharges", response.data);
        }
    } catch (error) {
        console.error("Error fetching extra charges:", error);
        // Emit empty array on error
        emit("update:extraCharges", []);
    }
};

// Watch for order type changes and fetch extra charges
watch(
    () => props.orderType,
    (newOrderType) => {
        if (newOrderType) {
            fetchExtraCharges(newOrderType);
        }
    },
    { immediate: false }
);

// Fetch formatted order number
const fetchOrderNumber = async () => {
    try {
        const response = await axios.get("/api/pos/get-order-number");
        // API returns array format: [order_number, formatted_order_number]
        if (Array.isArray(response.data) && response.data.length >= 2) {
            formattedOrderNumber.value =
                response.data[1] || response.data[0] || "";
        } else if (response.data?.formatted_order_number) {
            formattedOrderNumber.value = response.data.formatted_order_number;
        } else if (response.data?.order_number) {
            formattedOrderNumber.value = response.data.order_number;
        } else {
            formattedOrderNumber.value = props.orderNumber || "";
        }
        console.log("Fetched order number:", formattedOrderNumber.value);
    } catch (error) {
        console.error("Error fetching order number:", error);
        formattedOrderNumber.value = props.orderNumber || "";
    }
};

// Watch for orderNumber prop changes
watch(
    () => props.orderNumber,
    (newVal) => {
        if (!newVal) {
            // If orderNumber is empty, fetch a new one
            fetchOrderNumber();
        } else {
            formattedOrderNumber.value = newVal;
        }
    },
    { immediate: true }
);

// Fetch extra charges on mount
onMounted(() => {
    if (props.orderType) {
        fetchExtraCharges(props.orderType);
    }
    // Fetch order number if not provided
    if (!props.orderNumber) {
        fetchOrderNumber();
    }
});

// Handle discount application
const handleApplyDiscount = (discountData) => {
    emit("apply-discount", discountData);
    showDiscountModal.value = false;
};

// Handle order type selection
const handleSelectOrderType = (orderTypeData) => {
    emit("update:orderType", orderTypeData.displayType);
    showOrderTypeModal.value = false;
};

// Handle table selection
const handleSelectTable = (table) => {
    emit("select-table", table);
    showTableAssignmentModal.value = false;
};

const handleOpenOrder = (orderId) => {
    emit("open-order", orderId);
    showTableAssignmentModal.value = false;
};

// Handle save order with validation
const handleSaveOrder = (...actions) => {
    // Validate that there are items in the cart
    if (!props.cartItems || props.cartItems.length === 0) {
        alert("You need to add items to the order.");
        return;
    }

    // Emit the save-order event with actions
    emit("save-order", ...actions);
};
</script>

<style scoped></style>
