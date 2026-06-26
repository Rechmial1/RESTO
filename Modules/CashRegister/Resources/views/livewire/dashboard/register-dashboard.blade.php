<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Statistics</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Total Cash Sales Today -->
        <div class="p-5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Cash Sales Today</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ currency_format($totalCashSalesToday, restaurant()->currency_id) }}</div>
                    @php $pct = round($pctCashToday, 1); @endphp
                    <div class="mt-1 text-xs">
                        <span class="font-medium {{ $pct >= 0 ? 'text-green-600' : 'text-rose-600' }}">{{ $pct >= 0 ? '▲' : '▼' }} {{ abs($pct) }}%</span>
                        <span class="text-gray-500">vs yesterday</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center shadow">
                    <!-- icon: Cash/Banknote style for better clarity -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <rect x="2.25" y="6.75" width="19.5" height="10.5" rx="2.25" fill="currentColor" class="text-gray-200 dark:text-gray-700"/>
                        <rect x="2.25" y="6.75" width="19.5" height="10.5" rx="2.25" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="12" cy="12" r="2.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        <path stroke="currentColor" stroke-width="1.5" d="M2.25 9a2.25 2.25 0 0 0 2.25 2.25M21.75 9a2.25 2.25 0 0 1-2.25 2.25M2.25 15a2.25 2.25 0 0 1 2.25-2.25M21.75 15a2.25 2.25 0 0 0-2.25-2.25"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Safe Drops Today -->
        <div class="p-5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Safe Drops Today</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ currency_format($safeDropsToday, restaurant()->currency_id) }}</div>
                    @php $pctS = round($pctSafeDropToday, 1); @endphp
                    <div class="mt-1 text-xs">
                        <span class="font-medium {{ $pctS >= 0 ? 'text-green-600' : 'text-rose-600' }}">{{ $pctS >= 0 ? '▲' : '▼' }} {{ abs($pctS) }}%</span>
                        <span class="text-gray-500">vs yesterday</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center shadow">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-10a1 1 0 10-2 0v3a1 1 0 00.293.707l2 2a1 1 0 001.414-1.414L11 10.586V8z" clip-rule="evenodd"/></svg>
                </div>
            </div>
        </div>

        <!-- Sessions with Discrepancy -->
        <div class="p-5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Sessions with Discrepancy (7 days)</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $sessionsWithDiscrepancy7Days }}</div>
                    <div class="mt-1 text-xs text-gray-500">+0.0% vs last week</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center shadow">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8.257 3.099c.765-1.36 2.721-1.36 3.486 0l6.518 11.59c.75 1.335-.213 3.011-1.743 3.011H3.482c-1.53 0-2.493-1.676-1.743-3.011L8.257 3.1zM11 14a1 1 0 10-2 0 1 1 0 002 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z"/></svg>
                </div>
            </div>
        </div>

        <!-- Largest Cash-Out Reason -->
        <div class="p-5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Largest Cash-Out Reason (30 days)</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $largestCashOutReason }}</div>
            <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ currency_format($largestCashOutAmount, restaurant()->currency_id) }}</div>
            <div class="mt-1 text-xs">
                @php $pct = round($pctLargestCashOut30, 1); @endphp
                <span class="font-medium {{ $pct >= 0 ? 'text-green-600' : 'text-rose-600' }}">{{ $pct >= 0 ? '▲' : '▼' }} {{ abs($pct) }}%</span>
                <span class="text-gray-500">vs previous 30 days</span>
            </div>
        </div>

        <!-- Average Discrepancy -->
        <div class="p-5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Average Discrepancy per Session (30 days)</div>
            <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ currency_format($avgDiscrepancy30Days, restaurant()->currency_id) }}</div>
            <div class="mt-1 text-xs">
                @php $pct2 = round($pctAvgDisc30, 1); @endphp
                <span class="font-medium {{ $pct2 >= 0 ? 'text-rose-600' : 'text-green-600' }}">{{ $pct2 >= 0 ? '▲' : '▼' }} {{ abs($pct2) }}%</span>
                <span class="text-gray-500">vs previous 30 days</span>
            </div>
        </div>
    </div>
</div>


