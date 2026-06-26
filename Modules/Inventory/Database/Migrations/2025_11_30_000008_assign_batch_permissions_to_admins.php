<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Assign all batch-related permissions to every Admin role
     * (roles with display_name = 'Admin' for each restaurant).
     */
    public function up(): void
    {
        // List of batch-related permission names
        $permissionNames = [
            'Create Batch Recipe',
            'Show Batch Recipe',
            'Update Batch Recipe',
            'Delete Batch Recipe',
            'Produce Batch',
            'Show Batch Inventory',
        ];

        // Fetch the permissions (ignore missing ones gracefully)
        $permissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        if ($permissions->isEmpty()) {
            return;
        }

        // Find all admin roles for existing restaurants
        $adminRoles = Role::where('display_name', 'Admin')
            ->where('guard_name', 'web')
            ->get();

        foreach ($adminRoles as $role) {
            foreach ($permissions as $permission) {
                // Use givePermissionTo so we don't wipe existing permissions
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Optionally remove these permissions from Admin roles.
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

        $permissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        if ($permissions->isEmpty()) {
            return;
        }

        $adminRoles = Role::where('display_name', 'Admin')
            ->where('guard_name', 'web')
            ->get();

        foreach ($adminRoles as $role) {
            foreach ($permissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }
    }
};


