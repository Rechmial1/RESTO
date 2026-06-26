<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use App\Models\Module;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $inventoryModule = Module::where('name', 'Inventory')->first();
        
        if ($inventoryModule) {
            $permissions = [
                ['name' => 'Create Batch Recipe', 'guard_name' => 'web', 'module_id' => $inventoryModule->id],
                ['name' => 'Show Batch Recipe', 'guard_name' => 'web', 'module_id' => $inventoryModule->id],
                ['name' => 'Update Batch Recipe', 'guard_name' => 'web', 'module_id' => $inventoryModule->id],
                ['name' => 'Delete Batch Recipe', 'guard_name' => 'web', 'module_id' => $inventoryModule->id],
                ['name' => 'Produce Batch', 'guard_name' => 'web', 'module_id' => $inventoryModule->id],
                ['name' => 'Show Batch Inventory', 'guard_name' => 'web', 'module_id' => $inventoryModule->id],
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                    $permission
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionNames = [
            'Create Batch Recipe',
            'Show Batch Recipe',
            'Update Batch Recipe',
            'Delete Batch Recipe',
            'Produce Batch',
            'Show Batch Inventory',
        ];

        Permission::whereIn('name', $permissionNames)->delete();
    }
};

