<?php

namespace Modules\CashRegister\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class CashRegisterPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the module exists
        $module = Module::where('name', 'Cash Register')->first();

        if (!$module) {
            // Create module entry if missing to satisfy foreign key
            $module = Module::create([
                'name' => 'Cash Register',
                'description' => 'Cash drawer and register management',
                'is_default' => 1,
            ]);
        }

        // Create permission if not exists
        $permission = Permission::firstOrCreate(
            ['name' => 'Open Register', 'guard_name' => 'web'],
            ['module_id' => $module->id]
        );

        // Assign to default high-privilege roles (Admin, Branch Head) for all restaurants
        $defaultRoleDisplayNames = ['Admin', 'Branch Head'];
        $roles = Role::whereIn('display_name', $defaultRoleDisplayNames)->get();

        foreach ($roles as $role) {
            // Use permission id or model; spatie handles duplicates
            $role->givePermissionTo($permission);
        }
    }
}
