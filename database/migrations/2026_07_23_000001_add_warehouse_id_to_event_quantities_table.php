<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_quantities', function (Blueprint $table) {
            // The warehouse this quantity moves through: the raw-material /
            // semi-finished store for consumed items, or the finished-goods
            // store for produced items and side products. Lets the warehouse
            // inventory report surface event-driven movements.
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('event_pause_activity_id');
            // When the movement actually hit stock (confirmed out/in). Null
            // while the quantity is still held in process (event/emergency
            // in progress).
            $table->dateTime('confirmed_at')->nullable()->after('percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_quantities', function (Blueprint $table) {
            $table->dropColumn('warehouse_id');
        });
    }
};
