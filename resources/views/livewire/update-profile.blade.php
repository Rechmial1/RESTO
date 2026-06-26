<section class="bg-white dark:bg-gray-900">
    <div class="py-8 px-4 mx-auto max-w-2xl lg:py-16">
        <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">@lang('app.profileInfo')</h2>
        <form wire:submit="submitForm">
          @csrf
            <div class="grid gap-4 sm:grid-cols-2 sm:gap-6">
                <div class="sm:col-span-2">
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">@lang('app.fullName')</label>
                    <input type="text" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" wire:model='fullName'>
                    @error('fullName')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">@lang('modules.customer.email')</label>
                    <input type="email" id="email" readonly class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" wire:model='email'>
                    @error('email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="telephone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">@lang('modules.customer.phone')</label>
                    <div class="flex gap-2">
                        <!-- Phone Code Dropdown -->
                        <div x-data="{ isOpen: @entangle('phoneCodeIsOpen').live }" @click.away="isOpen = false" class="relative w-32">
                            <div @click="isOpen = !isOpen"
                                class="p-2.5 bg-gray-50 border border-gray-300 rounded-lg cursor-pointer dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
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
                            <ul x-show="isOpen" x-transition class="absolute z-10 w-full mt-1 overflow-auto bg-white rounded-lg shadow-lg max-h-60 ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                                <li class="sticky top-0 px-3 py-2 bg-white dark:bg-gray-700 z-10">
                                    <input wire:model.live.debounce.300ms="phoneCodeSearch" class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 dark:bg-gray-600 dark:border-gray-500 dark:text-white" type="text" placeholder="{{ __('placeholders.search') }}" />
                                </li>
                                @forelse ($phonecodes as $phonecode)
                                    <li @click="$wire.selectPhoneCode('{{ $phonecode }}')"
                                        wire:key="profile-phone-code-{{ $phonecode }}"
                                        class="relative py-2 pl-3 text-gray-900 transition-colors duration-150 cursor-pointer select-none pr-9 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-300"
                                        :class="{ 'bg-gray-100 dark:bg-gray-600': '{{ $phonecode }}' === '{{ $phoneCode }}' }" role="option">
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
                        <input type="tel" id="telephone" class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" wire:model='phone' placeholder="1234567890">
                    </div>
                    @error('phoneCode')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                    @error('phone')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">@lang('modules.customer.address')</label>
                    <textarea id="address" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" wire:model='address'>
                    </textarea>
                </div>
            </div>
            <x-button class="mt-4">@lang('app.save')</x-button>
        </form>
    </div>
  </section>