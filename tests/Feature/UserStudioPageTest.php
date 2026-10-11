<?php

namespace Tests\Feature;

use App\Models\Studio;
use App\Models\StudioSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStudioPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_customers_can_view_active_studio_details(): void
    {
        $this->withoutVite();
        Studio::create([
            'name' => 'Studio Public', 'description' => 'Studio menengah untuk kelas dan reservasi.',
            'capacity' => 15, 'price_per_hour' => 150000, 'status' => 'active', 'image' => 'studios/public.jpg',
        ]);
        Studio::create([
            'name' => 'Studio Hidden', 'capacity' => 10, 'price_per_hour' => 100000, 'status' => 'inactive',
        ]);

        $this->get(route('user.studios.index'))->assertOk()->assertViewIs('user.studios.index')
            ->assertSee('Studio Public')->assertSee('A medium-sized studio for classes and bookings.')
            ->assertSee('15 people')->assertSee('IDR 150,000')
            ->assertSee(asset('storage/studios/public.jpg'))->assertDontSee('Studio Hidden');
        $this->actingAs(User::factory()->create())->get(route('user.studios.index'))->assertOk();
        $this->get(route('landing'))->assertSee(route('user.studios.index'));
    }

    public function test_empty_studio_page_is_available_without_login(): void
    {
        $this->withoutVite();
        $this->get(route('user.studios.index'))->assertOk()->assertSee('No studios available yet');
    }

    public function test_search_excludes_blocked_slots_and_can_filter_by_studio(): void
    {
        $this->withoutVite();
        $busy = Studio::factory()->create(['status' => 'active']);
        $free = Studio::factory()->create(['status' => 'active']);
        $date = today()->addDay()->toDateString();
        StudioSchedule::create([
            'studio_id' => $busy->id, 'date' => $date,
            'start_time' => '10:00:00', 'end_time' => '11:00:00', 'status' => 'blocked',
        ]);
        $this->get(route('user.studios.index', [
            'date' => $date, 'start_time' => '10:00', 'end_time' => '11:00',
        ]))->assertOk()->assertViewHas('studios', fn ($studios) => $studios->count() === 1 && $studios->first()->id === $free->id);
        $this->get(route('user.studios.index', ['studio_id' => $busy->id]))
            ->assertOk()->assertViewHas('studios', fn ($studios) => $studios->count() === 1 && $studios->first()->id === $busy->id);
    }

    public function test_search_requires_a_date_and_a_valid_time_range(): void
    {
        $this->get(route('user.studios.index', ['start_time' => '10:00', 'end_time' => '09:00']))
            ->assertSessionHasErrors(['date', 'end_time']);
    }
}
