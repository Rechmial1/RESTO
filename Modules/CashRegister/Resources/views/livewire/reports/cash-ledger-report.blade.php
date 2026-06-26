<div class="mb-8">
    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="items-center justify-between block sm:flex ">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="ltr:sm:pr-3 rtl:sm:pl-3" action="#" method="GET">
                    <div class="lg:flex gap-2 items-center">
                        <x-select class="block w-fit" wire:model="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="yesterday">@lang('app.yesterday')</option>
                            <option value="this_week">@lang('app.currentWeek')</option>
                            <option value="last_week">@lang('app.lastWeek')</option>
                            <option value="this_month">@lang('app.currentMonth')</option>
                            <option value="last_month">@lang('app.lastMonth')</option>
                            <option value="custom">@lang('cashregister::app.customRange')</option>
                        </x-select>

                        <div id="date-range-picker-ledger" date-rangepicker class="flex items-center w-full">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-start-ledger" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-end-ledger" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' placeholder="@lang('app.selectEndDate')">
                            </div>
                        </div>
                    </div>
                </form>

                <div class="inline-flex gap-2 ml-0 lg:ml-3">
                    <x-select class="text-sm w-full" wire:model.live='branchId'>
                        <option value="">@lang('cashregister::app.allBranches')</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-select>
                    {{-- <x-select class="text-sm w-full" wire:model.live='registerId'>
                        <option value="">@lang('cashregister::app.allRegisters')</option>
                        @foreach($registers as $register)
                            <option value="{{ $register->id }}">{{ $register->name }}</option>
                        @endforeach
                    </x-select> --}}
                    @if(user_can('View Cash Register Reports'))
                        <x-select class="text-sm w-full" wire:model.live='cashierId'>
                            <option value="">@lang('cashregister::app.allCashiers')</option>
                            @foreach($cashiers as $cashier)
                                <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                            @endforeach
                        </x-select>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($sessions->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <!-- Report Header -->
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 items-start">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            @lang('cashregister::app.cashLedgerByRegister')
                        </h3>
                        <a href="{{ route('cashregister.export.cash-ledger', ['start' => $startDate, 'end' => $endDate, 'branch' => $branchId]) }}" class="mt-1 inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('cashregister::app.exportCsv')</a>
                    </div>
                    <div class="md:col-span-1 text-right space-y-1 mt-4 md:mt-0">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.totalSessions'): {{ $summary['total_sessions'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.totalCashSales'): {{ currency_format($summary['total_cash_sales'], restaurant()->currency_id) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                @lang('cashregister::app.totalOpeningFloat')
                            </p>
                            <p class="text-sm text-blue-600 dark:text-blue-400">
                                {{ currency_format($summary['total_opening_float'], restaurant()->currency_id) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                @lang('cashregister::app.totalCashSales')
                            </p>
                            <p class="text-sm text-green-600 dark:text-green-400">
                                {{ currency_format($summary['total_cash_sales'], restaurant()->currency_id) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                @lang('cashregister::app.totalCashIn')
                            </p>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                {{ currency_format($summary['total_cash_in'], restaurant()->currency_id) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                @lang('cashregister::app.totalCashOut')
                            </p>
                            <p class="text-sm text-red-600 dark:text-red-400">
                                {{ currency_format($summary['total_cash_out'], restaurant()->currency_id) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Sessions Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.date')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.cashier')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.openingFloat')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.cashSales')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.cashIn')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.cashOut')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.safeDrops')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.expectedCash')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.countedCash')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.discrepancy')
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.status')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sessions as $session)
                            @php
                                $transactions = \Modules\CashRegister\Entities\CashRegisterTransaction::where('cash_register_session_id', $session->id)->get();
                                $cashSales = $transactions->where('type', 'cash_sale')->sum('amount');
                                $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
                                $cashOut = $transactions->where('type', 'cash_out')->sum('amount');
                                $safeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $session->opened_at->timezone(timezone())->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $session->cashier->name ?? __('cashregister::app.unknown') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($session->opening_float, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($cashSales, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-right">
                                    {{ currency_format($cashIn, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                    {{ currency_format($cashOut, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                    {{ currency_format($safeDrops, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($session->expected_cash, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($session->counted_cash, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm {{ $session->discrepancy >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} text-right">
                                    {{ $session->discrepancy >= 0 ? '+' : '' }}{{ currency_format($session->discrepancy, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($session->status === 'closed')
                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($session->status === 'pending_approval')
                                            bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($session->status === 'rejected')
                                            bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($session->status === 'open')
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @else
                                            bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                        @endif">
                                        @lang('app.' . $session->status)
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals Row -->
            <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.totalSessions')</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $summary['total_sessions'] }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.totalExpected')</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ currency_format($summary['total_expected_cash'], restaurant()->currency_id) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.totalCounted')</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ currency_format($summary['total_counted_cash'], restaurant()->currency_id) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.totalDiscrepancy')</p>
                        <p class="font-semibold {{ $summary['total_discrepancy'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $summary['total_discrepancy'] >= 0 ? '+' : '' }}{{ currency_format($summary['total_discrepancy'], restaurant()->currency_id) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">@lang('cashregister::app.noDataAvailable')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @lang('cashregister::app.noCashRegisterSessionsFoundForSelectedCriteria')
                </p>
            </div>
        </div>
    @endif
</div>

@script
<script>
    const datepickerEl1 = document.getElementById('datepicker-range-start-ledger');

    datepickerEl1.addEventListener('changeDate', (event) => {
        $wire.dispatch('setStartDate', { start: datepickerEl1.value });
    });

    const datepickerEl2 = document.getElementById('datepicker-range-end-ledger');

    datepickerEl2.addEventListener('changeDate', (event) => {
        $wire.dispatch('setEndDate', { end: datepickerEl2.value });
    });
</script>
@endscript
