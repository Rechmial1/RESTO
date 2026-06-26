<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column - Product Information -->
            <div class="space-y-4">
                <div class="bg-white space-y-4 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        @lang('modules.menu.productInformation')
                    </h3>
                    <!-- Language Selection -->
                    @if(count($languages) > 1)
                    <div class="mb-4">
                        <x-label for="language" :value="__('modules.menu.selectLanguage')" />
                        <div class="relative mt-1">
                            @php
                            $languageSettings = collect(App\Models\LanguageSetting::LANGUAGES)
                            ->keyBy('language_code')
                            ->map(function ($lang) {
                            return [
                            'flag_url' => asset('flags/1x1/' . strtolower($lang['flag_code']) . '.svg'),
                            'name' => App\Models\LanguageSetting::LANGUAGES_TRANS[$lang['language_code']] ??
                            $lang['language_name']
                            ];
                            });
                            @endphp
                            <x-select class="block w-full pl-10" wire:model.live="currentLanguage">
                                @foreach($languages as $code => $name)
                                <option value="{{ $code }}"
                                    data-flag="{{ $languageSettings->get($code)['flag_url'] ?? asset('flags/1x1/' . strtolower($code) . '.svg') }}"
                                    class="flex items-center py-2">
                                    {{ $languageSettings->get($code)['name'] ?? $name }}
                                </option>
                                @endforeach
                            </x-select>

                            {{-- Current Selected Flag --}}
                            @php
                            $currentFlagCode = collect(App\Models\LanguageSetting::LANGUAGES)
                            ->where('language_code', $currentLanguage)
                            ->first()['flag_code'] ?? $currentLanguage;
                            @endphp
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <img src="{{ asset('flags/1x1/' . strtolower($currentFlagCode) . '.svg') }}"
                                     alt="{{ $currentLanguage }}" class="w-5 h-5 rounded-sm object-cover" />
                            </div>
                        </div>
                    </div>
                    @endif


                    <!-- Item Name and Description with Translation -->
                    <div class="mb-4">
                        <x-label for="itemName" :value="__('modules.menu.itemName') . ' (' . $languages[$currentLanguage] . ')'" />
                        <x-input id="itemName" class="block mt-1 w-full" type="text" placeholder="{{ __('placeholders.menuItemNamePlaceholder') }}" wire:model="itemName" wire:change="updateTranslation" />
                        <x-input-error for="translationNames.{{ $globalLocale }}" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="itemDescription" :value="__('modules.menu.itemDescription') . ' (' . $languages[$currentLanguage] . ')'" />
                        <x-textarea class="block mt-1 w-full" :placeholder="__('placeholders.itemDescriptionPlaceholder')" wire:model='itemDescription'
                            rows='2' wire:change="updateTranslation" data-gramm="false" />
                        <x-input-error for="itemDescription" class="mt-2" />
                    </div>

                    <!-- Translation Preview -->
                    <div>
                        @if(count($languages) > 1 && (array_filter($translationNames) ||
                        array_filter($translationDescriptions)))
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                            <x-label :value="__('modules.menu.translations')" class="text-sm mb-2 last:mb-0" />
                            <div class="divide-y divide-gray-200 dark:divide-gray-600">
                                @foreach($languages as $lang => $langName)
                                @if(!empty($translationNames[$lang]) || !empty($translationDescriptions[$lang]))
                                <div class="flex flex-col gap-1.5 py-2"
                                     wire:key="translation-details-{{ $loop->index }}">
                                    <div class="flex items-center gap-3">
                                        <span class="min-w-[80px] text-xs font-medium text-gray-600 dark:text-gray-300">
                                            {{ $languageSettings->get($lang)['name'] ?? strtoupper($lang) }}
                                        </span>
                                        <div class="flex-1">
                                            @if(!empty($translationNames[$lang]))
                                            <div class="mb-1">
                                                <span
                                                     class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('app.name'):</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-200 ml-1">{{
                                                    $translationNames[$lang] }}</span>
                                            </div>
                                            @endif
                                            @if(!empty($translationDescriptions[$lang]))
                                            <div>
                                                <span
                                                     class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('app.description'):</span>
                                                <span class="text-xs text-gray-700 dark:text-gray-200 ml-1">{{
                                                    $translationDescriptions[$lang] }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label for="menu" :value="__('modules.menu.chooseMenu')" />
                                <x-select id="menu" class="mt-1 block w-full" wire:model="menu">
                                    <option value="">--</option>
                                    @foreach ($menus as $item)
                                    <option value="{{ $item->id }}">{{ $item->menu_name }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error for="menu" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="itemCategory" :value="__('modules.menu.itemCategory')" />
                                <x-select id="itemCategory" name="item_category_id" class="mt-1 block w-full" wire:model="itemCategory">
                                    <option value="">--</option>
                                    @foreach ($categoryList as $item)
                                    <option value="{{ $item->id }}">{{ $item->category_name }}</option>
                                    @endforeach

                                    <x-slot name="append">
                                        <button class="font-semibold border-l-0 text-sm toggle-password"
                                             wire:click="$toggle('showMenuCategoryModal')" type="button">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                 fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
                                                <path
                                                     d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                </x-select>
                                <x-input-error for="itemCategory" class="mt-2" />
                            </div>
                        </div>

                        <!-- Item Type Selection -->
                        <div>
                            <x-label :value="__('modules.menu.itemType')" class="mb-3" />
                            <ul class="grid w-full gap-2 grid-cols-2 md:grid-cols-3">
                                <li>
                                    <input type="radio" id="typeVeg" name="itemType" value="veg" class="hidden peer" wire:model='itemType'>
                                    <label for="typeVeg"
                                         class="inline-flex items-center justify-between w-full p-2 text-gray-600 bg-white border-2 border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-gray-900 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium">
                                        <img src="{{ asset('img/veg.svg')}}" class="h-5 mr-1" />
                                        @lang('modules.menu.typeVeg')
                                    </label>
                                </li>
                                <li>
                                    <input type="radio" id="typeNonVeg" name="itemType" value="non-veg"
                                        class="hidden peer" wire:model='itemType' />
                                    <label for="typeNonVeg"
                                        class="inline-flex items-center justify-between w-full p-2 text-gray-600 bg-white border-2 border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-gray-900 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium">
                                        <img src="{{ asset('img/non-veg.svg')}}" class="h-5 mr-1" />
                                        @lang('modules.menu.typeNonVeg')
                                    </label>
                                </li>
                                <li>
                                    <input type="radio" id="typeEgg" name="itemType" value="egg" class="hidden peer" wire:model='itemType'>
                                    <label for="typeEgg"
                                         class="inline-flex items-center justify-between w-full p-2 text-gray-600 bg-white border-2 border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-gray-900 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium">
                                        <img src="{{ asset('img/egg.svg')}}" class="h-5 mr-1" />
                                        @lang('modules.menu.typeEgg')
                                    </label>
                                </li>
                                <li>
                                    <input type="radio" id="typeDrink" name="itemType" value="drink" class="hidden peer"
                                         wire:model='itemType'>
                                    <label for="typeDrink"
                                         class="inline-flex items-center justify-between w-full p-2 text-gray-600 bg-white border-2 border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-gray-900 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium">
                                        <img src="{{ asset('img/drink.svg')}}" class="h-5 mr-1"
                                             style="filter: invert(29%) sepia(100%) saturate(748%) hue-rotate(180deg) brightness(95%) contrast(92%);" />
                                        @lang('modules.menu.typeDrink')
                                    </label>
                                </li>
                                <li>
                                    <input type="radio" id="typeHalal" name="itemType" value="halal" class="hidden peer"
                                         wire:model='itemType'>
                                    <label for="typeHalal"
                                         class="inline-flex items-center justify-between w-full p-2  bg-white border-2 border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-gray-900 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium">
                                        <img src="{{ asset('img/halal.svg') }}" class="h-5 mr-1" />
                                        @lang('modules.menu.typeHalal')
                                    </label>
                                </li>
                                <li>
                                    <input type="radio" id="typeOther" name="itemType" value="other" class="hidden peer"
                                         wire:model='itemType'>
                                    <label for="typeOther"
                                         class="inline-flex items-center justify-between w-full p-2 text-gray-600 bg-white border-2 border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-skin-base peer-checked:border-skin-base peer-checked:text-gray-900 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium">
                                        {{-- <img src="{{ asset('img/egg.svg')}}" class="h-5 mr-1" /> --}}
                                        @lang('modules.menu.typeOther')
                                    </label>
                                </li>
                            </ul>
                        </div>

                        <!-- Additional Settings -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label for="preparationTime" :value="__('modules.menu.preparationTime')" />
                                <x-input id="preparationTime" type="number" step="1" min="0" wire:model="preparationTime" class="block w-full rounded text-gray-900 placeholder:text-gray-400 mt-1" :placeholder="__('modules.menu.minutes')" />
                                <x-input-error for="preparationTime" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="isAvailable" :value="__('modules.menu.isAvailable')" />
                                <x-select id="isAvailable" class="mt-1 block w-full" wire:model="isAvailable">
                                    <option value="1">@lang('app.yes')</option>
                                    <option value="0">@lang('app.no')</option>
                                </x-select>
                                <x-input-error for="isAvailable" class="mt-2" />
                            </div>
                        </div>

                        @if (in_array('Kitchen', restaurant_modules()))
                        <div>
                            <x-label for="kitchenType" :value="__('modules.menu.kitchenType')" />
                            <x-select id="kitchenType" class="mt-1 block w-full" wire:model="kitchenType">
                                <option value="">@lang('modules.menu.SelectKitchenType')</option>
                                @foreach($kitchenTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error for="kitchenType" class="mt-2" />
                        </div>
                        @endif

                        <!-- Item Image Upload -->
                        <div>
                            <x-label for="itemImage" value="{{ __('modules.menu.itemImage') }}" />

                            <input
                                class="block w-full text-sm border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 text-slate-500 mt-1"
                                type="file" wire:model="itemImageTemp" accept="image/*">

                            <x-input-error for="itemImageTemp" class="mt-2" />

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @lang('modules.menu.itemImageUploadHelp')
                            </p>

                            @if($itemImageTemp)
                            <div class="mt-2">
                                <div class="relative inline-block">
                                    <img src="{{ $itemImageTemp->temporaryUrl() }}" alt="Preview" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                                    <button type="button" wire:click="removeSelectedImage"
                                         class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    <p class="font-medium">{{ $itemImageTemp->getClientOriginalName() }}</p>
                                    <p class="text-gray-500">{{ $this->formatFileSize($itemImageTemp->getSize()) }}</p>
                                    @php
                                    $imageInfo = getimagesize($itemImageTemp->getRealPath());
                                    if ($imageInfo) {
                                    echo '<p class="text-gray-500">' . $imageInfo[0] . ' × ' . $imageInfo[1] . ' pixels
                                    </p>';
                                    }
                                    @endphp
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons - Hidden on mobile (buttons shown in pricing section) -->
                    <div class="hidden lg:flex w-full space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-button wire:loading.attr="disabled" wire:target="submitForm" class="flex-1">
                            <span wire:loading.remove wire:target="submitForm">@lang('app.save')</span>
                            <span wire:loading wire:target="submitForm" class="flex items-center">
                                Saving...
                            </span>
                        </x-button>
                        <x-secondary-link href="{{ route('menu-items.index') }}" wire:navigate wire:loading.attr="disabled" wire:target="submitForm"
                            class="flex-1">@lang('app.cancel')</x-secondary-link>
                    </div>
                </div>


                <!-- tax modes here work-->
                @if ($isTaxModeItem)
                <!-- Tax Settings Section -->
                <div
                     class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                 d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                            </path>
                        </svg>
                        @lang('modules.settings.taxSettings')
                    </h3>
                    <div class="mb-4">
                        <x-label for="selectedTaxes" class="mb-1" :value="__('modules.menu.selectTaxes')" />
                        <div x-data="{
                        isOpen: false,
                        selectedTaxes: @entangle('selectedTaxes').live,
                    }" @click.away="isOpen = false" wire:key="tax-selector">
                            <div class="relative">
                                <div @click="isOpen = !isOpen"
                                     class="w-full flex items-center justify-between bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 cursor-pointer">
                                    <div class="flex flex-wrap gap-1">
                                        @if(empty($selectedTaxes))
                                        <span
                                             class="text-gray-500 dark:text-gray-400">@lang('modules.menu.selectTaxes')</span>
                                        @else
                                        @foreach(collect($taxes)->whereIn('id', $selectedTaxes) as $tax)
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-600 rounded-md text-sm mr-1 flex items-center"
                                             wire:key="tax-badge-{{ $tax->id }}">
                                            {{ $tax->tax_name }} ({{ $tax->tax_percent }}%)
                                        </span>
                                        @endforeach
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>

                                <!-- Dropdown menu -->
                                <div x-show="isOpen" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md overflow-auto">
                                    <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">@lang('modules.menu.selectTaxes')</span>
                                    </div>
                                    <ul class="py-1">
                                        @foreach($taxes as $tax)
                                        <li class="px-2" wire:key="tax-option-{{ $tax->id }}">
                                            <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-md cursor-pointer">
                                                <input type="checkbox" value="{{ $tax->id }}" x-model="selectedTaxes"
                                                    @click="$wire.set('selectedTaxes', selectedTaxes);"
                                                    class="rounded border-gray-300 text-skin-base shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $tax->tax_name }} ({{ $tax->tax_percent }}%)
                                                </span>
                                            </label>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <x-input-error for="selectedTaxes" class="mt-2" />
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Pricing Details -->
            <div class="lg:col-span-1 space-y-4">
                <!-- Pricing Configuration -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-500" width="24" height="24" viewBox="0 0 24 24" stroke="currentColor" fill="currentColor" xmlns="http://www.w3.org/2000/svg" data-name="Layer 1"><path d="M9.5 10.5H12a1 1 0 0 0 0-2h-1V8a1 1 0 0 0-2 0v.55a2.5 2.5 0 0 0 .5 4.95h1a.5.5 0 0 1 0 1H8a1 1 0 0 0 0 2h1v.5a1 1 0 0 0 2 0v-.55a2.5 2.5 0 0 0-.5-4.95h-1a.5.5 0 0 1 0-1M21 12h-3V3a1 1 0 0 0-.5-.87 1 1 0 0 0-1 0l-3 1.72-3-1.72a1 1 0 0 0-1 0l-3 1.72-3-1.72a1 1 0 0 0-1 0A1 1 0 0 0 2 3v16a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1M5 20a1 1 0 0 1-1-1V4.73l2 1.14a1.08 1.08 0 0 0 1 0l3-1.72 3 1.72a1.08 1.08 0 0 0 1 0l2-1.14V19a3 3 0 0 0 .18 1Zm15-1a1 1 0 0 1-2 0v-5h2Z"/></svg>
                        @lang('modules.menu.pricingDetails')
                    </h3>

                    <!-- Variations Toggle Switch -->
                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <x-checkbox name="hasVariations" id="hasVariations" wire:model.live='hasVariations' wire:change="checkVariations()" />
                        <div class="ml-3 flex-1">
                            <x-label for="hasVariations" :value="__('modules.menu.hasVariations')" class="!mb-1 font-medium" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.menu.hasVariationsHelp')</p>
                        </div>
                    </div>

                    <!-- Variations Management Section -->
                    @if(!$showItemPrice)
                    <div class="space-y-4 mt-4" x-data="{ openVariation: null }">
                        <!-- Variations List -->
                        @foreach($inputs as $key => $value)
                        <div @class([
                            'bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 transition-all duration-200',
                            'ring-1 ring-skin-base shadow-md' => 'openVariation === ' . $key
                        ])
                            wire:key="variation-input-{{ $loop->index }}">

                            <!-- Variation Header - Clickable -->
                            <div class="cursor-pointer" @click="openVariation = openVariation === {{ $key }} ? null : {{ $key }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400 transition-transform duration-200"
                                            :class="openVariation === {{ $key }} ? 'rotate-90' : ''"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        <div class="flex items-center gap-3 select-none">
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-white">
                                                    {{ $variationName[$key] ?? 'Variation ' . ($key + 1) }}
                                                </h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    <span class="font-medium">@lang('modules.menu.setPrice'):</span> {{ restaurant()->currency->currency_symbol }}{{ $variationPrice[$key] ?? '0.00' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remove Button - Only shown when there are more than 1 variations -->
                                    @if(count($inputs) > 1)
                                        <button type="button"
                                                wire:click.stop="removeField({{ $key }})" wire:key="remove-field-{{ $key }}-{{ uniqid() }}"
                                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-md transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Expanded Content -->
                            <div x-show="openVariation === {{ $key }}" wire:key="variation-details-opened-{{ $key }}"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-4">

                                <!-- Variation Name and Price -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-label for="variationName.{{ $key }}" :value="__('modules.menu.variationName')" />
                                        <x-input id="variationName.{{ $key }}" class="block mt-1 w-full" type="text"
                                            :placeholder="__('placeholders.itemVariationPlaceholder')"
                                            wire:model.blur='variationName.{{ $key }}' />
                                        <x-input-error for="variationName.{{ $key }}" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-label for="variationPrice.{{ $key }}" :value="__('modules.menu.setPrice')" />
                                        <div class="relative rounded-md mt-1">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                            </div>
                                            <x-input id="variationPrice.{{ $key }}" type="number" step="0.001" min="0"
                                                wire:model.live.debounce.500ms='variationPrice.{{ $key }}'
                                                class="block w-full rounded pl-10 text-gray-900 placeholder:text-gray-400" placeholder="0.00" />
                                        </div>
                                        <x-input-error for="variationPrice.{{ $key }}" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Order Types Pricing -->
                                @if($orderTypes->isNotEmpty())
                                <div wire:key="order-types-pricing-{{ $key }}">
                                    <x-label value="Order Types Pricing" class="mb-3 text-base font-semibold" />
                                    <div class="space-y-2">
                                        @foreach($orderTypes->reject(fn($type) => strtolower($type->slug ?? $type->name) === 'delivery') as $orderType)
                                        <div class="flex items-center justify-between gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600" wire:key="order-type-{{ $orderType->id }}-variation-{{ $key }}">
                                            <div class="inline-flex items-center space-x-2">
                                                <div @class(['w-3 h-3 rounded-full flex-shrink-0', $this->orderTypeColor($orderType->id)])></div>
                                                <span class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $orderType->order_type_name }}</span>
                                            </div>
                                            <div class="relative flex-shrink-0">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 text-sm">{{ restaurant()->currency->currency_symbol }}</span>
                                                </div>
                                                <x-input type="number" step="0.001" min="0"
                                                        wire:model.blur="variationOrderTypePrices.{{ $key }}.{{ $orderType->id }}"
                                                        class="block pl-8 pr-3 w-24 sm:w-32"
                                                        placeholder="0.00" />
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <!-- Delivery Platforms -->
                                <div>
                                    <x-label value="Delivery Platforms" class="mb-3 text-base font-semibold" />
                                    <div class="space-y-2">
                                        <!-- Base Delivery Price -->
                                        <div class="flex items-center justify-between gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center space-x-2 flex-1 min-w-0">
                                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-200 flex-shrink-0" fill="currentColor" height="20" viewBox="0 0 64 64" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M4 16h14.001a3 3 0 0 1 3 3v11.001a3 3 0 0 1-3 3h-14a3 3 0 0 1-3.001-3v-11a3 3 0 0 1 3-3"/><circle cx="33.002" cy="7" r="5"/><path d="M12.003 35.852a5.92 5.92 0 0 0 1.7 4.15H29.96v-4.155a.996.996 0 0 0-.996-.996H12.998a1 1 0 0 0-.995 1.001"/><path d="M61.737 51.359a8.13 8.13 0 0 0-8.322-5.994 7 7 0 0 0 .24-1.791A5.93 5.93 0 0 0 51 38.75c-2.147-1.425-3.753-5.048-3.996-8.858h1.916a2.99 2.99 0 0 0 2.991-2.982v-1.986a2.99 2.99 0 0 0-2.991-2.982h-6.84c-5.782-1.665-7.522-3.583-8.561-4.732l-.063-.07a3.71 3.71 0 0 0-2.018-3.813 3.64 3.64 0 0 0-5.122 2.497l-2.869 13.71a2.983 2.983 0 0 0 2.598 3.571l4.917.544a.994.994 0 0 1 .887 1.043l-.774 13.106a5.27 5.27 0 0 1-1.477-5.796H14.313c-1.612 2.671-4.193 7.679-3.149 10.936a4.04 4.04 0 0 0 2.609 2.622 3.7 3.7 0 0 0 1.39.15 6.406 6.406 0 0 0 12.78 0h17.14a1.26 1.26 0 0 0 .875-.423 7 7 0 0 0 .587 1.703.996.996 0 0 0 1.716.14q.176-.25.376-.491a6.4 6.4 0 1 0 12.484-2.718.986.986 0 0 0 .875-1.075 8 8 0 0 0-.26-1.487m-40.184 8.318a4.407 4.407 0 0 1-4.385-3.967h8.77a4.407 4.407 0 0 1-4.385 3.967M40.94 48.754h-3.885l1.718-16.24a2.98 2.98 0 0 0-1.926-3.104l-4.9-1.829a.99.99 0 0 1-.622-1.149l.745-3.215a17.1 17.1 0 0 0 8.87 3.633zm14.586 11.218a4.413 4.413 0 0 1-4.961-4.86l.304-.38a11.08 11.08 0 0 1 7.676-1.51l.236.183a4.4 4.4 0 0 1-3.255 6.567"/></svg>
                                                <span class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm truncate">Base Delivery Price</span>
                                            </div>
                                            <div class="relative flex-shrink-0">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 text-sm">{{ restaurant()->currency->currency_symbol }}</span>
                                                </div>
                                                <x-input type="number" step="0.001"
                                                        wire:model.live="variationBaseDeliveryPrice.{{ $key }}"
                                                        class="block pl-8 pr-3 w-24 sm:w-32"
                                                        placeholder="0.00" min="0" />
                                            </div>
                                        </div>

                                        <!-- Delivery Apps -->
                                        @foreach($deliveryApps as $app)
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600" wire:key="delivery-app-{{ $app->id }}-variation-{{ $key }}">
                                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                                <div class="w-8 h-8 flex-shrink-0">
                                                    @if($app->logo)
                                                    <img class="w-8 h-8 rounded-lg object-cover border border-gray-200"
                                                        src="{{ $app->logo_url ?? asset('images/default-logo.png') }}"
                                                        alt="{{ $app->name }}">
                                                    @else
                                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="font-medium text-gray-900 dark:text-white text-xs sm:text-sm truncate block">{{ $app->name }}</span>
                                                    <div class="text-xs text-gray-500">
                                                        Commission: {{ $app->commission_value ?? 0 }}%
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end space-x-2 sm:space-x-3">
                                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                                    <input type="checkbox"
                                                        wire:model.defer="variationPlatformAvailability.{{ $key }}.{{ $app->id }}"
                                                        class="sr-only peer">
                                                    <div class="w-10 h-5 sm:w-11 sm:h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 sm:after:h-5 sm:after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                                </label>
                                                <div class="text-right flex-shrink-0">
                                                    <div class="font-semibold text-xs sm:text-sm text-gray-900 dark:text-white">
                                                        {{ restaurant()->currency->currency_symbol }}{{ $variationDeliveryPrices[$key][$app->id] ?? '0.00' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">@lang('modules.menu.finalPrice')</div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Tax Breakdown -->
                                @if($isTaxModeItem && !empty($variationBreakdowns[$key]['breakdown']))
                                <div class="bg-gray-100 dark:bg-gray-600 rounded-lg p-3">
                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('modules.menu.taxBreakdown')</h5>
                                    <div class="text-xs space-y-1">
                                        <div class="flex justify-between">
                                            <span>@lang('modules.menu.basePrice'):</span>
                                            <span>{{ restaurant()->currency->currency_symbol }}{{ $variationBreakdowns[$key]['breakdown']['base_price'] ?? '0.00' }}</span>
                                        </div>
                                        @if(!empty($variationBreakdowns[$key]['breakdown']['tax_breakdown']))
                                        @foreach($variationBreakdowns[$key]['breakdown']['tax_breakdown'] as $tax)
                                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>{{ $tax['name'] }} ({{ $tax['rate'] }}%):</span>
                                            <span>{{ restaurant()->currency->currency_symbol }}{{ $tax['amount'] }}</span>
                                        </div>
                                        @endforeach
                                        @endif
                                        <div class="flex justify-between font-medium pt-1 border-t border-gray-300 dark:border-gray-500">
                                            <span>@lang('modules.menu.total'):</span>
                                            <span>{{ restaurant()->currency->currency_symbol }}{{ $variationBreakdowns[$key]['breakdown']['final_price'] ?? '0.00' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        <!-- Add Variation Button -->
                        @if ($hasVariations)
                        <x-secondary-button wire:click="addMoreField({{ $i }})" class="w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            @lang('modules.menu.addVariations')
                        </x-secondary-button>
                        @endif
                    </div>
                    @endif

                    <!-- Order Type Pricing Section -->
                    @if ($showItemPrice)
                    <div class="space-y-6 mt-4">
                        <!-- Default Price (itemPrice) -->
                        <div>
                            <x-label for="itemPrice" :value="__('modules.menu.basePrice')" />
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                </div>
                                <x-input id="itemPrice" type="number" step="0.001" min="0" wire:model.live.debounce.500ms="itemPrice" class="block w-full pl-10 pr-4" placeholder="0.00" />
                            </div>
                            <x-input-error for="itemPrice" class="mt-1" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@lang('messages.menuItemBasePriceDescription')</p>
                        </div>

                        <!-- Order Types Pricing -->
                        <div>
                            <x-label value="Order Types Pricing" class="mb-4 text-base font-semibold" />
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                                @foreach($orderTypes->reject(fn($type) => strtolower($type->slug ?? $type->name) === 'delivery') as $orderType)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600" wire:key="order-type-{{ $orderType->id }}">
                                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                                        <div @class(['w-3 h-3 rounded-full flex-shrink-0', $this->orderTypeColor($orderType->id)])></div>
                                        <span class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                            {{ $orderType->order_type_name }}
                                        </span>
                                    </div>
                                    <div class="relative sm:flex-shrink-0 w-full sm:w-auto">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                        </div>
                                        <x-input type="number" step="0.001" min="0"
                                            wire:model.live="orderTypePrices.{{ $orderType->id }}"
                                            class="block w-full sm:w-32 pl-8 pr-3 border border-gray-300 rounded-lg text-gray-900 placeholder:text-gray-400 focus:ring-skin-base focus:border-skin-base"
                                            placeholder="0.00" />
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @foreach($orderTypes->reject(fn($type) => strtolower($type->slug ?? $type->name) === 'delivery') as $orderType)
                            <x-input-error for="orderTypePrices.{{ $orderType->id }}" class="mt-2" />
                            @endforeach
                        </div>

                        <!-- Delivery Platforms Display -->
                        <div>
                            <x-label value="Delivery Platforms" class="mb-4 text-base font-semibold" />
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">

                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-200 flex-shrink-0" fill="currentColor" height="24" viewBox="0 0 64 64" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4 16h14.001a3 3 0 0 1 3 3v11.001a3 3 0 0 1-3 3h-14a3 3 0 0 1-3.001-3v-11a3 3 0 0 1 3-3"/><circle cx="33.002" cy="7" r="5"/><path d="M12.003 35.852a5.92 5.92 0 0 0 1.7 4.15H29.96v-4.155a.996.996 0 0 0-.996-.996H12.998a1 1 0 0 0-.995 1.001"/><path d="M61.737 51.359a8.13 8.13 0 0 0-8.322-5.994 7 7 0 0 0 .24-1.791A5.93 5.93 0 0 0 51 38.75c-2.147-1.425-3.753-5.048-3.996-8.858h1.916a2.99 2.99 0 0 0 2.991-2.982v-1.986a2.99 2.99 0 0 0-2.991-2.982h-6.84c-5.782-1.665-7.522-3.583-8.561-4.732l-.063-.07a3.71 3.71 0 0 0-2.018-3.813 3.64 3.64 0 0 0-5.122 2.497l-2.869 13.71a2.983 2.983 0 0 0 2.598 3.571l4.917.544a.994.994 0 0 1 .887 1.043l-.774 13.106a5.27 5.27 0 0 1-1.477-5.796H14.313c-1.612 2.671-4.193 7.679-3.149 10.936a4.04 4.04 0 0 0 2.609 2.622 3.7 3.7 0 0 0 1.39.15 6.406 6.406 0 0 0 12.78 0h17.14a1.26 1.26 0 0 0 .875-.423 7 7 0 0 0 .587 1.703.996.996 0 0 0 1.716.14q.176-.25.376-.491a6.4 6.4 0 1 0 12.484-2.718.986.986 0 0 0 .875-1.075 8 8 0 0 0-.26-1.487m-40.184 8.318a4.407 4.407 0 0 1-4.385-3.967h8.77a4.407 4.407 0 0 1-4.385 3.967M40.94 48.754h-3.885l1.718-16.24a2.98 2.98 0 0 0-1.926-3.104l-4.9-1.829a.99.99 0 0 1-.622-1.149l.745-3.215a17.1 17.1 0 0 0 8.87 3.633zm14.586 11.218a4.413 4.413 0 0 1-4.961-4.86l.304-.38a11.08 11.08 0 0 1 7.676-1.51l.236.183a4.4 4.4 0 0 1-3.255 6.567"/></svg>
                                        <div class="min-w-0">
                                            <span class="font-medium text-gray-900 dark:text-white text-sm truncate block">
                                                Delivery
                                            </span>
                                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                Default delivery price
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative w-full sm:w-auto sm:flex-shrink-0">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500">{{ restaurant()->currency->currency_symbol }}</span>
                                        </div>
                                        <x-input type="number" step="0.001" min="0"
                                            wire:model.live="baseDeliveryPrice"
                                            class="block w-full sm:w-32 pl-8 pr-3 border border-gray-300 rounded-lg text-gray-900 placeholder:text-gray-400 focus:ring-skin-base focus:border-skin-base"
                                            placeholder="{{ $baseDeliveryPrice ?: '0.00' }}" />
                                    </div>
                                </div>
                                @forelse($deliveryApps as $app)
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600" wire:key="delivery-app-{{ $app->id }}">
                                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                                        <!-- Logo -->
                                        <div class="flex-shrink-0 w-8 h-8">
                                        @if($app->logo)
                                                <img class="w-8 h-8 rounded-lg object-cover border border-gray-200 dark:border-gray-600"
                                                    src="{{ $app->logo_url ?? asset('images/default-logo.png') }}"
                                                    alt="{{ $app->name }}" loading="lazy">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-600 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        </div>

                                        <div class="min-w-0">
                                            <span class="font-medium text-gray-900 dark:text-white text-sm truncate block">{{ $app->name }}</span>
                                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                                Commission:
                                                @if($app->commission_type === 'percent')
                                                    {{ $app->commission_value ?? 0 }}%
                                                @else
                                                    {{ restaurant()->currency->currency_symbol }}{{ $app->commission_value ?? 0 }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end space-x-2 sm:space-x-3">
                                        <!-- Availability Toggle -->
                                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                            <input type="checkbox"
                                                wire:model.defer="platformAvailability.{{ $app->id }}"
                                                class="sr-only peer">
                                            <div class="w-10 h-5 sm:w-11 sm:h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 sm:after:h-5 sm:after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        </label>

                                        <!-- Calculated Price Display -->
                                        <div class="text-right flex-shrink-0">
                                            <div class="font-semibold text-xs sm:text-sm text-gray-900 dark:text-white">
                                                {{ restaurant()->currency->currency_symbol }}{{ $deliveryPrices[$app->id] ?: '0.00' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">@lang('modules.menu.finalPrice')</div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center pt-8">
                                    <svg class="w-12 h-12 mx-auto text-gray-500 dark:text-gray-400 mb-4" height="24" viewBox="0 0 64 64" width="24" xmlns="http://www.w3.org/2000/svg"><g clip-rule="evenodd" fill="currentColor" fill-rule="evenodd"><path d="M32 21c0-8.284-6.716-15-15-15S2 12.716 2 21s6.716 15 15 15 15-6.716 15-15m-9.343-2.828a2 2 0 0 0-2.829-2.829L17 18.172l-2.828-2.829a2 2 0 0 0-2.829 2.829L14.172 21l-2.829 2.828a2 2 0 0 0 2.829 2.829L17 23.828l2.828 2.829a2 2 0 1 0 2.829-2.829L19.828 21z"/><path d="M34.89 19q.11.986.11 2c0 9.941-8.059 18-18 18a17.9 17.9 0 0 1-8-1.871V47a6 6 0 0 0 6 6h1.29a7.003 7.003 0 0 0 13.42 0h9.58a7.003 7.003 0 0 0 13.42 0H56a6 6 0 0 0 6-6v-9.964a2 2 0 0 0-.27-1.005l-4.094-7.045A6 6 0 0 0 52.448 26h-5.454l-4.43-4.986A6 6 0 0 0 38.078 19zM48 30v13h10v-3h-6a2 2 0 1 1 0-4h5.085l-2.908-5.005a2 2 0 0 0-1.73-.995zM23 48a3 3 0 1 0 0 6 3 3 0 0 0 0-6m23 0a3 3 0 1 0 0 6 3 3 0 0 0 0-6"/></g></svg>
                                    <p class="text-gray-500 dark:text-gray-400">@lang("messages.noDeliveryAppFound")</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- For Normal Price breakdowns -->
                    @if (!$hasVariations && $taxInclusivePriceDetails)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mt-4" wire:key="tax-details-section">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            @lang('modules.menu.taxBreakdown')</h4>
                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">@lang('modules.menu.basePrice'):</span>
                                <span class="font-medium">{{ currency_format($taxInclusivePriceDetails['base_raw'] ?? 0,
                                    restaurant()->currency_id) }}</span>
                            </div>
                            @if(!empty($taxInclusivePriceDetails['tax_breakdown']))
                            <div class="ml-2 my-1">
                                @foreach($taxInclusivePriceDetails['tax_breakdown'] as $tax)
                                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                    <span>{{ $tax['name'] }} ({{ $tax['rate'] }}%)</span>
                                    <span>{{ currency_format($tax['amount'], restaurant()->currency_id) }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">@lang('modules.menu.tax') ({{
                                    $taxInclusivePriceDetails['tax_percent'] }}%):</span>
                                <span class="font-medium">{{ currency_format($taxInclusivePriceDetails['tax_raw'] ?? 0,
                                    restaurant()->currency_id) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600 mt-2">
                                <span
                                     class="text-gray-700 dark:text-gray-300 font-semibold">@lang('modules.menu.total'):</span>
                                <span class="font-semibold text-lg">{{
                                    currency_format($taxInclusivePriceDetails['total_raw'] ?? 0,
                                    restaurant()->currency_id) }}</span>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                     d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @if($taxInclusivePriceDetails['inclusive'])
                            <span>
                                @lang('modules.menu.taxInclusiveInfo', [
                                'percent' => $taxInclusivePriceDetails['tax_percent'],
                                'tax' => currency_format($taxInclusivePriceDetails['tax_raw'] ?? 0,
                                restaurant()->currency_id),
                                'base' => currency_format($taxInclusivePriceDetails['base_raw'] ?? 0,
                                restaurant()->currency_id)
                                ])
                            </span>
                            @else
                            <span>
                                @lang('modules.menu.taxExclusiveInfo', [
                                'percent' => $taxInclusivePriceDetails['tax_percent'],
                                'tax' => currency_format($taxInclusivePriceDetails['tax_raw'] ?? 0,
                                restaurant()->currency_id),
                                'base' => currency_format($taxInclusivePriceDetails['base_raw'] ?? 0,
                                restaurant()->currency_id)
                                ])
                            </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Mobile Save Button - Only visible on mobile -->
                    <div class="flex w-full space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 lg:hidden">
                        <x-button wire:loading.attr="disabled" wire:target="submitForm" class="flex-1">
                            <span wire:loading.remove wire:target="submitForm">@lang('app.save')</span>
                            <span wire:loading wire:target="submitForm" class="flex items-center">
                                Saving...
                            </span>
                        </x-button>
                        <x-secondary-link href="{{ route('menu-items.index') }}" wire:navigate wire:loading.attr="disabled" wire:target="submitForm"
                            class="flex-1 text-center">@lang('app.cancel')</x-secondary-link>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <x-dialog-modal wire:model.live="showMenuCategoryModal" maxWidth="xl">
        <x-slot name="title">
            @lang('modules.menu.itemCategory')
        </x-slot>

        <x-slot name="content">
            @livewire('forms.addItemCategory')
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showMenuCategoryModal')" wire:loading.attr="disabled">
                @lang('app.cancel')</x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function () {
                    const select = document.querySelector('select[wire\\:model\\.live="currentLanguage"]');

                    if (select) {
                        // Style the select options with flags when using a custom select library
                        if (typeof Choices !== 'undefined') {
                            new Choices(select, {
                                templateResult: function(option) {
                                    if (!option.element) return option.text;

                                    return $(`
                                        <div class="flex items-center space-x-2">
                                            <img src="${option.element.dataset.flag}" class="w-5 h-5 rounded-sm" />
                                            <span>${option.text}</span>
                                        </div>
                                    `);
                                }
                            });
                        }
                    }
                });
    </script>
    @endpush
</div>
