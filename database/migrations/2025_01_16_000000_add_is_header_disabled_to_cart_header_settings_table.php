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
        Schema::table('cart_header_settings', function (Blueprint $table) {
            $table->boolean('is_header_disabled')->default(false)->after('header_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_header_settings', function (Blueprint $table) {
            $table->dropColumn('is_header_disabled');
        });
    }
};

