<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('studio_schedule_id')->constrained('studio_schedules')->cascadeOnDelete();
            $table->string('purpose')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('studio_schedule_id');
            $table->index(['user_id', 'status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
