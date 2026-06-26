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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_recipe_id')->nullable()->after('in_stock');
            $table->foreign('batch_recipe_id')->references('id')->on('batch_recipes')->onDelete('set null')->onUpdate('cascade');
            
            $table->decimal('batch_serving_size', 16, 2)->nullable()->after('batch_recipe_id'); // e.g., 0.15 L per cup
        });
        
        Schema::table('menu_item_variations', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_recipe_id')->nullable()->after('price');
            $table->foreign('batch_recipe_id')->references('id')->on('batch_recipes')->onDelete('set null')->onUpdate('cascade');
            
            $table->decimal('batch_serving_size', 16, 2)->nullable()->after('batch_recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['batch_recipe_id']);
            $table->dropColumn(['batch_recipe_id', 'batch_serving_size']);
        });
        
        Schema::table('menu_item_variations', function (Blueprint $table) {
            $table->dropForeign(['batch_recipe_id']);
            $table->dropColumn(['batch_recipe_id', 'batch_serving_size']);
        });
    }
};

