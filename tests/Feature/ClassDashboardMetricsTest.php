<?php

namespace Tests\Feature;

use App\Models\ClassRegistration;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Services\BookingDashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_chart_uses_session_date_and_verified_reservations(): void
    {
        $this->withoutVite();
        $schedule = ClassSchedule::factory()->create(['date' => '2026-10-15']);
        foreach (['confirmed', 'completed', 'pending', 'cancelled'] as $status) {
            ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'registration_date' => '2025-01-01', 'registration_status' => $status, 'payment_status' => 'paid', 'price' => 100000]);
        }
        ClassRegistration::factory()->create(['class_schedule_id' => $schedule->id, 'registration_status' => 'confirmed', 'payment_status' => 'unpaid']);
        $metrics = app(BookingDashboardMetrics::class)->get(['period' => 'month', 'year' => 2026], true);
        $this->assertCount(12, $metrics['charts']['month']['points']);
        $this->assertSame(2, $metrics['charts']['month']['total']);
        $this->assertSame(2, $metrics['charts']['month']['points'][9]['count']);
        $this->assertSame(400000.0, $metrics['revenue']);
        $this->assertSame(0, app(BookingDashboardMetrics::class)->get(['year' => 2025], true)['charts']['month']['total']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard', ['period' => 'month', 'year' => 2026]))
            ->assertOk()->assertSee('Grafik Reservasi Class')->assertSee('Total Class')->assertSee('Pengajuan Class')->assertSee('Class Aktif')->assertSee('Riwayat Class')
            ->assertDontSee('Akumulasi Pendapatan Class')->assertDontSee('Total Akumulasi Pendapatan')
            ->assertViewHas('classMetrics', fn ($data) => $data['charts']['month']['total'] === 2);
        \App\Models\Membership::factory()->create(['payment_status' => 'paid', 'price' => 150000]);
        \App\Models\Membership::factory()->create(['payment_status' => 'unpaid', 'price' => 200000]);
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Akumulasi Pendapatan Class')->assertSee('Total Akumulasi Pendapatan')->assertSee('Rp 550.000')
            ->assertViewHas('classReservationCount', 5)->assertViewHas('classPendingCount', 2)
            ->assertViewHas('classActiveCount', 2)->assertViewHas('classHistoryCount', 2);
    }
}
