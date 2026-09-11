<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Managed User',
            'email' => 'managed@example.test',
            'phone' => '08123456789',
            'role' => 'customer',
            'status' => 'active',
            'password' => 'password123',
        ], $overrides);
    }

    public function test_admin_list_and_search_only_include_customers(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Staff Account']);
        User::factory()->superAdmin()->create(['name' => 'Staff Root']);
        $customer = User::factory()->create(['name' => 'Staff Customer']);
        $this->actingAs($admin);

        foreach (['', '?search=Staff', '?search=%'] as $query) {
            $this->get(route('admin.users.index').$query)
                ->assertOk()
                ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->is($customer));
        }

        $this->get(route('admin.users.create'))->assertOk()
            ->assertDontSee('value="admin"', false)->assertDontSee('value="super_admin"', false);
    }

    public function test_admin_can_create_edit_and_delete_a_customer(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('admin.users.store'), $this->payload())->assertRedirect(route('admin.users.index'));
        $customer = User::where('email', 'managed@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $customer->password));
        $this->get(route('admin.users.edit', $customer))->assertOk()->assertSee('Managed User');
        $originalPassword = $customer->password;

        $this->put(route('admin.users.update', $customer), $this->payload([
            'name' => 'Updated Customer', 'password' => '',
        ]))->assertRedirect(route('admin.users.index'));
        $this->assertSame('Updated Customer', $customer->fresh()->name);
        $this->assertSame($originalPassword, $customer->fresh()->password);

        $this->delete(route('admin.users.destroy', $customer))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($customer);
    }

    public static function privilegedRoles(): array
    {
        return [['admin'], ['super_admin']];
    }

    #[DataProvider('privilegedRoles')]
    public function test_admin_cannot_manage_staff_or_assign_a_privileged_role(string $role): void
    {
        $actor = User::factory()->admin()->create();
        $staff = User::factory()->create(['role' => $role]);
        $customer = User::factory()->create();
        $this->actingAs($actor);

        $this->get(route('admin.users.edit', $staff))->assertForbidden();
        $this->put(route('admin.users.update', $staff), $this->payload())->assertForbidden();
        $this->delete(route('admin.users.destroy', $staff))->assertForbidden();
        $this->post(route('admin.users.store'), $this->payload(['role' => $role]))->assertSessionHasErrors('role');
        $this->put(route('admin.users.update', $customer), $this->payload(['role' => $role]))->assertSessionHasErrors('role');
        $this->assertSame('customer', $customer->fresh()->role);
        $this->assertSame($role, $staff->fresh()->role);
        $this->assertDatabaseMissing('users', ['email' => 'managed@example.test']);
    }

    public static function allRoles(): array
    {
        return [['customer'], ['admin'], ['super_admin']];
    }

    #[DataProvider('allRoles')]
    public function test_super_admin_can_manage_every_role(string $role): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->post(route('admin.users.store'), $this->payload(['role' => $role]))->assertRedirect(route('admin.users.index'));
        $target = User::where('email', 'managed@example.test')->firstOrFail();
        $this->assertSame($role, $target->role);
        $this->get(route('admin.users.index'))->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 2);
        $this->get(route('admin.users.create'))->assertOk()->assertSee('value="super_admin"', false);
        $this->get(route('admin.users.edit', $target))->assertOk();
        $this->put(route('admin.users.update', $target), $this->payload([
            'role' => 'super_admin', 'password' => 'newpassword123',
        ]))->assertRedirect(route('admin.users.index'));
        $this->assertSame('super_admin', $target->fresh()->role);
        $this->assertTrue(Hash::check('newpassword123', $target->fresh()->password));
        $this->delete(route('admin.users.destroy', $target))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($target);
    }

    public function test_customer_and_guest_cannot_access_user_management(): void
    {
        $target = User::factory()->create();
        $requests = [
            ['get', route('admin.users.index')],
            ['get', route('admin.users.create')],
            ['get', route('admin.users.edit', $target)],
            ['post', route('admin.users.store')],
            ['put', route('admin.users.update', $target)],
            ['delete', route('admin.users.destroy', $target)],
        ];
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertRedirect(route('login'));
        }
        $this->actingAs($target);
        foreach ($requests as [$method, $url]) {
            $this->{$method}($url)->assertForbidden();
        }
        $this->assertModelExists($target);
    }

    public function test_invalid_data_is_rejected_and_password_is_not_exposed(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        $existing = User::factory()->create();
        $this->post(route('admin.users.store'), $this->payload([
            'email' => $existing->email, 'role' => 'owner', 'status' => 'unknown',
            'password' => 'short',
        ]))->assertSessionHasErrors(['email', 'role', 'status', 'password']);
        $this->get(route('admin.users.edit', $existing))->assertOk()->assertDontSee($existing->password);
    }

    public function test_current_super_admin_cannot_delete_or_remove_own_access(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $this->actingAs($actor);
        $this->delete(route('admin.users.destroy', $actor))->assertForbidden();
        foreach ([['role' => 'customer'], ['role' => 'super_admin', 'status' => 'inactive']] as $changes) {
            $this->put(route('admin.users.update', $actor), $this->payload($changes))->assertSessionHasErrors('role');
        }
        $this->assertSame('super_admin', $actor->fresh()->role);
        $this->assertSame('active', $actor->fresh()->status);
    }

    public function test_account_with_booking_history_cannot_be_deleted(): void
    {
        $customer = User::factory()->create();
        $reservation = Reservation::factory()->create(['user_id' => $customer->id]);
        $this->actingAs(User::factory()->admin()->create());
        $this->delete(route('admin.users.destroy', $customer))
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('error');
        $this->assertModelExists($customer);
        $this->assertModelExists($reservation);
    }

    public function test_deactivated_account_cannot_login_or_continue_an_existing_session(): void
    {
        $customer = User::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('admin.users.update', $customer), $this->payload([
            'email' => $customer->email, 'status' => 'inactive',
        ]))->assertRedirect(route('admin.users.index'));
        $this->assertSame('inactive', $customer->fresh()->status);

        $this->actingAs($customer->fresh());
        $this->get(route('landing'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.store'), [
            'email' => $customer->email, 'password' => 'password123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
