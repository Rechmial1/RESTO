<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('inventory_items', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('preferred_supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'photo_path')) {
                $table->dropColumn('photo_path');
            }

            if (Schema::hasColumn('inventory_items', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

