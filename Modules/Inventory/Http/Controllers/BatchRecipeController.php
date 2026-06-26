<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

class BatchRecipeController extends Controller
{
    /**
     * Display a listing of batch recipes.
     */
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Batch Recipe'), 403);
        return view('inventory::batch-recipes.index');
    }

    /**
     * Display batch inventory.
     */
    public function inventory()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Batch Inventory'), 403);
        return view('inventory::batch-recipes.inventory');
    }
}

