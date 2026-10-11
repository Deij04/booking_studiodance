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
}
