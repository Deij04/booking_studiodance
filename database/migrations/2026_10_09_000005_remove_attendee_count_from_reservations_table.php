<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases may still contain this removed field.
        if (Schema::hasColumn('reservations', 'attendee_count')) {
            Schema::table('reservations', fn (Blueprint $table) => $table->dropColumn('attendee_count'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reservations', 'attendee_count')) {
            Schema::table('reservations', fn (Blueprint $table) => $table->unsignedInteger('attendee_count')->nullable());
        }
    }
};
