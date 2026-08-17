<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preparations and lines can draw the items they consume from several
     * warehouses, and route an individual item type to a specific one. The
     * finished-goods side stays a single warehouse.
     *
     * The original scalar source columns are kept as the default warehouse
     * (always the first of the list), so everything that resolves a warehouse
     * without an item type keeps working unchanged.
     */
    public function up(): void
    {
        Schema::table('preparations', function (Blueprint $table) {
            $table->json('rm_warehouse_ids')->nullable()->after('rm_warehouse_id');
            // { "<itemTypeId>": <warehouseId> } — where that item type is consumed from.
            $table->json('item_type_warehouses')->nullable()->after('fg_warehouse_id');
        });

        Schema::table('lines', function (Blueprint $table) {
            $table->json('sfg_warehouse_ids')->nullable()->after('sfg_warehouse_id');
            $table->json('item_type_warehouses')->nullable()->after('fg_warehouse_id');
        });

        $this->seedLists('preparations', 'rm_warehouse_id', 'rm_warehouse_ids');
        $this->seedLists('lines', 'sfg_warehouse_id', 'sfg_warehouse_ids');
    }

    /**
     * Existing rows start with a one-entry list holding the warehouse they
     * already point at.
     */
    private function seedLists(string $table, string $scalar, string $list): void
    {
        DB::table($table)
            ->whereNotNull($scalar)
            ->orderBy('id')
            ->select(['id', $scalar])
            ->chunkById(200, function ($rows) use ($table, $scalar, $list) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$list => json_encode([(int) $row->{$scalar}])]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('preparations', function (Blueprint $table) {
            $table->dropColumn(['rm_warehouse_ids', 'item_type_warehouses']);
        });

        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn(['sfg_warehouse_ids', 'item_type_warehouses']);
        });
    }
};
