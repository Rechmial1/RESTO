<form wire:submit="save" class="space-y-6">
    <!-- Name -->
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.name') <span class="text-red-500">*</span>
        </label>
        <input type="text" 
            wire:model="name"
            id="name"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="@lang('inventory::modules.batchRecipe.namePlaceholder')">
        @error('name') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Description -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.description')
        </label>
        <textarea 
            wire:model="description"
            id="description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="@lang('inventory::modules.batchRecipe.descriptionPlaceholder')"></textarea>
        @error('description') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Yield Unit -->
    <div>
        <label for="yieldUnitId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.yieldUnit') <span class="text-red-500">*</span>
        </label>
        <select 
            wire:model="yieldUnitId"
            id="yieldUnitId"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">@lang('inventory::modules.batchRecipe.selectYieldUnit')</option>
            @foreach($availableUnits ?? [] as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
            @endforeach
        </select>
        @error('yieldUnitId') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Default Batch Size -->
    <div>
        <label for="defaultBatchSize" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.defaultBatchSize') <span class="text-red-500">*</span>
        </label>
        <input type="number" 
            wire:model="defaultBatchSize"
            id="defaultBatchSize"
            step="0.001"
            min="0.01"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="10">
        @error('defaultBatchSize') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Default Expiry Days -->
    <div>
        <label for="defaultExpiryDays" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.defaultExpiryDays')
        </label>
        <input type="number" 
            wire:model="defaultExpiryDays"
            id="defaultExpiryDays"
            min="1"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="@lang('inventory::modules.batchRecipe.defaultExpiryDaysPlaceholder')">
        @error('defaultExpiryDays') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Ingredients -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            @lang('inventory::modules.batchRecipe.ingredients') <span class="text-red-500">*</span>
        </label>
        <div class="space-y-4">
            @foreach($ingredients as $index => $ingredient)
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            @lang('inventory::modules.batchRecipe.inventoryItem')
                        </label>
                        <select 
                            wire:model="ingredients.{{ $index }}.inventory_item_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">@lang('inventory::modules.batchRecipe.selectInventoryItem')</option>
                            @foreach($inventoryItemsWithUnits ?? [] as $item)
                                <option value="{{ $item['id'] }}">{{ $item['name'] }} ({{ $item['unit_symbol'] }})</option>
                            @endforeach
                        </select>
                        @error("ingredients.{$index}.inventory_item_id") <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="w-32">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            @lang('inventory::modules.batchRecipe.quantity')
                        </label>
                        <input type="number" 
                            wire:model="ingredients.{{ $index }}.quantity"
                            step="0.001"
                            min="0"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="0.00">
                        @error("ingredients.{$index}.quantity") <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="w-32">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            @lang('inventory::modules.batchRecipe.unit')
                        </label>
                        <select 
                            wire:model="ingredients.{{ $index }}.unit_id"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">@lang('inventory::modules.batchRecipe.selectUnit')</option>
                            @foreach($availableUnits ?? [] as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                            @endforeach
                        </select>
                        @error("ingredients.{$index}.unit_id") <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    @if(count($ingredients) > 1)
                    <button type="button" 
                        wire:click="removeIngredient({{ $index }})"
                        class="px-3 py-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                    @endif
                </div>
            @endforeach
            <button type="button" 
                wire:click="addIngredient"
                class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                + @lang('inventory::modules.batchRecipe.addIngredient')
            </button>
        </div>
        @error('ingredients') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Linked Menu Items -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            @lang('inventory::modules.batchRecipe.linkedMenuItems')
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            @lang('inventory::modules.batchRecipe.servingSizeHelp')
        </p>

        <div class="space-y-4">
            @foreach($linkedMenuItems as $index => $row)
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            @lang('inventory::modules.batchRecipe.menuItem')
                        </label>
                        <livewire:inventory::components.searchable-select
                            :name="'linkedMenuItems.'.$index.'.menu_item_id'"
                            :items="$availableMenuItems"
                            :modelId="$row['menu_item_id'] ?? null"
                            displayField="item_name"
                            dispatchEvent="batch-menu-item-selected"
                            :placeholder="__('inventory::modules.recipe.select_menu_item')"
                            :wire:key="'batch-recipe-menu-select-'.$index"
                        />
                    </div>
                    @php
                        $selectedMenuItem = null;
                        if (!empty($row['menu_item_id'] ?? null)) {
                            $selectedMenuItem = ($availableMenuItems ?? collect())->firstWhere('id', $row['menu_item_id']);
                        }
                    @endphp
                    @if($selectedMenuItem && $selectedMenuItem->variations && $selectedMenuItem->variations->count())
                        <div class="w-40">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                @lang('inventory::modules.batchRecipe.variation')
                            </label>
                            <select
                                wire:model="linkedMenuItems.{{ $index }}.menu_item_variation_id"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">@lang('inventory::modules.batchRecipe.allVariations')</option>
                                @foreach($selectedMenuItem->variations as $variation)
                                    <option value="{{ $variation->id }}">{{ $variation->variation }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="w-32">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            @lang('inventory::modules.batchRecipe.servingSize')
                        </label>
                        <input
                            type="number"
                            wire:model="linkedMenuItems.{{ $index }}.serving_size"
                            step="0.001"
                            min="0.01"
                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="@lang('inventory::modules.batchRecipe.servingSizePlaceholder')"
                        >
                    </div>
                    <button
                        type="button"
                        wire:click="removeLinkedMenuItem({{ $index }})"
                        class="px-3 py-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            @endforeach

            <button
                type="button"
                wire:click="addLinkedMenuItem"
                class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                + @lang('inventory::modules.batchRecipe.addLinkedMenuItem')
            </button>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <x-secondary-button type="button" wire:click="$dispatch('closeAddBatchRecipeModal')">
            @lang('inventory::modules.batchRecipe.cancel')
        </x-secondary-button>
        <x-button type="submit">
            @lang('inventory::modules.batchRecipe.save')
        </x-button>
    </div>
</form>

