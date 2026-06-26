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
        Schema::create('batch_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('batch_recipe_id');
            $table->foreign('batch_recipe_id')->references('id')->on('batch_recipes')->onDelete('cascade')->onUpdate('cascade');
            
            $table->decimal('quantity', 16, 2); // Quantity produced
            $table->decimal('total_cost', 16, 2); // Total cost of raw ingredients used
            
            $table->unsignedBigInteger('produced_by')->nullable();
            $table->foreign('produced_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_productions');
    }
};

