<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('code')->unique();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'completed',
            ])->default('pending');
            $table->text('notes')->nullable();
            // The auth-service user who asked, with a name snapshot.
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->string('requested_by_name')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->uuid('submission_key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_requests');
    }
};
