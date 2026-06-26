<div>
    <x-inventory::batch-reports.tabs />

    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">
            {{ __('inventory::modules.reports.batch_expected_vs_actual.title') }}
        </h2>

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        @lang('inventory::modules.reports.filters.start_date')
                    </label>
                    <input type="date"
                           wire:model.live="startDate"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        @lang('inventory::modules.reports.filters.end_date')
                    </label>
                    <input type="date"
                           wire:model.live="endDate"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        @lang('inventory::modules.batchRecipe.batchRecipe')
                    </label>
                    <select wire:model.live="batchRecipeFilter"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">@lang('inventory::modules.batchRecipe.allBatchRecipes')</option>
                        @foreach($rows as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @lang('inventory::modules.reports.batch_expected_vs_actual.description')
                    </p>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            @lang('inventory::modules.batchRecipe.batchName')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            @lang('inventory::modules.reports.batch_expected_vs_actual.expected')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            @lang('inventory::modules.reports.batch_expected_vs_actual.actual')
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            @lang('inventory::modules.reports.batch_expected_vs_actual.variance')
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $recipe)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $recipe->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $recipe->yieldUnit->name ?? '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($recipe->expected_quantity ?? 0, 2) }} {{ $recipe->yieldUnit->symbol ?? '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($recipe->actual_quantity ?? 0, 2) }} {{ $recipe->yieldUnit->symbol ?? '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($recipe->variance ?? 0, 2) }} {{ $recipe->yieldUnit->symbol ?? '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                @lang('inventory::modules.batchRecipe.noBatchStockFound')
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
</div>












