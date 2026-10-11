<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_pages_require_login(): void
    {
        $this->get(route('user.profile'))->assertRedirect(route('login'));
        $this->get(route('user.history'))->assertRedirect(route('login'));
    }

    public function test_dropdown_profile_history_and_logout_use_the_authenticated_account(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'Dancer Aurora']);
        $own = Membership::factory()->create(['user_id' => $user->id]);
        Membership::factory()->create();
        $this->actingAs($user)->get(route('user.about'))->assertOk()
            ->assertSee('Dancer Aurora')->assertSee(route('user.profile'))
            ->assertSee(route('user.history'))->assertSee('Log Out');
        $this->get(route('user.profile'))->assertOk()->assertSee($user->email);
        $this->get(route('user.history'))->assertOk()
            ->assertViewHas('memberships', fn ($records) => $records->count() === 1 && $records->first()->id === $own->id)
            ->assertViewHas('bookings', fn ($records) => $records->isEmpty())
            ->assertViewHas('registrations', fn ($records) => $records->isEmpty());
        $this->post(route('logout'))->assertRedirect(route('landing'));
        $this->assertGuest();
        $this->get(route('user.about'))->assertOk()->assertSee('Login')->assertDontSee('Dancer Aurora');
    }
}
