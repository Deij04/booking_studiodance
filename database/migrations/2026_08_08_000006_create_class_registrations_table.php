<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
            $table->date('registration_date');
            $table->decimal('price', 12, 2);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('registration_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->timestamps();

            $table->unique(['user_id', 'class_schedule_id']);
            $table->index(['class_schedule_id', 'registration_status', 'payment_status'], 'cr_schedule_status_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_registrations');
    }
};
