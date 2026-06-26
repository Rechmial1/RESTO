<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="-mb-px flex space-x-4" aria-label="Batch Reports">
        <a href="{{ route('inventory.batch-reports.production') }}"
           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs {{ request()->routeIs('inventory.batch-reports.production')
                ? 'border-skin-base text-skin-base dark:text-skin-base'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.reports.batch_production.title') }}
        </a>

        <a href="{{ route('inventory.batch-reports.consumption') }}"
           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs {{ request()->routeIs('inventory.batch-reports.consumption')
                ? 'border-skin-base text-skin-base dark:text-skin-base'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.reports.batch_consumption.title') }}
        </a>

        <a href="{{ route('inventory.batch-reports.expected-actual') }}"
           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs {{ request()->routeIs('inventory.batch-reports.expected-actual')
                ? 'border-skin-base text-skin-base dark:text-skin-base'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.reports.tabs.batch_production') }} vs {{ __('inventory::modules.reports.tabs.batch_consumption') }}
        </a>

        <a href="{{ route('inventory.batch-reports.waste-expiry') }}"
           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs {{ request()->routeIs('inventory.batch-reports.waste-expiry')
                ? 'border-skin-base text-skin-base dark:text-skin-base'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.reports.batch_waste.title') }}
        </a>

        <a href="{{ route('inventory.batch-reports.cogs') }}"
           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs {{ request()->routeIs('inventory.batch-reports.cogs')
                ? 'border-skin-base text-skin-base dark:text-skin-base'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
            {{ __('inventory::modules.reports.batch_cogs.title') }}
        </a>
    </nav>
</div>








