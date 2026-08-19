<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            // Internal transfers move stock between two internal warehouses;
            // otherwise stock leaves an internal warehouse for an external one.
            $table->boolean('is_internal')->default(false)->after('item_request_id');
            // Departments live in the parent service, so no FK constraint here.
            $table->unsignedBigInteger('department_id')->nullable()->after('is_internal');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['is_internal', 'department_id']);
        });
    }
};
