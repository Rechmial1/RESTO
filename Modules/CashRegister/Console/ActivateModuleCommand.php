<?php

namespace Modules\CashRegister\Console;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ActivateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cash-register:activate';

    /**
     * The console command description.
     */
    protected $description = 'Activate cash register module.';

    /**
     * Execute the console command.
     */
    public function handle() {
        try {
            Artisan::call('module:migrate CashRegister');
        } catch (\Exception $e) {
            // Silent exception
        }

        $restaurant = Restaurant::with('branches')->get();

        // foreach ($restaurant as $restaurant) {

        //     foreach ($restaurant->branches as $branch) {
        //         $kotPlace = $branch->kotPlaces()->first();
        //         MenuItem::whereNull('kot_place_id')
        //             ->whereNull('kot_place_id')
        //             ->update(['kot_place_id' => $kotPlace->id]);
        //     }

        // }
    }

}
