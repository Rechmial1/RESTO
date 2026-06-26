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
        $cashRegisterModule = Module::where('name', 'Cash Register')->first();

        // Create the Open Register permission
        $permission = Permission::firstOrCreate([
            'guard_name' => 'web',
            'name' => 'Open Register',
            'module_id' => $cashRegisterModule->id,
        ]);

        // Assign to existing roles (Admin, Branch Head) for all restaurants
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'Open Register')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
