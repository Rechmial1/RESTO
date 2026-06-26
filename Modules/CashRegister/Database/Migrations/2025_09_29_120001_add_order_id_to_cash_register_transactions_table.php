<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            Schema::table('cash_register_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('cash_register_transactions', 'order_id')) {
                    $table->unsignedBigInteger('order_id')->nullable()->after('branch_id');
                    $table->index('order_id');
                }
                if (!Schema::hasColumn('cash_register_transactions', 'currency_code')) {
                    $table->string('currency_code')->nullable()->after('amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            Schema::table('cash_register_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('cash_register_transactions', 'order_id')) {
                    $table->dropIndex(['order_id']);
                    $table->dropColumn('order_id');
                }
                if (Schema::hasColumn('cash_register_transactions', 'currency_code')) {
                    $table->dropColumn('currency_code');
                }
            });
        }
    }
};


