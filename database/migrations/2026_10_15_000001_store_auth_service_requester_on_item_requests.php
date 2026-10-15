<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users live in the auth service, not in this app's `users` table, so the
 * requester can't be a foreign key here — the id is the auth-service user's,
 * with a name snapshot so the list renders without an API call (the same way
 * event_status_logs records changed_by / changed_by_name).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->string('requested_by_name')->nullable()->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropColumn('requested_by_name');
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
