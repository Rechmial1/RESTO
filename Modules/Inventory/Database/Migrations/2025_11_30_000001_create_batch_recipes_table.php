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
        Schema::create('batch_recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            
            $table->string('name'); // e.g., "Masala Chai"
            $table->text('description')->nullable();
            
            // Yield information
            $table->unsignedBigInteger('yield_unit_id'); // Unit for batch output (Litre, Kg, etc.)
            $table->foreign('yield_unit_id')->references('id')->on('units')->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('default_batch_size', 16, 2)->default(1); // Default quantity to produce (e.g., 10 L)
            
            // Optional expiry settings
            $table->integer('default_expiry_days')->nullable(); // Days until batch expires
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_recipes');
    }
};

