@extends('layouts.app')

@section('content')
    <div class="p-6">
        <h2 class="text-xl font-semibold mb-4">@lang('cashregister::app.cashRegisterModule')</h2>
        <div class="space-y-2">
            <a href="{{ route('cashregister.dashboard') }}" class="text-skin-base underline">@lang('cashregister::app.linkRegisterDashboard')</a>
            <a href="{{ route('cashregister.cashier') }}" class="text-skin-base underline">@lang('cashregister::app.linkCashRegister')</a>
            <a href="{{ route('cashregister.reports') }}" class="text-skin-base underline">@lang('cashregister::app.linkReports')</a>
            <a href="{{ route('cashregister.denominations.index') }}" class="text-skin-base underline">@lang('cashregister::app.linkDenominations')</a>
        </div>
    </div>
@endsection
