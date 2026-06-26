<?php

namespace Modules\CashRegister\Console\Commands;

use Illuminate\Console\Command;
use Modules\CashRegister\Database\Seeders\CashRegisterDatabaseSeeder;

class SeedCashRegisterDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashregister:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Cash Register module with dummy data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding Cash Register data...');
        
        $seeder = new CashRegisterDatabaseSeeder();
        $seeder->run();
        
        $this->info('Cash Register data seeded successfully!');
        
        return 0;
    }
}
