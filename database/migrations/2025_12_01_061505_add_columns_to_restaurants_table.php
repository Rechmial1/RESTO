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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('wifi_name')->nullable()->after('yelp_link');
            $table->string('wifi_password')->nullable()->after('wifi_name');
            $table->boolean('show_wifi_icon')->default(false)->after('wifi_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['wifi_name', 'wifi_password', 'show_wifi_icon']);
        });
    }
};
