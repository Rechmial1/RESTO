<div>
    
    <x-inventory::reports.tabs />
    <!-- Title Section -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('inventory::modules.reports.turnover.title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('inventory::modules.reports.turnover.description') }}
        </p>
    </div>


    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.reports.purchase_orders.stats.total_amount') }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-2">
                    {{ currency_format($stats['total_amount'], restaurant()->currency_id) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ trans('inventory::modules.purchaseOrder.total_orders') }}: {{ $stats['order_count'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.reports.purchase_orders.stats.paid_amount') }}</p>
                <p class="text-2xl font-semibold text-green-600 dark:text-green-400 mt-2">
                    {{ currency_format($stats['paid_amount'], restaurant()->currency_id) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.reports.purchase_orders.stats.due_amount') }}</p>
                <p class="text-2xl font-semibold text-amber-600 dark:text-amber-400 mt-2">
                    {{ currency_format($stats['due_amount'], restaurant()->currency_id) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inventory::modules.reports.purchase_orders.stats.progress') }}</p>
                <p class="text-2xl font-semibold text-blue-600 dark:text-blue-400 mt-2">{{ $stats['average_progress'] }}%</p>
                <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-blue-500" style="width: {{ min($stats['average_progress'], 100) }}%"></div>
                </div>
            </div>
        </div>

    <div class="flex justify-end">
        <button wire:click="exportCsv" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium py-2 px-3 rounded-md transition duration-200">
            {{ __('inventory::modules.reports.purchase_orders.export_csv') }}
        </button>
    </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.purchase_orders.filters.start_date') }}
                    </label>
                    <x-input type="date" wire:model.live="startDate" class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.purchase_orders.filters.end_date') }}
                    </label>
                    <x-input type="date" wire:model.live="endDate" class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.purchase_orders.filters.supplier') }}
                    </label>
                    <x-select wire:model.live="supplierId" class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        <option value="">{{ __('inventory::modules.purchaseOrder.all_suppliers') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.purchase_orders.filters.status') }}
                    </label>
                    <x-select wire:model.live="status" class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        @foreach($orderStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.purchase_orders.filters.payment_status') }}
                    </label>
                    <x-select wire:model.live="paymentStatus" class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        @foreach($paymentStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('inventory::modules.reports.purchase_orders.filters.per_page') }}
                    </label>
                    <x-select wire:model.live="perPage" class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </x-select>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <x-secondary-button wire:click="clearFilters">
                    {{ __('inventory::modules.reports.purchase_orders.filters.clear') }}
                </x-secondary-button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.po_number') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.supplier') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.order_date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.status') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.total') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.paid') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.due') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('inventory::modules.reports.purchase_orders.table.progress') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($purchaseOrders as $order)
                            @php
                                $paidAmount = (float) $order->paid_amount;
                                $dueAmount = max($order->total_amount - $paidAmount, 0);
                                $progress = $order->total_amount > 0 ? min(($paidAmount / $order->total_amount) * 100, 100) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $order->po_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $order->supplier?->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $order->order_date?->translatedFormat('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $order->status === 'draft' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}
                                        {{ $order->status === 'sent' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : '' }}
                                        {{ $order->status === 'received' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : '' }}
                                        {{ $order->status === 'partially_received' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : '' }}
                                        {{ $order->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' : '' }}">
                                        {{ $orderStatuses[$order->status] ?? ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ currency_format($order->total_amount, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                    {{ currency_format($paidAmount, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm {{ $dueAmount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ currency_format($dueAmount, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-full rounded-full {{ $progress >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ number_format($progress, 0) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 text-sm">
                                    {{ __('inventory::modules.reports.purchase_orders.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $purchaseOrders->links() }}
            </div>
        </div>
    </div>

</div>