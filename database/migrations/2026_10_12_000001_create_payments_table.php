<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('class_registration_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('payment_stage', ['initial', 'settlement']);
            $table->enum('payment_type', ['full', 'dp'])->default('full');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_proof')->nullable();
            $table->text('notes')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verified_by_name')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'payment_stage', 'verification_status'], 'payments_booking_stage_status_idx');
            $table->index(['class_registration_id', 'payment_stage', 'verification_status'], 'payments_class_stage_status_idx');
        });

    }

    public function down(): void { Schema::dropIfExists('payments'); }
};
