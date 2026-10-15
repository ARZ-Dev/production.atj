<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One key per opened create form (PreventsDuplicateSubmission). Unique, so the
 * same form can never create two documents however often Submit is clicked;
 * null for documents not made through a form (the auto-created leftover Stock In
 * / Waste) and for everything created before this column existed.
 */
return new class extends Migration {
    private const TABLES = ['stock_ins', 'stock_outs', 'wastes', 'transfers'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('submission_key')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("{$table}_submission_key_unique");
                $t->dropColumn('submission_key');
            });
        }
    }
};
