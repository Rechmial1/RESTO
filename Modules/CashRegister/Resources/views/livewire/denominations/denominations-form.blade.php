<div>
    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800" wire:click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        @if($isEditMode)
                            @lang('cashregister::app.editDenomination')
                        @else
                            @lang('cashregister::app.addDenomination')
                        @endif
                    </h3>
                    <button 
                        wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="save" class="mt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                @lang('cashregister::app.name') <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name"
                                wire:model.blur="name"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                placeholder="@lang('cashregister::app.enterDenominationName')"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Value -->
                        <div>
                            <label for="value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                @lang('cashregister::app.value') <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    id="value"
                                    wire:model.blur="value"
                                    step="0.01"
                                    min="0.01"
                                    max="999999.99"
                                    class="block w-full px-3 py-2 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('value') border-red-500 @enderror"
                                    placeholder="0.00"
                                >
                                
                            </div>
                            @error('value')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                @lang('cashregister::app.type') <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="type"
                                wire:model.blur="type"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-500 @enderror"
                            >
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        

                        <!-- Status -->
                        <div>
                            <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                @lang('cashregister::app.status')
                            </label>
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="is_active"
                                    wire:model.blur="is_active"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded"
                                >
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                    @lang('cashregister::app.active')
                                </label>
                            </div>
                            @error('is_active')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                @lang('cashregister::app.description')
                            </label>
                            <textarea 
                                id="description"
                                wire:model.blur="description"
                                rows="3"
                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                placeholder="@lang('cashregister::app.enterDescription')"
                            ></textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Preview Section -->
                    @if($name && $value > 0)
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                @lang('cashregister::app.preview')
                            </h4>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center
                                        @if($type === 'coin') bg-yellow-100 dark:bg-yellow-900
                                        @elseif($type === 'note') bg-green-100 dark:bg-green-900
                                        @else bg-blue-100 dark:bg-blue-900
                                        @endif">
                                        <span class="text-lg font-bold
                                            @if($type === 'coin') text-yellow-800 dark:text-yellow-200
                                            @elseif($type === 'note') text-green-800 dark:text-green-200
                                            @else text-blue-800 dark:text-blue-200
                                            @endif">
                                            {{ number_format($value, 0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($value, 2) }} • 
                                        <span class="capitalize">{{ $types[$type] ?? $type }}</span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @endif">
                                        @if($is_active)
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            @lang('cashregister::app.active')
                                        @else
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                            @lang('cashregister::app.inactive')
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Modal Footer -->
                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button 
                            type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                            @lang('cashregister::app.cancel')
                        </button>
                        <x-button target="save">
                            @if($isEditMode)
                                @lang('cashregister::app.updateDenomination')
                            @else
                                @lang('cashregister::app.createDenomination')
                            @endif
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
