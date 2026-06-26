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

                        <div id="date-range-picker-z" date-rangepicker class="flex items-center w-full">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-start-z" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-end-z" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' placeholder="@lang('app.selectEndDate')">
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

    @if($sessions && count($sessions) > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Closed Sessions</h3>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Branch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cashier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Closed At</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sessions as $s)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">#{{ $s->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $s->branch?->name ?? 'N/A' }}</td>
                                @php
                                    $cashierName = $s->cashier?->name ?? optional(\App\Models\User::find($s->opened_by))->name;
                                @endphp
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $cashierName ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ optional($s->closed_at)->timezone(timezone())->format('d M Y, h:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    <button wire:click="selectSession({{ $s->id }})" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        @lang('cashregister::app.viewZReport')
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(($sessions && count($sessions) > 0) && $reportData)
        <div class="my-6 border-t border-gray-200 dark:border-gray-700"></div>
    @endif

    @if($reportData)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <!-- Report Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            @lang('cashregister::app.zReportHeader')
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            @lang('cashregister::app.generated'): {{ $reportData['generated_at']->timezone(timezone())->format('d M Y, h:i A') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.branch'): {{ $reportData['session']->branch?->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.register'): {{ $reportData['session']->register->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.cashier'): {{ $reportData['session']->cashier->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.closed'): {{ $reportData['session']->closed_at->timezone(timezone())->format('d M Y, h:i A') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Movement Summary -->
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">@lang('cashregister::app.movementSummary')</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    @lang('cashregister::app.lineItem')
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    @lang('cashregister::app.amount')
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.openingFloat')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($reportData['opening_float'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.cashSales')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    {{ currency_format($reportData['cash_sales'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.changeGiven')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                    -{{ currency_format($reportData['change_given'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.cashIn')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-right">
                                    {{ currency_format($reportData['cash_in'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.cashOut')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                    -{{ currency_format($reportData['cash_out'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.safeDrops')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                    -{{ currency_format($reportData['safe_drops'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @lang('cashregister::app.refundsCash')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                    -{{ currency_format($reportData['refunds'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    @lang('cashregister::app.expectedCash')
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                                    {{ currency_format($reportData['expected_cash'], restaurant()->currency_id) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Counted Cash (Denominations) -->
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">@lang('cashregister::app.countedCash') (@lang('cashregister::app.metric'))</h4>
                @if($denominations->count() > 0)
                    @php
                        $grouped = $denominations->groupBy('cash_denomination_id')->map(function($items) {
                            return [
                                'value' => optional($items->first()->denomination)->value,
                                'count' => $items->sum('count'),
                                'subtotal' => $items->sum('subtotal'),
                            ];
                        })->sortByDesc('value');
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('cashregister::app.denomination')
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('cashregister::app.count')
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('cashregister::app.subtotal')
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($grouped as $row)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ currency_format((float) $row['value'], restaurant()->currency_id) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-center">
                                            {{ $row['count'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                            {{ currency_format((float) $row['subtotal'], restaurant()->currency_id) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                        @lang('cashregister::app.total')
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-center">
                                        —
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                                        {{ currency_format($reportData['counted_cash'], restaurant()->currency_id) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        @lang('cashregister::app.noDenominationDataAvailable')
                    </div>
                @endif
            </div>

            <!-- Discrepancy Summary -->
            <div class="mb-6">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-md font-semibold text-gray-900 dark:text-white">@lang('cashregister::app.discrepancySummary')</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                @lang('cashregister::app.expected'): {{ currency_format($reportData['expected_cash'], restaurant()->currency_id) }} | 
                                @lang('cashregister::app.counted'): {{ currency_format($reportData['counted_cash'], restaurant()->currency_id) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold {{ $reportData['discrepancy'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $reportData['discrepancy'] >= 0 ? '+' : '' }}{{ currency_format($reportData['discrepancy'], restaurant()->currency_id) }}
                                @if($reportData['discrepancy'] > 0)
                                    (@lang('cashregister::app.over'))
                                @elseif($reportData['discrepancy'] < 0)
                                    (@lang('cashregister::app.short'))
                                @else
                                    (@lang('cashregister::app.exact'))
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Closing Notes -->
            @if($reportData['session']->closing_reason)
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">@lang('cashregister::app.notes')</h4>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $reportData['session']->closing_reason }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Approval Status -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @lang('cashregister::app.status'): 
                        <span class="font-medium text-gray-900 dark:text-white capitalize">
                            {{ $reportData['session']->status }}
                        </span>
                    </p>
                    @if($reportData['session']->approved_by)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.approvedBy'): {{ $reportData['session']->closer->name ?? __('cashregister::app.manager') }}
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <button wire:click="printReport" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        @lang('cashregister::app.printReport')
                    </button>
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
                    @lang('cashregister::app.noClosedSessionsFoundForSelectedCriteria')
                </p>
            </div>
        </div>
    @endif
</div>

@script
<script>
    const datepickerEl1 = document.getElementById('datepicker-range-start-z');

    datepickerEl1.addEventListener('changeDate', (event) => {
        $wire.dispatch('setStartDate', { start: datepickerEl1.value });
    });

    const datepickerEl2 = document.getElementById('datepicker-range-end-z');

    datepickerEl2.addEventListener('changeDate', (event) => {
        $wire.dispatch('setEndDate', { end: datepickerEl2.value });
    });
    
    // Listen for report image generation event using Livewire
    document.addEventListener('livewire:init', function() {
        Livewire.on('saveReportImageFromPrint', function(sessionId, htmlContent, reportType) {
            console.log('[Z-Report] Generating image for report:', reportType, sessionId);
            
            // This integrates with the main project's image generation system
            // Similar to how KOT and Order images are generated
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlContent;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.width = '80mm';
            document.body.appendChild(tempDiv);
            
            // Trigger image capture (this would integrate with existing image generation)
            setTimeout(() => {
                document.body.removeChild(tempDiv);
                console.log('[Z-Report] Report image generation completed for:', reportType, sessionId);
            }, 1000);
        });
    });

    // Browser popup print event listener (match Orders section pattern)
    $wire.on('print_location', (url) => {
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.target = '_blank';
        console.log('[Z-Report] Opening print popup:', url);
        anchor.click();
    });
</script>
@endscript
