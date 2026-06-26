<div class="w-full px-4 sm:px-6 lg:px-8 py-6 mb-8">
    <div class="flex items-start justify-between mb-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">@lang('cashregister::app.approvals')</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('cashregister::app.approvalsSubtitle')</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="items-center justify-between block sm:flex ">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="ltr:sm:pr-3 rtl:sm:pl-3" action="#" method="GET">
                    <div class="lg:flex gap-2 items-center">
                        <x-select class="block w-fit" wire:model="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="currentWeek">@lang('app.currentWeek')</option>
                            <option value="lastWeek">@lang('app.lastWeek')</option>
                            <option value="last7Days">@lang('app.last7Days')</option>
                            <option value="currentMonth">@lang('app.currentMonth')</option>
                            <option value="lastMonth">@lang('app.lastMonth')</option>
                            <option value="currentYear">@lang('app.currentYear')</option>
                            <option value="lastYear">@lang('app.lastYear')</option>
                            <option value="custom">@lang('cashregister::app.customRange')</option>
                        </x-select>

                        <div id="date-range-picker" date-rangepicker class="flex items-center w-full">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-start" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' wire:change="setStartDate($event.target.value)" placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-end" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' wire:change="setEndDate($event.target.value)" placeholder="@lang('app.selectEndDate')">
                            </div>
                        </div>
                    </div>
                </form>

                <div class="inline-flex gap-2 ml-0 lg:ml-3">
                    <x-select class="text-sm w-full" wire:model.live.debounce.250ms='status'>
                        <option value="pending_approval">@lang('app.showAll') @lang('cashregister::app.pending')</option>
                        <option value="closed">@lang('cashregister::app.approved')</option>
                        <option value="open">@lang('cashregister::app.open')</option>
                    </x-select>

                    <x-select class="text-sm w-full" wire:model.live.debounce.250ms='branchId'>
                        <option value="">@lang('cashregister::app.allBranches')</option>
                        @foreach($branches as $b)
                            <option value="{{ $b['id'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </x-select>

                    {{-- <x-input type="text" class="text-sm w-48" wire:model.live.debounce.300ms='search' placeholder="Search cashier/register" /> --}}
                </div>

            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="py-2 pr-4">@lang('cashregister::app.date')</th>
                        <th class="py-2 pr-4">@lang('cashregister::app.registerCol')</th>
                        <th class="py-2 pr-4">@lang('cashregister::app.cashierCol')</th>
                        <th class="py-2 pr-4 text-right">@lang('cashregister::app.expectedCol')</th>
                        <th class="py-2 pr-4 text-right">@lang('cashregister::app.countedCol')</th>
                        <th class="py-2 pr-4 text-right">@lang('cashregister::app.diffCol')</th>
                        <th class="py-2 pr-4">@lang('cashregister::app.noteCol')</th>
                        <th class="py-2">@lang('cashregister::app.actions')</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sessions as $s)
                        <tr class="text-gray-900 dark:text-gray-100">
                            <td class="py-2 pr-4">{{ $s->closed_at?->timezone(timezone())?->format('d M Y, h:i A') }}</td>
                            <td class="py-2 pr-4">{{ $s->register?->name ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $s->cashier?->name ?? '—' }}</td>
                            <td class="py-2 pr-4 text-right">{{ currency_format((float) ($s->expected_cash ?? 0), restaurant()->currency_id) }}</td>
                            <td class="py-2 pr-4 text-right">{{ currency_format((float) ($s->counted_cash ?? 0), restaurant()->currency_id) }}</td>
                            @php $diff = ($s->counted_cash ?? 0) - ($s->expected_cash ?? 0); @endphp
                            <td class="py-2 pr-4 text-right @if(abs($diff)>=200) text-red-600 @elseif(abs($diff)>=50) text-amber-600 @else text-green-600 @endif">
                                {{ $diff >= 0 ? '+' : '' }}{{ currency_format((float) $diff, restaurant()->currency_id) }}
                            </td>
                            <td class="py-2 pr-4 max-w-xs truncate" title="{{ $s->closing_note }}">{{ $s->closing_note }}</td>
                            <td class="py-2">
                                <div class="flex items-center gap-2">
                                    <x-button type="button" onclick="window.location='{{ route('cashregister.reports') }}'">@lang('cashregister::app.viewReport')</x-button>
                                    <x-button type="button" wire:click="approve({{ $s->id }})" class="bg-emerald-600 hover:bg-emerald-700">@lang('cashregister::app.approve')</x-button>
                                    <x-button type="button" wire:click="reject({{ $s->id }})" class="bg-rose-600 hover:bg-rose-700">@lang('cashregister::app.reopen')</x-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-gray-500 dark:text-gray-400">@lang('cashregister::app.noSessionsPending')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">
            {{ $sessions->links() }}
        </div>
    </div>
</div>

@script
<script>
    const datepickerEl1 = document.getElementById('datepicker-range-start');

    datepickerEl1.addEventListener('changeDate', (event) => {
        $wire.dispatch('setStartDate', { start: datepickerEl1.value });
    });

    const datepickerEl2 = document.getElementById('datepicker-range-end');

    datepickerEl2.addEventListener('changeDate', (event) => {
        $wire.dispatch('setEndDate', { end: datepickerEl2.value });
    });
</script>
@endscript


