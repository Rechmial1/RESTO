<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_types', function (Blueprint $table) {
            if (!Schema::hasColumn('order_types', 'enable_from_customer_site')) {
                $table->boolean('enable_from_customer_site')
                    ->default(true)
                    ->after('enable_token_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_types', function (Blueprint $table) {
            if (Schema::hasColumn('order_types', 'enable_from_customer_site')) {
                $table->dropColumn('enable_from_customer_site');
            }
        });
    }

};
