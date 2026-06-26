<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename permissions
        $permissionRenames = [
            'Manage Cash Register' => 'Manage Cash Register Settings',
            'Manage Denominations' => 'Manage Cash Denominations',
            'Open Register' => 'Open Cash Register',
        ];

        foreach ($permissionRenames as $oldName => $newName) {
            $permission = Permission::where('name', $oldName)->first();
            if ($permission) {
                $permission->update(['name' => $newName]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert permission names
        $permissionRenames = [
            'Manage Cash Register Settings' => 'Manage Cash Register',
            'Manage Cash Denominations' => 'Manage Denominations',
            'Open Cash Register' => 'Open Register',
        ];

        foreach ($permissionRenames as $oldName => $newName) {
            $permission = Permission::where('name', $oldName)->first();
            if ($permission) {
                $permission->update(['name' => $newName]);
            }
        }
    }
};
