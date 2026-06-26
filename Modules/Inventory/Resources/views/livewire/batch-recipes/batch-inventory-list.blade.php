<div class="py-6 px-4 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">@lang("inventory::modules.batchRecipe.batchInventory")</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">@lang("inventory::modules.batchRecipe.batchInventoryDescription")</p>
        </div>

        <div class="flex items-center gap-4">
            @if(user_can('Produce Batch'))
            <x-button wire:click="$dispatch('showProduceBatchModal')">
                @lang("inventory::modules.batchRecipe.produceBatch")
            </x-button>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="@lang('inventory::modules.batchRecipe.searchPlaceholder')"
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                <div class="absolute left-3 top-2.5">
                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-4">
            <select wire:model.live="batchRecipeFilter" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-4 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                <option value="">@lang('inventory::modules.batchRecipe.allBatchRecipes')</option>
                @foreach($batchRecipes as $recipe)
                    <option value="{{ $recipe->id }}">{{ $recipe->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 px-4 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                <option value="">@lang('inventory::modules.batchRecipe.allStatus')</option>
                <option value="active">@lang('inventory::modules.batchRecipe.active')</option>
                <option value="expired">@lang('inventory::modules.batchRecipe.expired')</option>
                <option value="finished">@lang('inventory::modules.batchRecipe.finished')</option>
            </select>

            @if($search || $batchRecipeFilter || $statusFilter)
                <button
                    wire:click="clearFilters"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    @lang('inventory::modules.batchRecipe.clearFilters')
                </button>
            @endif
        </div>
    </div>

    <!-- Batch Stock Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.batchRecipe.batchName")</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.batchRecipe.quantityAvailable")</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.batchRecipe.createdOn")</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.batchRecipe.expiry")</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.batchRecipe.costPerUnit")</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">@lang("inventory::modules.batchRecipe.status")</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($batchStocks as $stock)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $stock->batchRecipe->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ number_format($stock->remaining_quantity, 2) }} {{ $stock->batchRecipe->yieldUnit->symbol }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $stock->created_at->format('Y-m-d H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ $stock->expiry_date ? $stock->expiry_date->format('Y-m-d') : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ currency_format($stock->cost_per_unit, restaurant()->currency_id) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($stock->status === 'active') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                    @elseif($stock->status === 'expired') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                    @else bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200
                                    @endif">
                                    @lang("inventory::modules.batchRecipe.{$stock->status}")
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                @lang("inventory::modules.batchRecipe.noBatchStockFound")
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $batchStocks->links() }}
        </div>
    </div>

    <!-- Produce Batch Modal -->
    <x-right-modal wire:model.live="showProduceBatchModal">
        <x-slot name="title">
            @lang("inventory::modules.batchRecipe.produceBatch")
        </x-slot>
        <x-slot name="content">
            <livewire:inventory::batch-recipes.produce-batch key="produce-batch-{{ now()->timestamp }}" />
        </x-slot>
    </x-right-modal>
</div>

