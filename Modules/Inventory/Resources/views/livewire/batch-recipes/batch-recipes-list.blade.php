<div class="min-h-screen bg-white dark:bg-gray-800 py-8">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-3xl font-bold leading-7 text-gray-900 dark:text-white sm:text-4xl sm:truncate">
                    @lang('inventory::modules.batchRecipe.batchRecipes')
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @lang('inventory::modules.batchRecipe.batchRecipesDescription')
                </p>
            </div>
            <div class="mt-5 flex lg:mt-0 lg:ml-4 space-x-3">
                @if(user_can('Create Batch Recipe'))
                <x-button wire:click="addBatchRecipe" type="button" class="inline-flex gap-1 items-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    @lang('inventory::modules.batchRecipe.addBatchRecipe')
                </x-button>
                @endif
            </div>
        </div>

        <!-- Search -->
        <div class="my-6">
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

        <!-- Batch Recipes Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.batchRecipe.name')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.batchRecipe.yieldUnit')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.batchRecipe.defaultBatchSize')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.batchRecipe.ingredients')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                @lang('inventory::modules.batchRecipe.actions')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($batchRecipes as $batchRecipe)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $batchRecipe->name }}</div>
                                    @if($batchRecipe->description)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($batchRecipe->description, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $batchRecipe->yieldUnit->symbol }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ number_format($batchRecipe->default_batch_size, 2) }} {{ $batchRecipe->yieldUnit->symbol }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $batchRecipe->recipeItems->count() }} @lang('inventory::modules.batchRecipe.ingredientsCount')
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if(user_can('Update Batch Recipe'))
                                    <button wire:click="editBatchRecipe({{ $batchRecipe->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                        @lang('inventory::modules.batchRecipe.edit')
                                    </button>
                                    @endif
                                    @if(user_can('Delete Batch Recipe'))
                                    <button wire:click="showDeleteBatchRecipe({{ $batchRecipe->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        @lang('inventory::modules.batchRecipe.delete')
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    @lang('inventory::modules.batchRecipe.noBatchRecipesFound')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $batchRecipes->links() }}
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <x-right-modal wire:model.live="showAddBatchRecipe">
        <x-slot name="title">
            @if($isEditing)
                @lang('inventory::modules.batchRecipe.editBatchRecipe')
            @else
                @lang('inventory::modules.batchRecipe.addBatchRecipe')
            @endif
        </x-slot>
        <x-slot name="content">
            <livewire:inventory::batch-recipes.batch-recipe-form />
        </x-slot>
    </x-right-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="confirmDeleteBatchRecipe">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                @lang('inventory::modules.batchRecipe.confirmDelete')
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                @lang('inventory::modules.batchRecipe.confirmDeleteMessage')
            </p>
            <div class="flex justify-end space-x-3">
                <x-secondary-button wire:click="$set('confirmDeleteBatchRecipe', false)">
                    @lang('inventory::modules.batchRecipe.cancel')
                </x-secondary-button>
                <x-danger-button wire:click="deleteBatchRecipe">
                    @lang('inventory::modules.batchRecipe.delete')
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

