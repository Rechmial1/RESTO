<?php

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Entities\InventoryItem;

class ReportController extends Controller
{
    public function usage(Request $request)
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);
        
        return view('inventory::reports.usage');
    }

    public function turnover()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.turnover');
    }

    public function forecasting()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.forecasting');
    }

    public function cogs()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.cogs');
    }

    public function profitAndLoss()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);

        return view('inventory::reports.profit-and-loss');
    }

    public function purchaseOrders()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);

        return view('inventory::reports.purchase-orders');
    }

    public function batchProduction()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);

        return view('inventory::batch-reports.production');
    }

    public function batchConsumption()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);

        return view('inventory::batch-reports.consumption');
    }

    public function batchExpectedVsActual()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);

        return view('inventory::batch-reports.expected-actual');
    }

    public function batchWasteExpiry()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);

        return view('inventory::batch-reports.waste-expiry');
    }

    public function batchCogs()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Inventory Report'), 403);

        return view('inventory::batch-reports.cogs');
    }

} 