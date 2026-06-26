<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CashRegister\Entities\CashRegisterGlobalSetting;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Module::validateVersion(CashRegisterGlobalSetting::MODULE_NAME);

        if (!Schema::hasTable('cash_register_global_settings')) {
            Schema::create('cash_register_global_settings', function (Blueprint $table) {
                $table->id();
                $table->string('purchase_code')->nullable();
                $table->timestamp('supported_until')->nullable();
                $table->timestamp('purchased_on')->nullable();
                $table->string('license_type', 20)->nullable();
                $table->boolean('notify_update')->default(1);
                $table->timestamps();
            });

            CashRegisterGlobalSetting::create([]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_register_global_settings');
    }
};
