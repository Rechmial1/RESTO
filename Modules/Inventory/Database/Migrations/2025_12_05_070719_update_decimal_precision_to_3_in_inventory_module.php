<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update inventory_items table
        if (Schema::hasTable('inventory_items')) {
            DB::statement('ALTER TABLE inventory_items MODIFY threshold_quantity DECIMAL(16, 3) DEFAULT 0');
            if (Schema::hasColumn('inventory_items', 'reorder_quantity')) {
                DB::statement('ALTER TABLE inventory_items MODIFY reorder_quantity DECIMAL(16, 3) DEFAULT 0');
            }
            if (Schema::hasColumn('inventory_items', 'unit_purchase_price')) {
                DB::statement('ALTER TABLE inventory_items MODIFY unit_purchase_price DECIMAL(16, 3) DEFAULT 0');
            }
        }

        // Update inventory_stocks table
        if (Schema::hasTable('inventory_stocks')) {
            DB::statement('ALTER TABLE inventory_stocks MODIFY quantity DECIMAL(16, 3) DEFAULT 0');
        }

        // Update inventory_movements table
        if (Schema::hasTable('inventory_movements')) {
            DB::statement('ALTER TABLE inventory_movements MODIFY quantity DECIMAL(16, 3) DEFAULT 0');
            if (Schema::hasColumn('inventory_movements', 'unit_purchase_price')) {
                DB::statement('ALTER TABLE inventory_movements MODIFY unit_purchase_price DECIMAL(16, 3) DEFAULT 0');
            }
        }

        // Update recipes table
        if (Schema::hasTable('recipes')) {
            DB::statement('ALTER TABLE recipes MODIFY quantity DECIMAL(16, 3) DEFAULT 0');
        }

        // Update purchase_orders table
        if (Schema::hasTable('purchase_orders')) {
            DB::statement('ALTER TABLE purchase_orders MODIFY total_amount DECIMAL(10, 3) DEFAULT 0');
        }

        // Update purchase_order_items table
        if (Schema::hasTable('purchase_order_items')) {
            DB::statement('ALTER TABLE purchase_order_items MODIFY quantity DECIMAL(10, 3)');
            DB::statement('ALTER TABLE purchase_order_items MODIFY received_quantity DECIMAL(10, 3) DEFAULT 0');
            DB::statement('ALTER TABLE purchase_order_items MODIFY unit_price DECIMAL(10, 3)');
            DB::statement('ALTER TABLE purchase_order_items MODIFY subtotal DECIMAL(10, 3)');
        }

        // Update purchase_order_payments table
        if (Schema::hasTable('purchase_order_payments')) {
            DB::statement('ALTER TABLE purchase_order_payments MODIFY amount DECIMAL(12, 3)');
        }

        // Update batch_recipes table
        if (Schema::hasTable('batch_recipes')) {
            DB::statement('ALTER TABLE batch_recipes MODIFY default_batch_size DECIMAL(16, 3) DEFAULT 1');
        }

        // Update batch_recipe_items table
        if (Schema::hasTable('batch_recipe_items')) {
            DB::statement('ALTER TABLE batch_recipe_items MODIFY quantity DECIMAL(16, 3)');
        }

        // Update batch_productions table
        if (Schema::hasTable('batch_productions')) {
            DB::statement('ALTER TABLE batch_productions MODIFY quantity DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_productions MODIFY total_cost DECIMAL(16, 3)');
        }

        // Update batch_stocks table
        if (Schema::hasTable('batch_stocks')) {
            DB::statement('ALTER TABLE batch_stocks MODIFY quantity DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_stocks MODIFY cost_per_unit DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_stocks MODIFY total_cost DECIMAL(16, 3)');
        }

        // Update menu_items table
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'batch_serving_size')) {
            DB::statement('ALTER TABLE menu_items MODIFY batch_serving_size DECIMAL(16, 3)');
        }

        // Update menu_item_variations table
        if (Schema::hasTable('menu_item_variations') && Schema::hasColumn('menu_item_variations', 'batch_serving_size')) {
            DB::statement('ALTER TABLE menu_item_variations MODIFY batch_serving_size DECIMAL(16, 3)');
        }

        // Update batch_consumptions table
        if (Schema::hasTable('batch_consumptions')) {
            DB::statement('ALTER TABLE batch_consumptions MODIFY quantity DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_consumptions MODIFY cost DECIMAL(16, 3)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert inventory_items table
        if (Schema::hasTable('inventory_items')) {
            DB::statement('ALTER TABLE inventory_items MODIFY threshold_quantity DECIMAL(16, 3) DEFAULT 0');
            if (Schema::hasColumn('inventory_items', 'reorder_quantity')) {
                DB::statement('ALTER TABLE inventory_items MODIFY reorder_quantity DECIMAL(16, 3) DEFAULT 0');
            }
            if (Schema::hasColumn('inventory_items', 'unit_purchase_price')) {
                DB::statement('ALTER TABLE inventory_items MODIFY unit_purchase_price DECIMAL(16, 3) DEFAULT 0');
            }
        }

        // Revert inventory_stocks table
        if (Schema::hasTable('inventory_stocks')) {
            DB::statement('ALTER TABLE inventory_stocks MODIFY quantity DECIMAL(16, 3) DEFAULT 0');
        }

        // Revert inventory_movements table
        if (Schema::hasTable('inventory_movements')) {
            DB::statement('ALTER TABLE inventory_movements MODIFY quantity DECIMAL(16, 3) DEFAULT 0');
            if (Schema::hasColumn('inventory_movements', 'unit_purchase_price')) {
                DB::statement('ALTER TABLE inventory_movements MODIFY unit_purchase_price DECIMAL(16, 3) DEFAULT 0');
            }
        }

        // Revert recipes table
        if (Schema::hasTable('recipes')) {
            DB::statement('ALTER TABLE recipes MODIFY quantity DECIMAL(16, 3) DEFAULT 0');
        }

        // Revert purchase_orders table
        if (Schema::hasTable('purchase_orders')) {
            DB::statement('ALTER TABLE purchase_orders MODIFY total_amount DECIMAL(10, 3) DEFAULT 0');
        }

        // Revert purchase_order_items table
        if (Schema::hasTable('purchase_order_items')) {
            DB::statement('ALTER TABLE purchase_order_items MODIFY quantity DECIMAL(10, 3)');
            DB::statement('ALTER TABLE purchase_order_items MODIFY received_quantity DECIMAL(10, 3) DEFAULT 0');
            DB::statement('ALTER TABLE purchase_order_items MODIFY unit_price DECIMAL(10, 3)');
            DB::statement('ALTER TABLE purchase_order_items MODIFY subtotal DECIMAL(10, 3)');
        }

        // Revert purchase_order_payments table
        if (Schema::hasTable('purchase_order_payments')) {
            DB::statement('ALTER TABLE purchase_order_payments MODIFY amount DECIMAL(12, 3)');
        }

        // Revert batch_recipes table
        if (Schema::hasTable('batch_recipes')) {
            DB::statement('ALTER TABLE batch_recipes MODIFY default_batch_size DECIMAL(16, 3) DEFAULT 1');
        }

        // Revert batch_recipe_items table
        if (Schema::hasTable('batch_recipe_items')) {
            DB::statement('ALTER TABLE batch_recipe_items MODIFY quantity DECIMAL(16, 3)');
        }

        // Revert batch_productions table
        if (Schema::hasTable('batch_productions')) {
            DB::statement('ALTER TABLE batch_productions MODIFY quantity DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_productions MODIFY total_cost DECIMAL(16, 3)');
        }

        // Revert batch_stocks table
        if (Schema::hasTable('batch_stocks')) {
            DB::statement('ALTER TABLE batch_stocks MODIFY quantity DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_stocks MODIFY cost_per_unit DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_stocks MODIFY total_cost DECIMAL(16, 3)');
        }

        // Revert menu_items table
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'batch_serving_size')) {
            DB::statement('ALTER TABLE menu_items MODIFY batch_serving_size DECIMAL(16, 3)');
        }

        // Revert menu_item_variations table
        if (Schema::hasTable('menu_item_variations') && Schema::hasColumn('menu_item_variations', 'batch_serving_size')) {
            DB::statement('ALTER TABLE menu_item_variations MODIFY batch_serving_size DECIMAL(16, 3)');
        }

        // Revert batch_consumptions table
        if (Schema::hasTable('batch_consumptions')) {
            DB::statement('ALTER TABLE batch_consumptions MODIFY quantity DECIMAL(16, 3)');
            DB::statement('ALTER TABLE batch_consumptions MODIFY cost DECIMAL(16, 3)');
        }
    }
};
