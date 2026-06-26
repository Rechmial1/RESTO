<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->boolean('force_open_after_login')->default(false);
            $table->json('force_open_roles')->nullable(); // Array of role IDs
            $table->timestamps();
            
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->unique('restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_settings');
    }
};