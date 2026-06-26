<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LanguageSetting;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        if (!Schema::hasColumn('printers', 'print_type')) {
            Schema::table('printers', function (Blueprint $table) {
                $table->enum('print_type', ['image', 'pdf'])->default('image')->after('printing_choice');
            });
        }
    }
};
