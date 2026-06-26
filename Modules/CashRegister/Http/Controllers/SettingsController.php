<?php

namespace Modules\CashRegister\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CashRegister\Livewire\Settings\RegisterSettings;

class SettingsController extends Controller
{

    public function index()
    {
        return view('cashregister::settings.index');
    }
}
