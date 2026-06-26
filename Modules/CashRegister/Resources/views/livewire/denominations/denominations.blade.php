<div>
    {{-- The Master doesn't talk, he acts. --}}
    <div>

        <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <div class="mb-4">
                    <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">@lang('cashregister::app.denominationManagement')</h1>
                </div>
                <div
                    class="items-center justify-between block sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700">
                    <div class="flex items-center mb-4 sm:mb-0">
                        <form class="sm:pr-3" action="#" method="GET">
                            <label for="products-search" class="sr-only">Search</label>
                            <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                                <x-input id="denominations" class="block mt-1 w-full" type="text"
                                    placeholder="{{ __('cashregister::app.searchDenominations') }}"
                                    wire:model.live="search" />
                            </div>
                        </form>
                    </div>

                    @if (user_can('Manage Cash Denominations'))
                    <x-button type='button' wire:click="openCreateModal">
                        @lang('cashregister::app.addDenomination')
                    </x-button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 mb-8" wire:key="denominations-container">
            @livewire('cashregister::denominations.denominations-table', ['search' => $search])
        </div>

        <!-- Denominations Form Modal -->
        @livewire('cashregister::denominations.denominations-form')

    </div>
</div>
