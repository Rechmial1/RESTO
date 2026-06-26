<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Restaurant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cashRegisterModule = Module::firstOrCreate(['name' => 'Cash Register']);

        $permissions = [
            'Manage Cash Register',
            'View Cash Register Reports',
            'Manage Denominations',
            'Approve Cash Register',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate([
                'guard_name' => 'web',
                'name' => $name,
                'module_id' => $cashRegisterModule->id,
            ]);

            Restaurant::chunk(50, function ($restaurants) use ($permission) {
                foreach ($restaurants as $restaurant) {
                    $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
                    $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

                    if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
                        $adminRole->givePermissionTo($permission);
                    }
                    if ($branchHeadRole && !$branchHeadRole->hasPermissionTo($permission)) {
                        $branchHeadRole->givePermissionTo($permission);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $cashRegisterModule = Module::where('name', 'Cash Register')->first();

        if ($cashRegisterModule) {
            $permissions = Permission::where('module_id', $cashRegisterModule->id)->delete();
        }
    }
};
