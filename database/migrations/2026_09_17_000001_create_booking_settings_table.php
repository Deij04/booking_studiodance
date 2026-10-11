<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('kind', ['operating_hours', 'block'])->default('block');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->foreignId('studio_id')->nullable()->constrained('studios')->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['blocked', 'available'])->default('blocked');
            $table->string('block_reason', 500)->nullable();
            $table->index(['studio_id', 'kind', 'status', 'date']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_settings');
    }
};
