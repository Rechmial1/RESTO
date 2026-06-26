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
        Schema::create('batch_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('batch_recipe_id');
            $table->foreign('batch_recipe_id')->references('id')->on('batch_recipes')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('batch_production_id'); // Link to production record
            $table->foreign('batch_production_id')->references('id')->on('batch_productions')->onDelete('cascade')->onUpdate('cascade');
            
            $table->decimal('quantity', 16, 2); // Available quantity
            $table->decimal('cost_per_unit', 16, 2); // Cost per unit of batch
            $table->decimal('total_cost', 16, 2); // Total cost of this batch
            
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['active', 'expired', 'finished'])->default('active');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_stocks');
    }
};

