<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_register_sessions', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('closed_by');
            }
            if (!Schema::hasColumn('cash_register_sessions', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_register_sessions', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('cash_register_sessions', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
        });
    }
};


