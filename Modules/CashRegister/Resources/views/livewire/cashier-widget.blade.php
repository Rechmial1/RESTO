<div class="space-y-6">
    <!-- Session Status - Full Width -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            @can('Open Cash Register')
                @if(session('intended_after_register') && $forceOpen)
                    <div class="mb-4 p-3 text-sm rounded-md bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200">
                        @lang('cashregister::app.cashRegister') - @lang('cashregister::app.openRegister')
                    </div>
                @endif
            @endcan
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
                @lang('cashregister::app.sessionStatus')
                @if($session)
                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400 flex items-center">
                        (<span class="mr-1">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.418-5.03-8-8.118-8-12A8 8 0 1 1 20 9c0 3.882-3.582 6.97-8 12zm0-9a3 3 0 100-6 3 3 0 000 6z"/>
                            </svg>
                        </span>
                        <span>{{ $session->branch->name }}</span>)
                    </span>
                @endif
            </h3>

            @if($session)
                <!-- Session Active Banner -->
                <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-lg font-semibold text-green-800 dark:text-green-200">@lang('cashregister::app.sessionActive')</h4>
                                <p class="text-sm text-green-700 dark:text-green-300">@lang('cashregister::app.openStatus') - {{ $session->opened_at?->timezone(timezone())?->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-green-700 dark:text-green-300">@lang('cashregister::app.expectedCash')</div>
                            <div class="text-xl font-bold text-green-800 dark:text-green-200">{{ currency_format((float) $expectedCash, restaurant()->currency_id) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Cashier Information -->
                <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.cashier')</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $session->cashier->name ?? $session->openedBy->name }}</span>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @cannot('Open Cash Register')
                        <div class="md:col-span-3">
                            <div class="p-3 rounded-md bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800 text-sm text-yellow-800 dark:text-yellow-200">
                                @lang('cashregister::app.noPermissionOpenRegister')
                            </div>
                        </div>
                    @endcannot
                    <div class="md:col-span-2">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">@lang('cashregister::app.noActiveSession')</div>

                        @can('Open Cash Register')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('cashregister::app.openingBalance')</label>
                                <input type="number" step="0.01" wire:model.live="openingFloat" placeholder="0.00"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div class="flex items-end">
                                <x-button type="button" wire:click="openRegister" class="w-full flex justify-center items-center py-3 min-h-[46px]">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    @lang('cashregister::app.openRegister')
                                </x-button>
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Column 1 - Register Status -->
        @if($session)
        <div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        @lang('cashregister::app.registerStatus')
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.openingBalance')</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currency_format((float) $openingFloat, restaurant()->currency_id) }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.cashSalesLabel')</div>
                            <div class="text-xl font-bold text-green-600">{{ currency_format((float) $cashSales, restaurant()->currency_id) }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.cashOutLabel')</div>
                            <div class="text-xl font-bold text-red-600">-{{ currency_format((float) $cashOut, restaurant()->currency_id) }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.safeDropLabel')</div>
                            <div class="text-xl font-bold text-blue-600">-{{ currency_format((float) $safeDrop, restaurant()->currency_id) }}</div>
                        </div>
                    </div>

                    <div class="mt-4 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">@lang('cashregister::app.expectedCash')</span>
                            <span class="text-2xl font-bold text-indigo-600">{{ currency_format((float) $expectedCash, restaurant()->currency_id) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Column 2 - Quick Actions -->
        @if($session)
        <div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                        </svg>
                        @lang('cashregister::app.quickActions')
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('cashregister::app.amount')</label>
                            <input type="number" step="0.01" wire:model.live="amount" placeholder="0.00"
                                   class="w-full rounded-lg px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent border @error('amount') border-rose-500 focus:ring-rose-500 @else border-gray-300 dark:border-gray-600 @enderror" />
                            <x-input-error for="amount" class="mt-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('cashregister::app.reason')</label>
                            <input type="text" wire:model.live="reason" placeholder="@lang('cashregister::app.enterReason')"
                                   class="w-full rounded-lg px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent border @error('reason') border-rose-500 focus:ring-rose-500 @else border-gray-300 dark:border-gray-600 @enderror" />
                            <x-input-error for="reason" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <x-button type="button" wire:click="confirmCashIn" class="w-full">@lang('cashregister::app.cashIn')</x-button>
                            <x-button type="button" wire:click="confirmCashOut" class="w-full">@lang('cashregister::app.cashOutLabel')</x-button>
                            <x-button type="button" wire:click="confirmSafeDrop" class="w-full">@lang('cashregister::app.safeDropLabel')</x-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Column 3 - Reports & Close Register -->
        <div class="space-y-8">

            <!-- Reports Card -->
            {{-- <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                        </svg>
                        Reports
                    </h3>
                    <button onclick="window.location='{{ route('cashregister.reports') }}'"
                            class="w-full flex items-center justify-center px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z" />
                            <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.5" fill="none"/>
                        </svg>
                        View Reports
                    </button>
                </div>
            </div> --}}

            <!-- Close Register Card -->
            @if($session)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        @lang('cashregister::app.closeRegister')
                    </h3>

                    @if(!$showClose)
                        <button wire:click="startClose"
                                class="w-full flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            @lang('cashregister::app.closeRegister')
                        </button>
                    @else
                        <div class="space-y-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.enterDenomsForClosing')</div>

                            <div>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($denoms as $index => $d)
                                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ currency_format((float) $d['value'], restaurant()->currency_id) }}</div>
                                            <div class="space-y-2">
                                                <input type="number" min="0" wire:model.live="denoms.{{ $index }}.count"
                                                        class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center" />
                                                <div class="text-sm font-semibold text-gray-900 dark:text-white text-left">{{ currency_format((float) ($d['subtotal'] ?? 0), restaurant()->currency_id) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-between items-center p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                <span class="font-semibold text-gray-900 dark:text-white">@lang('cashregister::app.countedTotal')</span>
                                <span class="text-xl font-bold text-indigo-600">{{ currency_format((float) $countedCash, restaurant()->currency_id) }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">@lang('cashregister::app.closingNote')</label>
                                <textarea wire:model.live="closingNote" rows="3"
                                          class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="@lang('cashregister::app.closingNotePlaceholder')"></textarea>
                            </div>

                            <div class="w-full">
                                <x-button type="button" wire:click="confirmSubmitClosing" class="w-full flex justify-center items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    @lang('cashregister::app.submitForApproval')
                                </x-button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($session)
    <!-- Full Width Transaction Logs -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">@lang('cashregister::app.transactionLogs')</h3>
            @php
                $txns = \Modules\CashRegister\Entities\CashRegisterTransaction::where('cash_register_session_id', $session->id)
                    ->orderByDesc('happened_at')
                    ->limit(20)
                    ->get();
            @endphp
            @if($txns->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">@lang('cashregister::app.dateTime')</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">@lang('cashregister::app.type')</th>
                                <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">@lang('cashregister::app.amount')</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-300">@lang('cashregister::app.reason')</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($txns as $t)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $t->happened_at?->timezone(timezone())?->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($t->type==='cash_in' || $t->type==='cash_sale') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @elseif($t->type==='cash_out') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @endif">
                                            @lang('app.' . $t->type)
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right @if($t->type==='cash_in' || $t->type==='cash_sale') text-green-600 dark:text-green-400 @elseif($t->type==='cash_out') text-red-600 dark:text-red-400 @else text-blue-600 dark:text-blue-400 @endif">
                                        {{ ($t->type==='cash_out' ? '-' : '+') . currency_format($t->amount, restaurant()->currency_id) }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $t->reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">@lang('cashregister::app.noDataAvailable')</p>
            @endif
        </div>
    </div>
    @endif

    <x-confirmation-modal wire:model.live="confirming">
        <x-slot name="title">{{ $confirmTitle }}</x-slot>
        <x-slot name="content">{{ $confirmMessage }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirming', false)">Cancel</x-secondary-button>
            <x-button class="ml-2" wire:click="performConfirmed">Confirm</x-button>
        </x-slot>
    </x-confirmation-modal>
</div>


