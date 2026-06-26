<?php

namespace App\Http\Controllers;

use App\Models\Order;

class PosController extends Controller
{

    public function posvue()
    {
        return view('pos.posvue');
    }

    public function ordervue($id)
    {
        return view('pos.ordervue', compact('id'));
    }

    public function kotvue($id)
    {
        return view('pos.posvue', compact('id'));
    }

    public function index()
    {
        abort_if((!in_array('Order', restaurant_modules()) || !user_can('Create Order')), 403);
        return view('pos.index');
    }

    public function show($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;
        return view('pos.show', compact('tableOrderID'));
    }

    public function order($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $tableOrderID = $id;
        return view('pos.order', compact('tableOrderID'));
    }

    public function kot($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $orderID = $id;
        $order = Order::find($orderID);

        $showOrderDetail = request()->get('show-order-detail') == 'true' ? true : false;
        return view('pos.kot', compact('orderID', 'showOrderDetail'));
    }

    public function draft($id)
    {
        abort_if((!in_array('Order', restaurant_modules())), 403);
        $orderID = $id;
        
        $order = Order::find($orderID);

        if (!$order || $order->status !== 'draft') {
            return redirect()->route('pos.index');
        }

        return view('pos.draft', compact('orderID'));
    }

    public function customerDisplay()
    {
        abort_if((!in_array('Customer Display', restaurant_modules())), 403);
        return view('pos.customer-display');
    }

    public function customerOrderBoard()
    {
        abort_if((!in_array('Customer Display', restaurant_modules())), 403);
        return view('pos.customer-order-board');
    }
}
