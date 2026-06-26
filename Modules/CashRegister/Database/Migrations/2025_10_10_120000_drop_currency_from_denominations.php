<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unique index that includes currency_code, then drop column
        Schema::table('denominations', function (Blueprint $table) {
            // Safely drop unique index if exists
            try {
                $table->dropUnique('unique_denomination_per_branch');
            } catch (\Throwable $e) {
                // ignore if index not present
            }
        });

        Schema::table('denominations', function (Blueprint $table) {
            if (Schema::hasColumn('denominations', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });

        // Recreate a simpler unique index without currency_code
        Schema::table('denominations', function (Blueprint $table) {
            try {
                $table->unique(['value', 'type', 'branch_id', 'restaurant_id'], 'unique_denomination_per_branch');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    public function down(): void
    {
        // Re-add currency_code and restore original unique index
        Schema::table('denominations', function (Blueprint $table) {
            if (!Schema::hasColumn('denominations', 'currency_code')) {
                $table->string('currency_code', 3)->nullable();
            }
        });

        Schema::table('denominations', function (Blueprint $table) {
            try {
                $table->dropUnique('unique_denomination_per_branch');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::table('denominations', function (Blueprint $table) {
            try {
                $table->unique(['value', 'type', 'currency_code', 'branch_id', 'restaurant_id'], 'unique_denomination_per_branch');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};


