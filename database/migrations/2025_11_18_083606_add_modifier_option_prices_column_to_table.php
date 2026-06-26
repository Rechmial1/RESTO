<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Drop existing foreign key
            Schema::table('order_item_modifier_options', function (Blueprint $table) {
                $table->dropForeign(['modifier_option_id']);
            });

            Schema::table('order_item_modifier_options', function (Blueprint $table) {
                // Make modifier_option_id nullable
                $table->unsignedBigInteger('modifier_option_id')->nullable()->change();

                $table->text('modifier_option_name')->nullable()->after('modifier_option_id');
                $table->decimal('modifier_option_price', 10, 2)->nullable()->after('modifier_option_name');

                $table->foreign('modifier_option_id')->references('id')->on('modifier_options')->onUpdate('cascade')->onDelete('SET NULL');
            });
        } catch (\Exception $e) {
            Log::error('Error migrating modifier option prices: ' . $e->getMessage());
        }

        // Migrate existing data to historical columns using Eloquent
        $this->migrateExistingModifierData();
    }

    /**
     * Migrate existing modifier option data to historical columns
     * Uses the same logic as OrderItemModifierOption::creating event
     */
    private function migrateExistingModifierData(): void
    {
        // Process in chunks to avoid memory issues on large datasets
        DB::table('order_item_modifier_options as oimo')
            ->join('modifier_options as mo', 'oimo.modifier_option_id', '=', 'mo.id')
            ->join('order_items as oi', 'oimo.order_item_id', '=', 'oi.id')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->leftJoin('modifier_option_prices as mop', function ($join) {
                $join->on('mop.modifier_option_id', '=', 'mo.id')
                    ->on('mop.order_type_id', '=', 'o.order_type_id')
                    ->where('mop.status', '=', 1)
                    ->where(function ($query) {
                        // Match delivery app context: both null OR both match
                        $query->whereRaw('(o.delivery_app_id IS NULL AND mop.delivery_app_id IS NULL)')
                            ->orWhereRaw('(o.delivery_app_id IS NOT NULL AND mop.delivery_app_id = o.delivery_app_id)');
                    });
            })
            ->whereNull('oimo.modifier_option_name')
            ->whereNotNull('oimo.modifier_option_id')
            ->select([
                'oimo.id',
                'mo.name',
                DB::raw('COALESCE(mop.final_price, mo.price) as contextual_price')
            ])
            ->orderBy('oimo.id')
            ->chunk(500, function ($records) {
                $updates = [];

                foreach ($records as $record) {
                    $updates[] = [
                        'id' => $record->id,
                        'modifier_option_name' => $record->name,
                        'modifier_option_price' => $record->contextual_price ?? 0,
                    ];
                }

                // Bulk update using case statements for better performance
                if (!empty($updates)) {
                    $this->bulkUpdateModifierOptions($updates);
                }
            });
    }

    /**
     * Perform bulk update using CASE statements for optimal performance
     */
    private function bulkUpdateModifierOptions(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        $ids = array_column($updates, 'id');
        $nameCase = 'CASE id ';
        $priceCase = 'CASE id ';

        foreach ($updates as $update) {
            $name = DB::connection()->getPdo()->quote($update['modifier_option_name']);
            $price = (float) $update['modifier_option_price'];

            $nameCase .= "WHEN {$update['id']} THEN {$name} ";
            $priceCase .= "WHEN {$update['id']} THEN {$price} ";
        }

        $nameCase .= 'END';
        $priceCase .= 'END';
        $idList = implode(',', $ids);

        DB::statement("
            UPDATE order_item_modifier_options
            SET modifier_option_name = {$nameCase},
                modifier_option_price = {$priceCase}
            WHERE id IN ({$idList})
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key first
        Schema::table('order_item_modifier_options', function (Blueprint $table) {
            $table->dropForeign(['modifier_option_id']);
        });

        // Revert changes
        Schema::table('order_item_modifier_options', function (Blueprint $table) {
            $table->unsignedBigInteger('modifier_option_id')->nullable(false)->change();

            $table->dropColumn(['modifier_option_name', 'modifier_option_price']);

            // Restore original foreign key with cascade
            $table->foreign('modifier_option_id')->references('id')->on('modifier_options')->onUpdate('cascade')->onDelete('cascade');
        });
    }
};
