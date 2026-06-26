<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_register_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('opened_by');
            $table->dateTime('opened_at');
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('counted_cash', 12, 2)->default(0);
            $table->decimal('discrepancy', 12, 2)->default(0);
            $table->string('status')->default('open');
            $table->text('closing_note')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_register_session_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');
            $table->dateTime('happened_at');
            $table->string('type');
            $table->string('reference')->nullable();
            $table->string('reason')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('running_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('cash_denominations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->integer('value');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cash_register_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_register_session_id');
            $table->unsignedBigInteger('cash_denomination_id');
            $table->integer('count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cash_register_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_register_session_id');
            $table->unsignedBigInteger('approved_by');
            $table->dateTime('approved_at');
            $table->text('manager_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_approvals');
        Schema::dropIfExists('cash_register_counts');
        Schema::dropIfExists('cash_denominations');
        Schema::dropIfExists('cash_register_transactions');
        Schema::dropIfExists('cash_register_sessions');
        Schema::dropIfExists('cash_registers');
    }
};


