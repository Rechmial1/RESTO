<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->integer('menu_items_limit')->default(-1)->after('branch_limit');
            $table->integer('order_limit')->default(-1)->after('menu_items_limit');
            $table->integer('staff_limit')->default(-1)->after('order_limit');
        });

        if (!Schema::hasColumn('branches', 'count_orders') && !Schema::hasColumn('branches', 'total_orders')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->integer('count_orders')->default(0);
                $table->integer('total_orders')->default(-1);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['menu_items_limit', 'order_limit', 'staff_limit']);
        });

        if (Schema::hasColumn('branches', 'count_orders') && Schema::hasColumn('branches', 'total_orders')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn(['count_orders', 'total_orders']);
            });
        }
    }
};
