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
        Schema::create('batch_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_recipe_id');
            $table->foreign('batch_recipe_id')->references('id')->on('batch_recipes')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('inventory_item_id'); // Raw ingredient
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade')->onUpdate('cascade');
            
            $table->decimal('quantity', 16, 2); // Quantity needed per 1 unit of batch
            $table->unsignedBigInteger('unit_id');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade')->onUpdate('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_recipe_items');
    }
};

