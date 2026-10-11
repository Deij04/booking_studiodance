<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservation_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('class_registration_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('payment_proof');
            $table->string('status', 20)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verified_by_name')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reservation_settlements'); }
};
