<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('payment_method', 30);
            $table->string('payment_type', 20)->default('full');
            $table->string('payment_proof')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verified_by_name')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_payments');
    }
};
