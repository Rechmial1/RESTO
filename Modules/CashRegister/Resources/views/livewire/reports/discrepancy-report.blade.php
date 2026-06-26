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

                        <div id="date-range-picker-disc" date-rangepicker class="flex items-center w-full">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-start-disc" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-end-disc" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' placeholder="@lang('app.selectEndDate')">
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
                            @lang('cashregister::app.discrepancyReportClosings')
                        </h3>
                        <a href="{{ route('cashregister.export.discrepancy', ['start' => $startDate, 'end' => $endDate, 'branch' => $branchId]) }}" class="mt-1 inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">@lang('cashregister::app.exportCsv')</a>
                    </div>
                    <div class="md:col-span-1 text-right space-y-1 mt-4 md:mt-0">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.totalSessions'): {{ $sessions->count() }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.sessionsWithDiscrepancy'): {{ $sessions->where('discrepancy', '!=', 0)->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                @lang('cashregister::app.highDiscrepancy') (≥ 200)
                            </p>
                            <p class="text-sm text-red-600 dark:text-red-400">
                                {{ $sessions->filter(function($s) { return abs($s->discrepancy) >= 200; })->count() }} @lang('cashregister::app.sessions')
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
                                @lang('cashregister::app.mediumDiscrepancy') (50-199)
                            </p>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">
                                {{ $sessions->filter(function($s) { return abs($s->discrepancy) >= 50 && abs($s->discrepancy) < 200; })->count() }} @lang('cashregister::app.sessions')
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
                                @lang('cashregister::app.lowDiscrepancy') (< 50)
                            </p>
                            <p class="text-sm text-green-600 dark:text-green-400">
                                {{ $sessions->filter(function($s) { return abs($s->discrepancy) < 50; })->count() }} @lang('cashregister::app.sessions')
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Data -->
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
                                @lang('cashregister::app.expected')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.counted')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.diff')
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.status')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.managerNote')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sessions as $session)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $session->closed_at->timezone(timezone())->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $session->cashier->name ?? __('cashregister::app.unknown') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($session->expected_cash, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($session->counted_cash, restaurant()->currency_id) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm {{ $this->getDiscrepancyColor($session->discrepancy) }} text-right">
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
                                        @else
                                            bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                        @endif">
                                        @lang('app.' . $session->status)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $session->closing_reason ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                    @lang('cashregister::app.noClosedSessionsFoundForSelectedCriteria')
                </p>
            </div>
        </div>
    @endif
</div>

@script
<script>
    const datepickerEl1 = document.getElementById('datepicker-range-start-disc');

    datepickerEl1.addEventListener('changeDate', (event) => {
        $wire.dispatch('setStartDate', { start: datepickerEl1.value });
    });

    const datepickerEl2 = document.getElementById('datepicker-range-end-disc');

    datepickerEl2.addEventListener('changeDate', (event) => {
        $wire.dispatch('setEndDate', { end: datepickerEl2.value });
    });
</script>
@endscript

