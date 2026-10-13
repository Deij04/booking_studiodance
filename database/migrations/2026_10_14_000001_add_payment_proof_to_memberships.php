<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', fn (Blueprint $table) => $table->string('payment_proof')->nullable());
    }

    public function down(): void
    {
        Schema::table('memberships', fn (Blueprint $table) => $table->dropColumn('payment_proof'));
    }
};
