<div>


    <div class="space-y-6 mt-10">

        <div class="gap-4 grid lg:grid-cols-3">

            <div>
                <div>
                    <x-input type="date" id="date" wire:model.live="date"
                            class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                    <x-input-error for="date" class="mt-1" />
                </div>
            </div>

            <!-- Guest Dropdown -->
            <div x-data="{ isOpen: false }" @click.away="isOpen = false" class="relative">
                <button @click="isOpen = !isOpen" type="button" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg text-lg text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 w-full justify-between">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people mr-2" viewBox="0 0 16 16">
                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                    </svg>
                    {{ $numberOfGuests }} @lang('modules.reservation.guests')
                    <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div x-show="isOpen" x-transition class="absolute z-20 mt-1 w-full bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 max-w-60">
                    <ul class="py-2 text-gray-700 dark:text-gray-200 max-h-72 overflow-auto">
                        @for ($i = 1; $i <= 30; $i++)
                        <li>
                            <a href="javascript:;" @click="isOpen = false; $wire.setReservationGuest('{{ $i }}')" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white text-md">{{ $i }} @lang('modules.reservation.guests')</a>
                        </li>
                        @endfor
                    </ul>
                </div>
                @error('numberOfGuests') <span class="text-sm text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Meal Type Dropdown -->
            <div x-data="{ isOpen: false }" @click.away="isOpen = false" class="relative">
                <button @click="isOpen = !isOpen" type="button" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-lg text-lg text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 w-full justify-between">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock mr-2" viewBox="0 0 16 16">
                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                    </svg>
                    @lang('modules.reservation.' . $slotType)
                    <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>

                <!-- Dropdown menu -->
                <div x-show="isOpen" x-transition class="absolute z-20 mt-1 w-full bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 max-w-60">
                    <ul class="py-2 text-gray-700 dark:text-gray-200 max-h-72 overflow-auto">
                        <li>
                            <a href="javascript:;" @click="isOpen = false; $wire.setReservationSlotType('Breakfast')" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white text-md">
                                @lang('modules.reservation.Breakfast')
                            </a>
                        </li>
                        <li>
                            <a href="javascript:;" @click="isOpen = false; $wire.setReservationSlotType('Lunch')" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white text-md">
                                @lang('modules.reservation.Lunch')
                            </a>
                        </li>
                        <li>
                            <a href="javascript:;" @click="isOpen = false; $wire.setReservationSlotType('Dinner')" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white text-md">
                                @lang('modules.reservation.Dinner')
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

            <h4 class="text-xl font-semibold dark:text-white mt-10">@lang('messages.selectTimeSlot')</h4>
            <div class="mt-2 space-y-2">
                @if (!empty($timeSlots))
                <ul class="grid w-full lg:gap-6 gap-4 lg:grid-cols-6">
                    @foreach ($timeSlots as $index => $timeSlot)
                    <li wire:key="timeSlot.{{ $index }}.{{ microtime() }}">
                        <input
                            type="radio"
                            id="timeSlot{{ $index }}"
                            wire:model="availableTimeSlots"
                            value="{{ $timeSlot['time'] }}"
                            class="hidden peer"
                            {{ $timeSlot['disabled'] ? 'disabled' : '' }}
                        />
                        <label
                            for="timeSlot{{ $index }}"
                            class="inline-flex items-center justify-center w-full p-2 rounded-lg transition-all duration-200
                                   {{ $timeSlot['disabled']
                                       ? 'text-gray-400 bg-gray-100 border border-gray-200 cursor-not-allowed opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-500'
                                       : 'text-gray-500 bg-gray-50 border border-gray-200 hover:text-gray-600 hover:bg-gray-100 cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-skin-base dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700'
                                   }}">
                            <div class="block text-center">
                                <div class="w-full text-md font-medium">
                                    {{ \Carbon\Carbon::parse($timeSlot['time'])->translatedFormat('h:i A') }}
                                </div>
                            </div>
                        </label>
                    </li>
                    @endforeach
                </ul>
                @error('availableTimeSlots') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">@lang('messages.noTimeSlot')</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if($date === now(timezone() ?: 'Asia/Kolkata')->format('Y-m-d'))
                                @lang('messages.noTimeSlotToday')
                            @else
                                @lang('messages.noTimeSlotForDate')
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            </div>

            @if (!empty($timeSlots))
            <div>
                <x-label for="specialRequest" :value="__('app.specialRequest')" />
                <x-textarea class="block mt-1 w-full" wire:model='specialRequest' rows='2' />
                <x-input-error for="specialRequest" class="mt-2" />
            </div>


            <!-- Customer Search Section -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">@lang('modules.customer.searchCustomer')</h3>
                    </div>
                    @if($selectedCustomerId)
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <svg class="w-3 h-3 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                @lang('modules.customer.existingCustomer')
                            </span>
                            <button type="button" wire:click="clearCustomerSelection" class="text-xs text-skin-base hover:underline">
                                @lang('app.clear')
                            </button>
                        </div>
                    @endif
                </div>

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchQuery"
                           class="block w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-skin-base focus:border-transparent"
                           placeholder="@lang('modules.customer.searchByNamePhoneEmail')" />
                </div>

                <!-- Search Results Dropdown -->
                @if($searchResults && count($searchResults) > 0)
                    <div class="mt-2 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 overflow-hidden max-h-60 overflow-y-auto">
                        @foreach($searchResults as $result)
                            <div wire:key="customer-result-{{ $result->id }}"
                                 wire:click="selectCustomer({{ $result->id }})"
                                 class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer transition-colors border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                <div class="flex-shrink-0 ltr:mr-3 rtl:ml-3">
                                    <div class="w-8 h-8 rounded-full bg-skin-base flex items-center justify-center">
                                        <span class="text-white font-medium text-sm">{{ strtoupper(substr($result->name, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $result->name }}</p>
                                    <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        @if($result->phone)
                                            <span>📞 {{ $result->phone_code ? '+' . $result->phone_code . ' ' : '' }}{{ $result->phone }}</span>
                                        @endif
                                        @if($result->email)
                                            <span>✉️ {{ $result->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($searchQuery && strlen($searchQuery) >= 2)
                    <div class="mt-2 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg text-center text-sm text-gray-500 dark:text-gray-400">
                        @lang('modules.customer.noCustomersFound') - @lang('modules.customer.fillDetailsBelow')
                    </div>
                @endif
            </div>

            <!-- Row 1: Name and Email -->
            <div class="gap-4 grid lg:grid-cols-2">
                <div>
                    <x-label for="customer_name" value="{{ __('modules.customer.name') }}" />
                    <input id="customer_name" type="text" wire:model='customerName'
                        {{ $selectedCustomerId ? 'readonly' : '' }}
                        class="block w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-skin-base focus:border-transparent transition-all duration-200 {{ $selectedCustomerId ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : '' }}" />
                    <x-input-error for="customerName" class="mt-2" />
                </div>
                <div>
                    <x-label for="customerEmail" value="{{ __('modules.customer.email') }}" />
                    <input id="customerEmail" type="email" wire:model='customerEmail'
                        {{ $selectedCustomerId ? 'readonly' : '' }}
                        class="block w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-skin-base focus:border-transparent transition-all duration-200 {{ $selectedCustomerId ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : '' }}" />
                    <x-input-error for="customerEmail" class="mt-2" />
                </div>
            </div>

            <!-- Row 2: Phone Number -->
            <div class="mt-4">
                <x-label for="customerPhone" value="{{ __('modules.customer.phone') }}" />
                <div class="flex gap-2 mt-1">
                    <!-- Phone Code Dropdown -->
                    <div x-data="{ isOpen: @entangle('phoneCodeIsOpen').live }" @click.away="isOpen = false" class="relative w-32">
                        <div @click="{{ $selectedCustomerId ? '' : 'isOpen = !isOpen' }}"
                            class="p-2 bg-gray-100 border rounded dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-600 {{ $selectedCustomerId ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm">
                                    @if($phoneCode)
                                        +{{ $phoneCode }}
                                    @else
                                        {{ __('modules.settings.select') }}
                                    @endif
                                </span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Search Input and Options -->
                        <ul x-show="isOpen" x-transition class="absolute z-10 w-full mt-1 overflow-auto bg-white rounded-lg shadow-lg max-h-60 ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-600">
                            <li class="sticky top-0 px-3 py-2 bg-white dark:bg-gray-900 z-10">
                                <x-input wire:model.live.debounce.300ms="phoneCodeSearch" class="block w-full" type="text" placeholder="{{ __('placeholders.search') }}" />
                            </li>
                            @forelse ($phonecodes as $phonecode)
                                <li @click="$wire.selectPhoneCode('{{ $phonecode }}')"
                                    wire:key="reservation-phone-code-{{ $phonecode }}"
                                    class="relative py-2 pl-3 text-gray-900 transition-colors duration-150 cursor-pointer select-none pr-9 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-600"
                                    :class="{ 'bg-gray-100 dark:bg-gray-800': '{{ $phonecode }}' === '{{ $phoneCode }}' }" role="option">
                                    <div class="flex items-center">
                                        <span class="block ml-3 text-sm whitespace-nowrap">+{{ $phonecode }}</span>
                                        <span x-show="'{{ $phonecode }}' === '{{ $phoneCode }}'" class="absolute inset-y-0 right-0 flex items-center pr-4 text-black dark:text-gray-300" x-cloak>
                                            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="relative py-2 pl-3 text-gray-500 cursor-default select-none pr-9 dark:text-gray-400">
                                    {{ __('modules.settings.noPhoneCodesFound') }}
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Phone Number Input -->
                    <input id="customerPhone" type="tel" wire:model='customerPhone' placeholder="1234567890"
                        {{ $selectedCustomerId ? 'readonly' : '' }}
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-skin-base focus:border-transparent transition-all duration-200 {{ $selectedCustomerId ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : '' }}" />
                </div>
                <x-input-error for="phoneCode" class="mt-2" />
                <x-input-error for="customerPhone" class="mt-2" />
            </div>


            <x-button type='button' wire:click='submitReservation'>@lang('app.reserveNow')</x-button>

            @else

            <x-alert type="danger">
                @lang('messages.noTimeSlot')
            </x-alert>

            @endif


    </div>

</div>
