@extends('layouts.app')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <h2 class="text-2xl font-semibold">@lang('cashregister::app.registerDashboard')</h2>

    @livewire('cash-register.dashboard.register-dashboard')
</div>
@endsection


