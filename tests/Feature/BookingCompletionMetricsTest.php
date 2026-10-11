<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingCompletion;
use App\Services\BookingDashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCompletionMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $attributes = [], string $end = '11:00:00'): Booking
    {
        $studio = \App\Models\Studio::factory()->create(['name' => 'Studio '.\Illuminate\Support\Str::random(8)]);
        $record = Booking::factory()->create($attributes + ['studio_id' => $studio->id] + ['status' => 'confirmed', 'payment_status' => 'paid', 'payment_type' => 'full', 'total_price' => 300000]);
        $record->update(['date' => today()->toDateString(), 'start_time' => '09:00:00', 'end_time' => $end]);
        return $record;
    }

    public function test_only_elapsed_and_fully_verified_bookings_complete(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 12:00:00', 'Asia/Makassar'));
        $full = $this->booking();
        $dp = $this->booking(['payment_type' => 'dp']);
        $unverified = $this->booking(['payment_status' => 'unpaid']);
        $future = $this->booking([], '14:00:00');
        $paidDp = $this->booking(['payment_type' => 'dp']);
        $paidDp->settlement()->create(['amount' => 150000, 'payment_method' => 'cash', 'payment_proof' => '', 'verification_status' => 'verified', 'payment_stage' => 'settlement']);
        $cancelled = $this->booking(['status' => 'cancelled']);
        $this->assertSame(2, app(BookingCompletion::class)->run());
        foreach ([$full, $paidDp] as $booking) $this->assertSame('completed', $booking->fresh()->status);
        foreach ([$dp, $unverified, $future] as $booking) $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertSame(0, app(BookingCompletion::class)->run());
    }

    public function test_admin_settlement_before_end_stays_active_and_after_end_completes(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 12:00:00', 'Asia/Makassar'));
        $this->actingAs(User::factory()->admin()->create());
        $future = $this->booking(['payment_type' => 'dp'], '14:00:00');
        $past = $this->booking(['payment_type' => 'dp']);
        $this->post(route('admin.bookings.settle', $future), ['payment_method' => 'cash'])->assertRedirect(route('admin.bookings.index', ['tab' => 'active']));
        $this->assertSame('confirmed', $future->fresh()->status);
        $this->post(route('admin.bookings.settle', $past), ['payment_method' => 'cash'])->assertRedirect(route('admin.bookings.index', ['tab' => 'history']));
        $this->assertSame('completed', $past->fresh()->status);
        $this->travelTo(now()->setTime(14, 0));
        $this->get(route('admin.bookings.index', ['tab' => 'history']))->assertOk()->assertSee($future->booking_code);
        $this->assertSame('completed', $future->fresh()->status);
    }

    public function test_accepting_elapsed_booking_completes_full_but_keeps_dp_active(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 12:00:00', 'Asia/Makassar'));
        $this->actingAs(User::factory()->admin()->create());
        $full = $this->booking(['status' => 'pending', 'payment_status' => 'unpaid']);
        $dp = $this->booking(['status' => 'pending', 'payment_status' => 'unpaid', 'payment_type' => 'dp']);
        foreach ([$full, $dp] as $record) {
            $this->patch(route('admin.bookings.decide', $record), ['decision' => 'accept'])->assertRedirect();
        }
        $this->assertSame('completed', $full->fresh()->status);
        $this->assertSame('confirmed', $dp->fresh()->status);
    }

    public function test_chart_date_range_month_and_year_filters_use_same_bookings(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 12:00:00', 'Asia/Makassar'));
        $first = $this->booking();
        $second = $this->booking();
        $second->update(['date' => '2026-11-02']);
        $old = $this->booking();
        $old->update(['date' => '2025-10-11']);
        $metrics = app(BookingDashboardMetrics::class)->get(['from' => '2026-10-10', 'to' => '2026-11-03']);
        foreach ($metrics['charts'] as $chart) {
            $this->assertSame(2, $chart['total']);
            $this->assertSame(2, array_sum(array_column($chart['points'], 'count')));
        }
        $this->assertSame(['Oktober 2026', 'November 2026'], array_column($metrics['charts']['month']['points'], 'label'));
        $this->assertSame(1, app(BookingDashboardMetrics::class)->get(['month' => 10, 'year' => 2025])['charts']['month']['total']);
        $this->assertSame(2, app(BookingDashboardMetrics::class)->get(['year' => 2026])['charts']['month']['total']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard', ['from' => '2026-10-10', 'to' => '2026-11-03']))->assertOk()
            ->assertSee('Oktober 2026')->assertSee('November 2026')->assertSee('data-chart-count="1"', false);
        $this->get(route('admin.dashboard', ['from' => '2026-11-03', 'to' => '2026-10-10']))->assertSessionHasErrors('to');
    }

    public function test_cancelled_dp_is_revenue_and_not_chart_rental(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 12:00:00', 'Asia/Makassar'));
        $this->booking(['status' => 'cancelled', 'payment_type' => 'dp']);
        $this->booking(['status' => 'pending', 'payment_status' => 'unpaid']);
        $full = $this->booking();
        $dp = $this->booking(['payment_type' => 'dp']);
        $dp->settlement()->create(['amount' => 150000, 'payment_method' => 'cash', 'payment_proof' => '', 'verification_status' => 'pending', 'payment_stage' => 'settlement']);
        $metrics = app(BookingDashboardMetrics::class)->get();
        $this->assertSame(600000.0, $metrics['revenue']);
        foreach ($metrics['charts'] as $chart) $this->assertSame(2, $chart['total']);
        $dp->settlement->update(['verification_status' => 'verified', 'payment_stage' => 'settlement']);
        $this->assertSame(750000.0, app(BookingDashboardMetrics::class)->get()['revenue']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Rp 750.000')->assertSee('Grafik Penyewaan Studio')->assertSee('Mingguan')->assertSee('Bulanan')->assertDontSee('Tahunan');
    }
}
