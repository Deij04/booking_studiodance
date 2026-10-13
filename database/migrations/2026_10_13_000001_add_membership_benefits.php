<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->foreignId('studio_id')->nullable()->constrained('studios')->restrictOnDelete();
            $table->unsignedInteger('quota_hours')->nullable();
            $table->boolean('free_classes')->default(true);
        });
        Schema::table('memberships', function (Blueprint $table) {
            $table->foreignId('studio_id')->nullable()->constrained('studios')->restrictOnDelete();
            $table->unsignedInteger('quota_minutes')->default(0);
            $table->boolean('free_classes')->default(true);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verified_by_name')->nullable();
            $table->timestamp('verified_at')->nullable();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->restrictOnDelete();
            $table->unsignedInteger('membership_minutes')->default(0);
        });
        Schema::table('class_registrations', function (Blueprint $table) {
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->restrictOnDelete();
        });
    }
    public function down(): void {
        Schema::table('class_registrations', function (Blueprint $table) { $table->dropConstrainedForeignId('membership_id'); });
        Schema::table('bookings', function (Blueprint $table) { $table->dropConstrainedForeignId('membership_id'); $table->dropColumn('membership_minutes'); });
        Schema::table('memberships', function (Blueprint $table) { $table->dropConstrainedForeignId('studio_id'); $table->dropConstrainedForeignId('verified_by'); $table->dropColumn(['quota_minutes', 'free_classes', 'verified_by_name', 'verified_at']); });
        Schema::table('membership_plans', function (Blueprint $table) { $table->dropConstrainedForeignId('studio_id'); $table->dropColumn(['quota_hours', 'free_classes']); });
    }
};
