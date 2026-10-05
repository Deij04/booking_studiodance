<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();
        });
        Schema::table('studio_schedules', function (Blueprint $table) {
            $table->string('block_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('studio_schedules', fn (Blueprint $table) => $table->dropColumn('block_reason'));
        Schema::dropIfExists('booking_settings');
    }
};
