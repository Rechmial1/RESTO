@extends('layouts.app')

@section('content')

<div class="p-4 bg-white block  dark:bg-gray-800 dark:border-gray-700">
    <div class="flex justify-between">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">@lang('menu.dashboard')</h1>

        <div class="inline-flex items-center gap-1 dark:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-event" viewBox="0 0 16 16">
                <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
            </svg>

            {{ now()->timezone(timezone())->translatedFormat('l, d M, h:i A') }}
        </div>
    </div>


</div>

@if (!empty(global_setting()->vapid_public_key) && !empty(global_setting()->vapid_private_key))
    <div id="notification-alert" class="px-4">
        <x-alert type="warning" icon="info-circle">
            <div class="flex items-center gap-2">
                @lang('messages.pushNotificationMessage')
                <x-button id="subscribe-button">@lang('messages.enableNotifications')</x-button>
            </div>
        </x-alert>
    </div>
@endif

<x-banner />
@php
    $restaurantModules = restaurant_modules();
    $restaurantId = user()->restaurant_id;
    
    // Get stats for all three modules
    $orderStats = branch() ? getRestaurantOrderStats(branch()->id) : null;
    $staffStats = getRestaurantStaffStats($restaurantId);
    $menuItemStats = getRestaurantMenuItemStats($restaurantId);
    
    // Check which limits are exceeded
    $orderLimitExceeded = in_array('Order', $restaurantModules) && $orderStats && !$orderStats['unlimited'] && $orderStats['current_count'] >= $orderStats['order_limit'];
    $staffLimitExceeded = in_array('Staff', $restaurantModules) && $staffStats && !$staffStats['unlimited'] && $staffStats['current_count'] >= $staffStats['staff_limit'];
    $menuItemLimitExceeded = in_array('Menu Item', $restaurantModules) && $menuItemStats && !$menuItemStats['unlimited'] && $menuItemStats['current_count'] >= $menuItemStats['menu_items_limit'];
    
    $anyLimitExceeded = $orderLimitExceeded || $staffLimitExceeded || $menuItemLimitExceeded;
@endphp

@if($anyLimitExceeded)
<div class="px-4 py-4 space-y-3">
    @if($orderLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.order.orderLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.order.orderLimitExceededMessage', [
                        'current' => number_format($orderStats['current_count']),
                        'limit' => number_format($orderStats['order_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($staffLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.staff.staffLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.staff.staffLimitExceededMessage', [
                        'current' => number_format($staffStats['current_count']),
                        'limit' => number_format($staffStats['staff_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($menuItemLimitExceeded)
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                    @lang('modules.menu.menuItemLimitExceeded')
                </h3>
                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    @lang('modules.menu.menuItemLimitExceededMessage', [
                        'current' => number_format($menuItemStats['current_count']),
                        'limit' => number_format($menuItemStats['menu_items_limit'])
                    ])
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<div class="grid lg:grid-cols-3">

    <div class="sm:col-span-2 p-4">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-xl dark:text-white my-2 px-4">@lang('modules.dashboard.todayStats')</h1>

        <div class="grid w-full grid-cols-1 gap-4 xl:grid-cols-2">

            @if (user_can('Show Order'))
            @livewire('dashboard.todayOrderCount')
            @endif

            @if (user_can('Show Reports'))
            @livewire('dashboard.todayEarnings')
            @endif

            @if (user_can('Show Customer'))
            @livewire('dashboard.todayCustomerCount')
            @endif

            @if (user_can('Show Reports'))
            @livewire('dashboard.averageDailyEarning')
            @endif

        </div>

        @if (user_can('Show Reports'))
        <div class="grid w-full grid-cols-1 gap-4 py-4">
            @livewire('dashboard.weeklySalesChart')
        </div>
        @endif


        @if (user_can('Show Reports'))
        <div class="grid grid-cols-1 gap-4 mb-10">
            @livewire('dashboard.todayPaymentMethodEarnings')

            @livewire('dashboard.today-menu-item-earnings')

            @livewire('dashboard.today-table-earnings')
        </div>
        @endif

    </div>

    <div class="p-4">
        @if (user_can('Show Order'))
        @livewire('dashboard.todayOrderList')
        @endif

    </div>
</div>

@endsection
