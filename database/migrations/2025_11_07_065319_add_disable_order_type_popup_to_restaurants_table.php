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
            $table->boolean('disable_order_type_popup')->default(false)->after('show_order_type_options');
            $table->unsignedBigInteger('default_order_type_id')->nullable()->after('disable_order_type_popup');
            $table->foreign('default_order_type_id')->references('id')->on('order_types')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropForeign(['default_order_type_id']);
            $table->dropColumn(['disable_order_type_popup', 'default_order_type_id']);
        });
    }
};
