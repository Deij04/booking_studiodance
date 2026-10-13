<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\DanceClass;
use App\Models\Studio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserClassPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_class_page_shows_database_content_and_upcoming_sessions(): void
    {
        $this->withoutVite();
        $class = DanceClass::factory()->create(['name' => 'Hip Hop Public', 'status' => 'active', 'image' => null]);
        $hidden = DanceClass::factory()->create(['name' => 'Hidden Class', 'status' => 'inactive']);
        $studio = Studio::factory()->create(['status' => 'active']);
        ClassSchedule::factory()->create([
            'dance_class_id' => $class->id, 'studio_id' => $studio->id, 'date' => today()->addDay()->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '11:00:00', 'capacity' => 12, 'status' => 'active',
        ]);
        $this->get(route('user.classes.index'))->assertOk()->assertViewIs('user.classes.index')
            ->assertSee('Hip Hop Public')->assertSee('12 spots left')->assertSee('Image unavailable')
            ->assertSee('10:00')->assertSee($studio->name)->assertDontSee($hidden->name);
    }

    public function test_filter_and_empty_results_work(): void
    {
        $this->withoutVite();
        $first = DanceClass::factory()->create(['status' => 'active']);
        DanceClass::factory()->create(['status' => 'active']);
        $this->get(route('user.classes.index', ['class_id' => $first->id]))->assertOk()
            ->assertViewHas('classes', fn ($classes) => $classes->count() === 1 && $classes->first()->id === $first->id)
            ->assertViewHas('catalog', fn ($catalog) => $catalog->count() === 1 && $catalog->first()->id === $first->id)
            ->assertSee('id="class-filter" name="class_id"', false)
            ->assertSee('No available class schedules match your selection.');
        $this->get(route('user.classes.index', ['class_id' => 999999]))->assertOk()
            ->assertViewHas('catalog', fn ($catalog) => $catalog->isEmpty())
            ->assertSee('No available class schedules match your selection.');
    }
    public function test_schedule_search_filters_class_sessions_and_studio_page_has_no_search_form(): void
    {
        $this->withoutVite();
        $studio = Studio::factory()->create(['status' => 'active']);
        $date = today()->addDay()->toDateString();
        $class = DanceClass::factory()->create(['status' => 'active']);
        ClassSchedule::factory()->create([
            'dance_class_id' => $class->id, 'studio_id' => $studio->id,
            'date' => $date, 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'status' => 'active',
        ]);
        $this->get(route('user.classes.index', ['studio_id' => $studio->id, 'date' => $date, 'start_time' => '09:00', 'end_time' => '12:00']))
            ->assertOk()->assertViewHas('classes', fn ($classes) => $classes->count() === 1);
        $this->get(route('user.classes.index', ['date' => $date, 'start_time' => '12:00', 'end_time' => '13:00']))
            ->assertOk()->assertViewHas('classes', fn ($classes) => $classes->isEmpty());
        $this->get(route('user.studios.index'))->assertOk()->assertDontSee('class="studio-search"', false);
    }

}
