<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [
            'customer' => ['customer', 'landing'],
            'admin' => ['admin', 'admin.dashboard'],
            'super admin' => ['super_admin', 'admin.dashboard'],
        ];
    }

    #[DataProvider('roles')]
    public function test_login_redirects_to_the_page_for_the_role(string $role, string $destination): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route($destination));

        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('roles')]
    public function test_dashboard_access_and_navigation_follow_the_role(string $role, string $destination): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => $role]));

        $this->get('/dashboard')->assertRedirect(route($destination));
        $this->get(route('login'))->assertRedirect(route($destination));
        $this->get(route('register'))->assertRedirect(route($destination));

        $landing = $this->get(route('landing'))->assertOk()->assertSee('Logout');

        if ($role === 'customer') {
            $this->get(route('admin.dashboard'))->assertForbidden();
            $landing->assertDontSee(route('admin.dashboard'));
        } else {
            $this->get(route('admin.dashboard'))
                ->assertOk()
                ->assertViewIs('admin.dashboard.index')
                ->assertSee('id="sidebar"', false);
            $landing->assertSee(route('admin.dashboard'));
        }
    }

    public function test_guest_can_view_landing_but_must_log_in_for_dashboard(): void
    {
        $this->withoutVite();
        $this->get(route('landing'))->assertOk()->assertSee('Login')->assertDontSee('Logout');
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_registration_creates_a_customer_and_redirects_to_landing(): void
    {
        $this->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'new@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => 'Jalan Studio 1',
            'state' => 'Bali',
            'phone' => '81234567890',
            'terms' => '1',
            'role' => 'super_admin',
        ])->assertRedirect(route('landing'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.test', 'role' => 'customer']);
    }
}
