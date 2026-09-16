<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * End-of-event reconciliation for events whose consumed quantities can't
     * be verified at start (event_types.start_items_unverifiable).
     *
     * `actual_quantity` keeps meaning "what was taken for the event" — it is
     * recorded at start and never rewritten. These columns record what was
     * really consumed, what came back, and where the leftover went.
     */
    public function up(): void
    {
        Schema::table('event_quantities', function (Blueprint $table) {
            $table->decimal('actual_used_quantity', 10, 4)->nullable()->after('actual_quantity');
            $table->decimal('remaining_quantity', 10, 4)->nullable()->after('actual_used_quantity');
            // 'stock_in' (booked back into the warehouse) or 'waste' (written off).
            $table->string('remaining_action')->nullable()->after('remaining_quantity');
            // The Stock In / Waste document the leftover was filed on. The index
            // is named explicitly: the generated name is over MySQL's 64 characters.
            $table->nullableMorphs('remaining_document', 'eq_remaining_document_index');
        });
    }

    public function down(): void
    {
        Schema::table('event_quantities', function (Blueprint $table) {
            $table->dropMorphs('remaining_document', 'eq_remaining_document_index');
            $table->dropColumn(['actual_used_quantity', 'remaining_quantity', 'remaining_action']);
        });
    }
};
