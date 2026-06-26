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

                        <div id="date-range-picker-inout" date-rangepicker class="flex items-center w-full">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-start-inout" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-end-inout" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' placeholder="@lang('app.selectEndDate')">
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
                    <x-select class="text-sm w-full" wire:model.live='type'>
                        <option value="">@lang('cashregister::app.type')</option>
                        <option value="cash_in">@lang('app.cash_in')</option>
                        <option value="cash_out">@lang('app.cash_out')</option>
                        <option value="safe_drop">@lang('cashregister::app.safeDropLabel')</option>
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

    @if($transactions->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <!-- Report Header -->
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 items-start">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            @lang('cashregister::app.cashInOutSummary')
                        </h3>
                        <a href="{{ route('cashregister.export.cash-in-out', ['start' => $startDate, 'end' => $endDate, 'branch' => $branchId, 'register' => $registerId, 'cashier' => $cashierId, 'type' => $type]) }}" class="mt-1 inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('cashregister::app.exportCsv')</a>
                    </div>
                    <div class="md:col-span-1 text-right space-y-1 mt-4 md:mt-0">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.totalTransactions'): {{ $summary['total_transactions'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.netCashFlow'): {{ currency_format($summary['net_cash_flow'], restaurant()->currency_id) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                @lang('cashregister::app.totalCashIn')
                            </p>
                            <p class="text-sm text-green-600 dark:text-green-400">
                                {{ currency_format($summary['total_cash_in'], restaurant()->currency_id) }}
                            </p>
                            <p class="text-xs text-green-500 dark:text-green-300">
                                {{ $summary['cash_in_count'] }} @lang('cashregister::app.transactions')
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                @lang('cashregister::app.totalCashOut')
                            </p>
                            <p class="text-sm text-red-600 dark:text-red-400">
                                {{ currency_format($summary['total_cash_out'], restaurant()->currency_id) }}
                            </p>
                            <p class="text-xs text-red-500 dark:text-red-300">
                                {{ $summary['cash_out_count'] }} @lang('cashregister::app.transactions')
                            </p>
                        </div>
                    </div>
                </div>                
                
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-indigo-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                                @lang('cashregister::app.safeDropLabel')
                            </p>
                            <p class="text-sm text-indigo-600 dark:text-indigo-400">
                                -{{ currency_format($summary['total_safe_drop'] ?? 0, restaurant()->currency_id) }}
                            </p>
                            <p class="text-xs text-indigo-500 dark:text-indigo-300">
                                {{ $summary['safe_drop_count'] ?? 0 }} @lang('cashregister::app.transactions')
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                @lang('cashregister::app.netCashFlow')
                            </p>
                            <p class="text-sm {{ $summary['net_cash_flow'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $summary['net_cash_flow'] >= 0 ? '+' : '' }}{{ currency_format($summary['net_cash_flow'], restaurant()->currency_id) }}
                            </p>
                            <p class="text-xs text-blue-500 dark:text-blue-300">
                                {{ $summary['net_cash_flow'] >= 0 ? __('cashregister::app.netInflow') : __('cashregister::app.netOutflow') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"></thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.dateTime')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.cashier')
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.type')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.amount')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.reason')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->created_at->timezone(timezone())->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->session->cashier->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($transaction->type === 'cash_in')
                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($transaction->type === 'cash_out')
                                            bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @endif">
                                        @lang('app.' . $transaction->type)
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm @if($transaction->type==='cash_in') text-green-600 dark:text-green-400 @elseif($transaction->type==='cash_out') text-red-600 dark:text-red-400 @else text-blue-600 dark:text-blue-400 @endif text-right">
                                    {{ ($transaction->type==='cash_out' || $transaction->type==='safe_drop' ? '-' : '+') . currency_format($transaction->amount, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $transaction->reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary Stats -->
            <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.averageCashIn')</p>
                        <p class="font-semibold text-green-600 dark:text-green-400">
                            {{ $summary['cash_in_count'] > 0 ? currency_format($summary['total_cash_in'] / $summary['cash_in_count'], restaurant()->currency_id) : currency_format(0, restaurant()->currency_id) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.averageCashOut')</p>
                        <p class="font-semibold text-red-600 dark:text-red-400">
                            {{ $summary['cash_out_count'] > 0 ? currency_format($summary['total_cash_out'] / $summary['cash_out_count'], restaurant()->currency_id) : currency_format(0, restaurant()->currency_id) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400">@lang('cashregister::app.transactionsPerDay')</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ Carbon\Carbon::parse($startDate)->diffInDays(Carbon\Carbon::parse($endDate)) + 1 > 0 ? round($summary['total_transactions'] / (Carbon\Carbon::parse($startDate)->diffInDays(Carbon\Carbon::parse($endDate)) + 1), 1) : 0 }}
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
                    @lang('cashregister::app.noCashInOutTransactions')
                </p>
            </div>
        </div>
    @endif
</div>

@script
<script>
    const datepickerEl1 = document.getElementById('datepicker-range-start-inout');

    datepickerEl1.addEventListener('changeDate', (event) => {
        $wire.dispatch('setStartDate', { start: datepickerEl1.value });
    });

    const datepickerEl2 = document.getElementById('datepicker-range-end-inout');

    datepickerEl2.addEventListener('changeDate', (event) => {
        $wire.dispatch('setEndDate', { end: datepickerEl2.value });
    });
</script>
@endscript
