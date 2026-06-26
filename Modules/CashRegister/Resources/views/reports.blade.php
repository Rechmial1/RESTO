@extends('layouts.app')

@section('content')
<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-0">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">@lang('cashregister::app.reportsTitle')</h1>
            </div>
        </div>
    </div>

    <div class="p-4" x-data="{tab:'x'}">
        <div class="border-b dark:border-gray-700">
            <nav class="-mb-px flex flex-wrap gap-2" aria-label="Tabs">
                <button @click="tab='x'" :class="tab==='x' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">@lang('cashregister::app.xReportTab')</button>
                <button @click="tab='z'" :class="tab==='z' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">@lang('cashregister::app.zReportTab')</button>
                <button @click="tab='disc'" :class="tab==='disc' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">@lang('cashregister::app.discrepancyTab')</button>
                <button @click="tab='ledger'" :class="tab==='ledger' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">@lang('cashregister::app.cashLedgerTab')</button>
                <button @click="tab='summary'" :class="tab==='summary' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">@lang('cashregister::app.cashInOutTab')/Safe-drop</button>
                <button @click="tab='shift'" :class="tab==='shift' ? 'border-b-2 border-skin-base text-skin-base' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="px-3 py-2 text-sm font-medium">@lang('cashregister::app.sessionSummaryTab')</button>
            </nav>
        </div>
        <div class="space-y-6 mt-6">
            <div x-show="tab==='x'">
                @livewire('cash-register.reports.x-report')
            </div>
    
            <div x-show="tab==='z'">
                @livewire('cash-register.reports.z-report')
            </div>
    
            <div x-show="tab==='disc'">
                @livewire('cash-register.reports.discrepancy-report')
            </div>
    
            <div x-show="tab==='ledger'">
                @livewire('cash-register.reports.cash-ledger-report')
            </div>
    
            <div x-show="tab==='summary'">
                @livewire('cash-register.reports.cash-in-out-report')
            </div>
    
            <div x-show="tab==='shift'">
                @livewire('cash-register.reports.shift-summary-report')
            </div>
        </div>
    </div>

</div>
@endsection


