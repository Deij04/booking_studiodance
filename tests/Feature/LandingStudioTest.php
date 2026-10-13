<?php

namespace Tests\Feature;

use App\Models\Studio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_displays_active_studios_and_reflects_database_updates(): void
    {
        $this->withoutVite();
        $studio = Studio::create([
            'name' => 'Ruang Aurora', 'description' => 'Ruang latihan dari database.',
            'capacity' => 20, 'price_per_hour' => 150000, 'status' => 'active',
            'image' => 'studios/aurora.jpg',
        ]);
        Studio::create([
            'name' => 'Ruang Nonaktif', 'capacity' => 10,
            'price_per_hour' => 100000, 'status' => 'inactive',
        ]);

        $this->get(route('landing'))->assertOk()
            ->assertSee('Ruang Aurora')->assertSee('Ruang latihan dari database.')
            ->assertSee(asset('storage/studios/aurora.jpg'))
            ->assertDontSee('Ruang Nonaktif');

        $studio->update(['name' => 'Ruang Aurora Baru', 'image' => null]);
        $this->get(route('landing'))->assertOk()->assertSee('Ruang Aurora Baru')
            ->assertDontSee('storage/studios/aurora.jpg');

        $studio->update(['status' => 'inactive']);
        $this->get(route('landing'))->assertOk()
            ->assertSee('No studios are currently available.')->assertDontSee('Ruang Aurora Baru');
    }

    public function test_landing_handles_an_empty_studio_database(): void
    {
        $this->withoutVite();
        $this->get(route('landing'))->assertOk()->assertSee('No studios are currently available.');
    }

    public function test_home_uses_live_availability_classes_and_destination_links(): void
    {
        $this->withoutVite();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 07:00:00'));
        $studio = Studio::factory()->create();
        $class = \App\Models\DanceClass::factory()->create(['name' => 'Afro Database', 'price' => 70000, 'status' => 'active']);
        \App\Models\Booking::factory()->create([
            'studio_id' => $studio->id, 'date' => '2026-10-15',
            'start_time' => '08:00', 'end_time' => '22:00', 'status' => 'pending',
        ]);

        $this->get(route('landing'))->assertOk()
            ->assertSee('No available studio slots today.')
            ->assertSee('Afro Database')->assertSee('Rp 70.000')
            ->assertSee(route('user.studios.index'), false)
            ->assertSee(route('user.classes.index', ['class_id' => $class->id]), false)
            ->assertSee(route('memberships.index'), false)
            ->assertDontSee('Sample available slots.');
    }
}
