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
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-2xl sm:mx-auto overflow-y-auto"
        >
            <form @submit.prevent="handleSave">
                <div class="px-6 py-4">
                    <!-- Header -->
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-skin-base rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Add Customer Details
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Search or create a new customer
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Search Section -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-2 mb-3">
                                <svg
                                    class="w-4 h-4 text-gray-600 dark:text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                                <h3 class="text-base font-medium text-gray-900 dark:text-white">Search Customer</h3>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    @input="handleSearch"
                                    class="block w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    placeholder="Search by name, phone, or email"
                                    autofocus
                                />
                            </div>

                            <!-- Search Results Dropdown -->
                            <div class="relative mt-3" v-if="searchQuery && searchQuery.length >= 2">
                                <div
                                    v-if="searchResults.length > 0"
                                    class="absolute z-50 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden"
                                >
                                    <div class="max-h-60 overflow-y-auto">
                                        <div class="p-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                Found {{ searchResults.length }} customer(s)
                                            </p>
                                        </div>
                                        <div
                                            v-for="result in searchResults"
                                            :key="result.id"
                                            @click="selectCustomer(result)"
                                            class="group flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors border-b border-gray-100 dark:border-gray-600 last:border-b-0"
                                        >
                                            <div class="flex-shrink-0 mr-3">
                                                <div class="w-8 h-8 rounded-full bg-skin-base flex items-center justify-center">
                                                    <span class="text-white font-medium text-sm">{{
                                                        result.name ? result.name.charAt(0).toUpperCase() : '?'
                                                    }}</span>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                    {{ result.name }}
                                                </p>
                                                <div class="flex flex-wrap gap-3">
                                                    <span
                                                        v-if="result.phone"
                                                        class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400"
                                                    >
                                                        <svg
                                                            class="w-3 h-3 mr-1 text-gray-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                                            />
                                                        </svg>
                                                        {{ result.phone }}
                                                    </span>
                                                    <span
                                                        v-if="result.email"
                                                        class="inline-flex items-center text-xs text-gray-600 dark:text-gray-400"
                                                    >
                                                        <svg
                                                            class="w-3 h-3 mr-1 text-gray-500"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                            />
                                                        </svg>
                                                        {{ result.email }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <svg
                                                    class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 5l7 7-7 7"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else-if="!loadingSearch"
                                    class="absolute z-50 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden"
                                >
                                    <div class="p-4 text-center">
                                        <div
                                            class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center"
                                        >
                                            <svg
                                                class="w-6 h-6 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                                />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            No customers found
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                            No customers matching "{{ searchQuery }}"
                                        </p>
                                        <button
                                            type="button"
                                            @click="createNewCustomer"
                                            class="inline-flex items-center px-3 py-2 bg-skin-base hover:bg-skin-base/80 text-white text-sm font-medium rounded-lg transition-colors"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                                />
                                            </svg>
                                            Create New Customer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Details Form -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-2">
                                    <svg
                                        class="w-4 h-4 text-gray-600 dark:text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Customer Details</h3>
                                </div>
                                <div v-if="selectedCustomerId" class="flex items-center space-x-2">
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                                    >
                                        <svg
                                            class="w-3 h-3 mr-1"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        Existing Customer
                                    </span>
                                    <button
                                        type="button"
                                        @click="clearSelection"
                                        class="text-xs text-skin-base hover:text-skin-base/80 dark:text-skin-base/40 dark:hover:text-skin-base/30 transition-colors"
                                    >
                                        Create New Instead
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Customer Name -->
                                <div class="space-y-1">
                                    <label
                                        for="customerName"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Name
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="customerName"
                                            v-model="customerForm.name"
                                            type="text"
                                            :readonly="selectedCustomerId && !editingFields.name"
                                            :class="[
                                                'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200',
                                                selectedCustomerId && !editingFields.name
                                                    ? 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                    : '',
                                            ]"
                                            placeholder="Enter customer name"
                                            required
                                        />
                                        <button
                                            v-if="selectedCustomerId"
                                            type="button"
                                            @click="toggleFieldEdit('name')"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                        >
                                            <svg
                                                v-if="editingFields.name"
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Phone Field -->
                                <div class="space-y-1">
                                    <label
                                        for="customerPhone"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Phone
                                    </label>
                                    <div class="flex gap-2">
                                        <!-- Phone Code Dropdown -->
                                        <div class="relative w-32" ref="phoneCodeDropdown">
                                            <div
                                                @click="phoneCodeIsOpen = !phoneCodeIsOpen"
                                                :class="[
                                                    'p-2 bg-gray-100 border rounded cursor-pointer dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
                                                    selectedCustomerId && !editingFields.phone
                                                        ? 'opacity-50 cursor-not-allowed'
                                                        : '',
                                                ]"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm">
                                                        {{ customerForm.phone_code ? '+' + customerForm.phone_code : 'Select' }}
                                                    </span>
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 9l-7 7-7-7"
                                                        />
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- Phone Code Dropdown Menu -->
                                            <ul
                                                v-if="phoneCodeIsOpen"
                                                class="absolute z-10 w-full mt-1 overflow-auto bg-white rounded-lg shadow-lg max-h-60 ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                <li class="sticky top-0 px-3 py-2 bg-white dark:bg-gray-900 z-10">
                                                    <input
                                                        v-model="phoneCodeSearch"
                                                        @input="filterPhoneCodes"
                                                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                                        type="text"
                                                        placeholder="Search"
                                                    />
                                                </li>
                                                <li
                                                    v-for="phonecode in filteredPhoneCodes"
                                                    :key="phonecode"
                                                    @click="selectPhoneCode(phonecode)"
                                                    :class="[
                                                        'relative py-2 pl-3 text-gray-900 transition-colors duration-150 cursor-pointer select-none pr-9 hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-300',
                                                        phonecode === customerForm.phone_code
                                                            ? 'bg-gray-100 dark:bg-gray-800'
                                                            : '',
                                                    ]"
                                                >
                                                    <div class="flex items-center">
                                                        <span class="block ml-3 text-sm whitespace-nowrap">+{{ phonecode }}</span>
                                                        <span
                                                            v-if="phonecode === customerForm.phone_code"
                                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-black dark:text-gray-300"
                                                        >
                                                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </li>
                                                <li
                                                    v-if="filteredPhoneCodes.length === 0"
                                                    class="relative py-2 pl-3 text-gray-500 cursor-default select-none pr-9 dark:text-gray-400"
                                                >
                                                    No phone codes found
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Phone Number Input -->
                                        <div class="flex-1 relative">
                                            <input
                                                id="customerPhone"
                                                v-model="customerForm.phone"
                                                type="tel"
                                                :readonly="selectedCustomerId && !editingFields.phone"
                                                :class="[
                                                    'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200',
                                                    selectedCustomerId && !editingFields.phone
                                                        ? 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                        : '',
                                                ]"
                                                placeholder="Enter phone number"
                                                required
                                            />
                                            <button
                                                v-if="selectedCustomerId"
                                                type="button"
                                                @click="toggleFieldEdit('phone')"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                            >
                                                <svg
                                                    v-if="editingFields.phone"
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                <svg
                                                    v-else
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Field -->
                            <div class="space-y-1">
                                <label
                                    for="customerEmail"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Email
                                </label>
                                <div class="relative">
                                    <input
                                        id="customerEmail"
                                        v-model="customerForm.email"
                                        type="email"
                                        :readonly="selectedCustomerId && !editingFields.email"
                                        :class="[
                                            'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200',
                                            selectedCustomerId && !editingFields.email
                                                ? 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                : '',
                                        ]"
                                        placeholder="Enter email address"
                                    />
                                    <button
                                        v-if="selectedCustomerId"
                                        type="button"
                                        @click="toggleFieldEdit('email')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg
                                            v-if="editingFields.email"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Address Field -->
                            <div class="space-y-1">
                                <label
                                    for="customerAddress"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Address
                                </label>
                                <div class="relative">
                                    <textarea
                                        id="customerAddress"
                                        v-model="customerForm.address"
                                        rows="3"
                                        :readonly="selectedCustomerId && !editingFields.address"
                                        :class="[
                                            'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none',
                                            selectedCustomerId && !editingFields.address
                                                ? 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                : '',
                                        ]"
                                        placeholder="Enter delivery address"
                                    ></textarea>
                                    <button
                                        v-if="selectedCustomerId"
                                        type="button"
                                        @click="toggleFieldEdit('address')"
                                        class="absolute top-2 right-2 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg
                                            v-if="editingFields.address"
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="flex items-center justify-between px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600"
                >
                    <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>Search or create a new customer</span>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            type="button"
                            @click="handleClose"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                        >
                            <svg
                                v-if="!saving"
                                class="w-4 h-4 mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span v-if="!saving">Save</span>
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
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import axios from "axios";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    customer: {
        type: Object,
        default: () => ({
            name: "",
            email: "",
            phone: "",
            phone_code: "",
            address: "",
        }),
    },
});

const emit = defineEmits(["close", "save"]);

const saving = ref(false);
const searchQuery = ref("");
const searchResults = ref([]);
const loadingSearch = ref(false);
const selectedCustomerId = ref(null);
const phoneCodeIsOpen = ref(false);
const phoneCodeSearch = ref("");
const allPhoneCodes = ref([]);
const filteredPhoneCodes = ref([]);
const phoneCodeDropdown = ref(null);

const customerForm = ref({
    name: "",
    email: "",
    phone: "",
    phone_code: "",
    address: "",
});

const editingFields = ref({
    name: false,
    phone: false,
    email: false,
    address: false,
});

let searchTimeout = null;

// Load phone codes on mount
onMounted(async () => {
    try {
        const response = await axios.get("/api/pos/phone-codes");
        allPhoneCodes.value = response.data || [];
        filteredPhoneCodes.value = allPhoneCodes.value;
        // Set default phone code (first one, or can be set from restaurant settings)
        if (customerForm.value.phone_code === "" && allPhoneCodes.value.length > 0) {
            customerForm.value.phone_code = allPhoneCodes.value[0];
        }
    } catch (error) {
        console.error("Error loading phone codes:", error);
    }
});

// Watch for prop changes to update form
watch(
    () => props.customer,
    (newCustomer) => {
        if (newCustomer) {
            customerForm.value = {
                name: newCustomer.name || "",
                email: newCustomer.email || "",
                phone: newCustomer.phone || "",
                phone_code: newCustomer.phone_code || allPhoneCodes.value[0] || "",
                address: newCustomer.address || newCustomer.delivery_address || "",
            };
        }
    },
    { immediate: true }
);

// Watch for modal show/hide
watch(
    () => props.show,
    (isShowing) => {
        if (!isShowing) {
            resetForm();
        }
    }
);

const handleSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    loadingSearch.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get("/api/pos/customers", {
                params: { search: searchQuery.value },
            });
            searchResults.value = response.data || [];
        } catch (error) {
            console.error("Error searching customers:", error);
            searchResults.value = [];
        } finally {
            loadingSearch.value = false;
        }
    }, 300);
};

const selectCustomer = (customer) => {
    selectedCustomerId.value = customer.id;
    customerForm.value = {
        name: customer.name || "",
        email: customer.email || "",
        phone: customer.phone || "",
        phone_code: customer.phone_code || allPhoneCodes.value[0] || "",
        address: customer.address || customer.delivery_address || "",
    };
    // Ensure phone_code is set even if customer doesn't have it
    if (!customerForm.value.phone_code && allPhoneCodes.value.length > 0) {
        customerForm.value.phone_code = allPhoneCodes.value[0];
    }
    searchQuery.value = "";
    searchResults.value = [];
    editingFields.value = {
        name: false,
        phone: false,
        email: false,
        address: false,
    };
};

const createNewCustomer = () => {
    const searchTerm = searchQuery.value;
    searchQuery.value = "";
    searchResults.value = [];
    selectedCustomerId.value = null;
    editingFields.value = {
        name: true,
        phone: true,
        email: true,
        address: true,
    };
    // Pre-fill name if search term looks like a name
    if (searchTerm && !/\d/.test(searchTerm)) {
        customerForm.value.name = searchTerm;
    }
};

const clearSelection = () => {
    selectedCustomerId.value = null;
    searchQuery.value = "";
    searchResults.value = [];
    editingFields.value = {
        name: true,
        phone: true,
        email: true,
        address: true,
    };
};

const toggleFieldEdit = (field) => {
    if (editingFields.value[field] !== undefined) {
        editingFields.value[field] = !editingFields.value[field];
    }
};

const filterPhoneCodes = () => {
    if (!phoneCodeSearch.value) {
        filteredPhoneCodes.value = allPhoneCodes.value;
        return;
    }
    filteredPhoneCodes.value = allPhoneCodes.value.filter((code) =>
        code.toString().includes(phoneCodeSearch.value)
    );
};

const selectPhoneCode = (phonecode) => {
    customerForm.value.phone_code = phonecode;
    phoneCodeIsOpen.value = false;
    phoneCodeSearch.value = "";
    filterPhoneCodes();
};

const resetForm = () => {
    customerForm.value = {
        name: "",
        email: "",
        phone: "",
        phone_code: allPhoneCodes.value[0] || "",
        address: "",
    };
    searchQuery.value = "";
    searchResults.value = [];
    selectedCustomerId.value = null;
    editingFields.value = {
        name: false,
        phone: false,
        email: false,
        address: false,
    };
    phoneCodeIsOpen.value = false;
    phoneCodeSearch.value = "";
};

const handleClose = () => {
    emit("close");
};

const handleSave = async () => {
    // Validate required fields - trim and check for non-empty strings
    const name = (customerForm.value.name || "").trim();
    const phone = (customerForm.value.phone || "").trim();
    const phone_code = (customerForm.value.phone_code || "").trim();

    // Debug logging
    console.log("Form data before validation:", {
        name,
        phone,
        phone_code,
        customerForm: customerForm.value,
    });

    if (!name || !phone || !phone_code) {
        const missingFields = [];
        if (!name) missingFields.push("Name");
        if (!phone) missingFields.push("Phone");
        if (!phone_code) missingFields.push("Phone Code");
        alert(`Please fill in all required fields: ${missingFields.join(", ")}`);
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post("/api/pos/customers", {
            name: name,
            phone: phone,
            phone_code: phone_code,
            email: (customerForm.value.email || "").trim() || null,
            address: (customerForm.value.address || "").trim() || null,
        });

        if (response.data.success) {
            emit("save", response.data.customer);
            handleClose();
        }
    } catch (error) {
        console.error("Error saving customer:", error);
        const errorMessage =
            error.response?.data?.message || 
            (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : null) ||
            error.message || 
            "Failed to save customer";
        alert(errorMessage);
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

// Handle click outside for phone code dropdown
const handleClickOutside = (event) => {
    if (
        phoneCodeDropdown.value &&
        !phoneCodeDropdown.value.contains(event.target) &&
        phoneCodeIsOpen.value
    ) {
        phoneCodeIsOpen.value = false;
    }
};

watch(
    () => props.show,
    (isShowing) => {
        if (isShowing) {
            document.addEventListener("click", handleClickOutside);
        } else {
            document.removeEventListener("click", handleClickOutside);
        }
    }
);

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped></style>
