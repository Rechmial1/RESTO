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
        if (!Schema::hasColumn('payment_gateway_credentials', 'epay_status')) {
            Schema::table('payment_gateway_credentials', function (Blueprint $table) {
                $table->boolean('epay_status')->default(false);
                $table->enum('epay_mode', ['sandbox', 'live'])->default('sandbox');
                $table->string('epay_client_id')->nullable();
                $table->string('epay_client_secret')->nullable();
                $table->string('epay_terminal_id')->nullable();
                $table->string('test_epay_client_id')->nullable();
                $table->string('test_epay_client_secret')->nullable();
                $table->string('test_epay_terminal_id')->nullable();
            });
        }

        if (!Schema::hasTable('epay_payments')) {
            Schema::create('epay_payments', function (Blueprint $table) {
                $table->id();
                $table->string('epay_payment_id')->nullable();
                $table->unsignedBigInteger('order_id');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
                $table->timestamp('payment_date')->nullable();
                $table->json('payment_error_response')->nullable();
                $table->string('epay_invoice_id')->nullable();
                $table->string('epay_secret_hash')->nullable();
                $table->text('epay_access_token')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->change();
        });

        if (!Schema::hasColumn('global_settings', 'enable_epay')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->boolean('enable_epay')->default(true);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateway_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'epay_status',
                'epay_mode',
                'epay_client_id',
                'epay_client_secret',
                'epay_terminal_id',
                'test_epay_client_id',
                'test_epay_client_secret',
                'test_epay_terminal_id',
            ]);
        });

        Schema::dropIfExists('epay_payments');

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'upi', 'card', 'due', 'stripe', 'flutterwave', 'razorpay', 'paypal', 'payfast', 'paystack'])->default('cash')->change();
        });

        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn('enable_epay');
        });
    }
};
