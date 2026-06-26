<div>
    <form wire:submit="submitForm">
        @csrf

        <div>
            <x-label for="restaurantName" value="{{ __('modules.restaurant.name') }}" />
            <x-input id="restaurantName" class="block mt-1 w-full" type="text" wire:model='restaurantName' />
            <x-input-error for="restaurantName" class="mt-2" />
        </div>

        @includeIf('subdomain::super-admin.restaurant.subdomain-field', ['restaurant' => $restaurant])

        <div class="mt-4">
            <x-label for="email" value="{{ __('app.email') }}" />
            <x-input id="email" class="block mt-1 w-full" type="email" wire:model='email' />
            <x-input-error for="email" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-label class="mt-4" for="phone"
                value="{{ __('modules.settings.restaurantPhoneNumber') }}" />
            <div class="flex gap-2 mt-2">
                <!-- Phone Code Dropdown -->
                <div x-data="{ isOpen: @entangle('phoneCodeIsOpen').live }" @click.away="isOpen = false" class="relative w-32">
                    <div @click="isOpen = !isOpen"
                        class="p-2 bg-gray-100 border rounded cursor-pointer dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-600">
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
                                wire:key="phone-code-{{ $phonecode }}"
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
                <x-input id="phone" class="block w-full" type="tel"
                    wire:model='phone' placeholder="1234567890" />
            </div>

            <x-input-error for="phoneCode" class="mt-2" />
            <x-input-error for="phone" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-label for="address" value="{{ __('modules.settings.restaurantAddress') }}" />
            <x-textarea id="address" class="block mt-1 w-full" wire:model='address' rows="3" />
            <x-input-error for="address" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-label for="country" value="{{ __('Country') }}" />
            <x-select id="restaurantCountry" class="mt-1 block w-full" wire:model="country">
                @foreach ($countries as $item)
                <option value="{{ $item->id }}">{{ $item->countries_name }}</option>
                @endforeach
            </x-select>
            <x-input-error for="country" class="mt-2" />
        </div>

        <!-- NOUVEAUX CHAMPS EMECEf - DÉBUT -->
        <div class="mt-4 border-t pt-4 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Configuration EMECEf (Bénin)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label for="reg_com" value="RCCM (Registre de Commerce)" />
                    <x-input id="reg_com" class="block mt-1 w-full" type="text"
                        wire:model='reg_com' placeholder="Ex: RC-2024-A-1234" />
                    <x-input-error for="reg_com" class="mt-2" />
                </div>

                <div>
                    <x-label for="ifu" value="IFU (Identifiant Fiscal Unique)" />
                    <x-input id="ifu" class="block mt-1 w-full" type="text"
                        wire:model='ifu' placeholder="Ex: 0202401234567" />
                    <x-input-error for="ifu" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <x-label for="api_jeton" value="Jeton API EMECEf" />
                    <div class="relative">
                        <x-input id="api_jeton" class="block mt-1 w-full pr-10" 
                            type="{{ $showApiToken ? 'text' : 'password' }}" 
                            wire:model='api_jeton' 
                            placeholder="Entrez le jeton d'authentification MECEF" />
                        <button type="button" 
                            @click="$wire.toggleApiTokenVisibility()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 mt-1">
                            @if($showApiToken)
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            @else
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            @endif
                        </button>
                    </div>
                    <x-input-error for="api_jeton" class="mt-2" />
                    <p class="text-xs text-gray-500 mt-1">
                        Jeton fourni par la DGI pour l'authentification à l'API MECEF
                    </p>
                </div>

                <div>
                    <x-label for="sfe_status" value="Statut EMECEf" />
                    <x-select id="sfe_status" class="mt-1 block w-full" wire:model="sfe_status">
                        <option value="0">{{ __('app.inactive') }}</option>
                        <option value="1">{{ __('app.active') }}</option>
                    </x-select>
                    <x-input-error for="sfe_status" class="mt-2" />
                </div>

                @if($sfe_activated_at)
                <div>
                    <x-label value="Date d'activation" />
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($sfe_activated_at)->format('d/m/Y H:i') }}
                    </p>
                </div>
                @endif
            </div>

            <!-- Message d'information -->
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1 md:flex md:justify-between">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Ces informations sont requises pour la facturation électronique MECEF (SYDONIA) au Bénin.
                            L'IFU et le jeton API sont obligatoires pour activer la normalisation des factures.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- NOUVEAUX CHAMPS EMECEf - FIN -->

        <div class="mt-4">
            <x-label for="facebook" value="{{ __('modules.settings.facebook_link') }}" />
            <x-input id="facebook" class="block mt-1 w-full" type="url"
               autofocus wire:model='facebook' />
            <x-input-error for="facebook" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-label for="instagram" value="{{ __('modules.settings.instagram_link') }}" />
            <x-input id="instagram" class="block mt-1 w-full" type="url"
                autofocus wire:model='instagram' />
            <x-input-error for="instagram" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-label for="twitter" value="{{ __('modules.settings.twitter_link') }}" />
            <x-input id="twitter" class="block mt-1 w-full" type="url"
               autofocus wire:model='twitter' />
            <x-input-error for="twitter" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-label for="isActive" value="{{ __('app.status') }}"/>
            <x-select id="isActive" class="mt-1 block w-full" wire:model="isActive">
                <option value="1">{{ __('app.active') }}</option>
                <option value="0">{{ __('app.inactive') }}</option>
            </x-select>
            <x-input-error for="isActive" class="mt-2"/>
        </div>

        <div class="mt-4 border-t pt-4 border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Association d'entreprises
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label for="association_code" value="Code d'association" />
                    <x-input id="association_code" class="block mt-1 w-full uppercase" type="text" maxlength="5"
                        wire:model='association_code' />
                    <x-input-error for="association_code" class="mt-2" />
                </div>

                <div>
                    <x-label for="is_primary" value="Type d'entreprise" />
                    <x-select id="is_primary" class="mt-1 block w-full" wire:model.live="is_primary">
                        <option value="0">Standard / secondaire</option>
                        <option value="1">Primaire</option>
                    </x-select>
                    <x-input-error for="is_primary" class="mt-2" />
                </div>

                @if(!$is_primary)
                    <div class="md:col-span-2">
                        <x-label for="primary_restaurant_id" value="Entreprise primaire rattachee" />
                        <x-select id="primary_restaurant_id" class="mt-1 block w-full" wire:model="primary_restaurant_id">
                            <option value="">Aucune</option>
                            @foreach($availablePrimaryRestaurants ?? [] as $primaryRestaurant)
                                <option value="{{ $primaryRestaurant->id }}">
                                    {{ $primaryRestaurant->name }} ({{ $primaryRestaurant->association_code }})
                                </option>
                            @endforeach
                        </x-select>
                        <x-input-error for="primary_restaurant_id" class="mt-2" />
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-start mt-6">
            <x-button>
                {{ __('app.update') }}
            </x-button>
        </div>

    </form>
</div>
