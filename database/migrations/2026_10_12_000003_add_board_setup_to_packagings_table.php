<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same warehouse shape as preparations / lines (see RoutesWarehouses):
        // a list of source warehouses with the first kept in the scalar column
        // as the default, a per-item-type override map, and one finished-goods
        // warehouse. Nullable so packagings created before this keep loading —
        // the form requires them on the next save.
        Schema::table('packagings', function (Blueprint $table) {
            $table->unsignedBigInteger('source_warehouse_id')->nullable()->after('factory_id');
            $table->json('source_warehouse_ids')->nullable()->after('source_warehouse_id');
            $table->json('item_type_warehouses')->nullable()->after('source_warehouse_ids');
            $table->unsignedBigInteger('fg_warehouse_id')->nullable()->after('item_type_warehouses');
        });

        Schema::create('event_type_packaging', function (Blueprint $table) {
            $table->foreignId('event_type_id')->constrained('event_types')->cascadeOnDelete();
            $table->foreignId('packaging_id')->constrained('packagings')->cascadeOnDelete();
            $table->primary(['event_type_id', 'packaging_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_type_packaging');

        Schema::table('packagings', function (Blueprint $table) {
            $table->dropColumn(['source_warehouse_id', 'source_warehouse_ids', 'item_type_warehouses', 'fg_warehouse_id']);
        });
    }
};
