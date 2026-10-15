<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The key of the form that created the row (PreventsDuplicateSubmission).
 *
 * - item_requests: one request per form → unique, like the stock documents.
 * - events: one save of the add-event popup creates several events (and their
 *   companions), all stamped with the same key → indexed, not unique; the popup
 *   checks for the key before it saves.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            $table->uuid('submission_key')->nullable()->unique();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->uuid('submission_key')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropUnique('item_requests_submission_key_unique');
            $table->dropColumn('submission_key');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_submission_key_index');
            $table->dropColumn('submission_key');
        });
    }
};
