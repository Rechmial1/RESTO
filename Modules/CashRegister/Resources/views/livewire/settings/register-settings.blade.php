<div>
    <div class="p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800 mx-4 my-4">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('cashregister::app.registerSettings')</h3>

        <form wire:submit.prevent="save">
            <div class="grid gap-6">
                <!-- Force Open After Login -->
                <div class="flex items-center justify-between py-4">

                    <div class="flex flex-col flex-grow">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            @lang('cashregister::app.forceOpenAfterLogin')
                        </div>
                        <div class="text-base font-normal text-gray-500 dark:text-gray-400">
                            @lang('cashregister::app.forceOpenAfterLoginDescription')
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="forceOpenAfterLogin" wire:model.live="forceOpenAfterLogin" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    </div>
                </div>

                <!-- Role Selection -->
                @if($forceOpenAfterLogin)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            @lang('cashregister::app.selectRoles')
                        </h4>

                        @if(count($availableRoles) > 0)
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach($availableRoles as $role)
                                    <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-sm hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                        <div class="flex-1">
                                            <label for="role_{{ $role->id }}" class="text-base font-medium text-gray-900 dark:text-white cursor-pointer">
                                                {{ $role->display_name }}
                                            </label>
                                        </div>
                                        <input
                                            type="checkbox"
                                            id="role_{{ $role->id }}"
                                            wire:model="selectedRoles"
                                            value="{{ $role->id }}"
                                            class="w-4 h-4 border-gray-300 rounded text-primary-600 focus:ring-primary-500"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                            @lang('cashregister::app.noRolesAvailable')
                                        </p>
                                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                            @lang('cashregister::app.noRolesAvailableDescription')
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @error('selectedRoles')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <!-- Save Button -->
                <div class="mt-6">
                    <x-button>@lang('app.save')</x-button>
                </div>
            </div>
        </form>

    </div>
</div>
