<div class="space-y-6">
    <!-- Batch Recipe Selection -->
    <div>
        <label for="batchRecipeId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.selectBatchRecipe') <span class="text-red-500">*</span>
        </label>
        <select 
            wire:model.live="batchRecipeId"
            id="batchRecipeId"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">@lang('inventory::modules.batchRecipe.selectBatchRecipe')</option>
            @foreach($availableBatchRecipes ?? [] as $recipe)
                <option value="{{ $recipe->id }}">{{ $recipe->name }} ({{ $recipe->yieldUnit->symbol }})</option>
            @endforeach
        </select>
        @error('batchRecipeId') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Quantity -->
    <div>
        <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.quantity') <span class="text-red-500">*</span>
        </label>
        <input type="number" 
            wire:model.live="quantity"
            id="quantity"
            step="0.001"
            min="0.01"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="10">
        @error('quantity') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Required Ingredients -->
    @if($selectedBatchRecipe && count($requiredIngredients) > 0)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                @lang('inventory::modules.batchRecipe.requiredIngredients')
            </label>
            <div class="space-y-2">
                @foreach($requiredIngredients as $ingredient)
                    <div class="flex items-center justify-between p-3 rounded-lg {{ $ingredient['is_sufficient'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $ingredient['inventory_item']->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                @lang('inventory::modules.batchRecipe.required'): {{ number_format($ingredient['required_quantity'], 2) }} {{ $ingredient['unit']->symbol }}
                                | @lang('inventory::modules.batchRecipe.available'): {{ number_format($ingredient['available_stock'], 2) }} {{ $ingredient['unit']->symbol }}
                            </div>
                        </div>
                        <div>
                            @if($ingredient['is_sufficient'])
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(count($insufficientStock) > 0)
                <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-sm text-red-800 dark:text-red-200 font-medium">
                        @lang('inventory::modules.batchRecipe.insufficientStockWarning')
                    </p>
                    <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                        @foreach($insufficientStock as $item)
                            <li>{{ $item['name'] }}: {{ number_format($item['required'], 2) }} {{ $item['unit'] }} @lang('inventory::modules.batchRecipe.requiredButOnly') {{ number_format($item['available'], 2) }} {{ $item['unit'] }} @lang('inventory::modules.batchRecipe.available')</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Notes -->
    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            @lang('inventory::modules.batchRecipe.notes')
        </label>
        <textarea 
            wire:model="notes"
            id="notes"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="@lang('inventory::modules.batchRecipe.notesPlaceholder')"></textarea>
        @error('notes') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <x-secondary-button type="button" wire:click="$set('showModal', false)">
            @lang('inventory::modules.batchRecipe.cancel')
        </x-secondary-button>
        <x-button type="button" wire:click="produce" :disabled="count($insufficientStock) > 0">
            @lang('inventory::modules.batchRecipe.produceBatch')
        </x-button>
    </div>
</div>

