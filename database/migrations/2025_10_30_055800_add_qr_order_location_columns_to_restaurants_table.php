<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('restrict_qr_order_by_location')->default(false)->after('auto_confirm_orders');
            $table->unsignedInteger('qr_order_radius_meters')->nullable()->after('restrict_qr_order_by_location');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['restrict_qr_order_by_location', 'qr_order_radius_meters']);
        });
    }
};


