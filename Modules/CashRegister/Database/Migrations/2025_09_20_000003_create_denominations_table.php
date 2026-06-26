<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CashRegister\Entities\Denomination;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('denominations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->decimal('value', 10, 2);
            $table->enum('type', ['coin', 'note', 'bill']);
            // currency removed
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'restaurant_id']);
            $table->index(['type', 'is_active']);
            $table->index(['value', 'type']);
            $table->unique(['value', 'type', 'branch_id', 'restaurant_id'], 'unique_denomination_per_branch');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
        });

        $denominations = [];

        foreach ($denominations as $denomination) {
            Denomination::create($denomination);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('denominations');
    }
};


