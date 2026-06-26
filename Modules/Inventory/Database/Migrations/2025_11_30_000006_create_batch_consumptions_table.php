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
        Schema::create('batch_consumptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('batch_stock_id');
            $table->foreign('batch_stock_id')->references('id')->on('batch_stocks')->onDelete('cascade')->onUpdate('cascade');
            
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null')->onUpdate('cascade');
            
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null')->onUpdate('cascade');
            
            $table->unsignedBigInteger('kot_item_id')->nullable();
            $table->foreign('kot_item_id')->references('id')->on('kot_items')->onDelete('set null')->onUpdate('cascade');
            
            $table->decimal('quantity', 16, 2); // Quantity consumed
            $table->decimal('cost', 16, 2); // Cost allocated to this consumption
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_consumptions');
    }
};

