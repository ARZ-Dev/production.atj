<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_request_id')->nullable()->constrained()->cascadeOnDelete();
            // Internal: the source must be an internal warehouse.
            $table->boolean('is_internal')->default(false);
            // Departments live in the parent service, so no FK constraint here.
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('warehouse_from_id');
            $table->unsignedBigInteger('warehouse_to_id');
            $table->string('status')->default('pending');
            // The create form's key: one form makes one document.
            $table->uuid('submission_key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
