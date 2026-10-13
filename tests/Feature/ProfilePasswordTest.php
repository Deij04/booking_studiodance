<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_updates_only_authenticated_users_allowed_fields(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch(route('user.profile.update'), [
            'name' => 'Updated Dancer', 'email' => 'updated@example.com',
            'phone' => '+628123456', 'address' => 'Makassar', 'state' => 'Sulawesi Selatan',
            'id' => $other->id, 'role' => 'super_admin', 'status' => 'inactive', 'password' => 'injected',
        ])->assertRedirect(route('user.profile'))->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Dancer', 'email' => 'updated@example.com', 'role' => 'customer', 'status' => 'active']);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_duplicate_email_is_rejected_and_guests_cannot_update(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->patch(route('user.profile.update'), [])->assertRedirect(route('login'));
        $this->actingAs($user)->patch(route('user.profile.update'), ['name' => 'Changed', 'email' => $other->email])->assertSessionHasErrors('email');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->patch(route('user.profile.update'), ['name' => 'Changed', 'email' => $user->email])->assertSessionHasNoErrors();
    }

    public function test_reset_email_link_and_password_change_with_single_use_token(): void
    {
        $this->withoutVite();
        Notification::fake();
        $user = User::factory()->create();
        $this->get(route('password.request'))->assertOk();
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token, $user) {
            $token = $notification->token;
            $this->assertStringContainsString('/reset-password/', $notification->toMail($user)->actionUrl);

            return true;
        });
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee($user->email);
        $data = ['token' => $token, 'email' => $user->email, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password-123'])->assertRedirect(route('landing'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_expired_tokens_and_password_confirmation_are_rejected(): void
    {
        $user = User::factory()->create();
        $data = ['email' => $user->email, 'token' => 'invalid', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $data['token'] = Password::createToken($user);
        $this->post(route('password.update'), [...$data, 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        $this->travel(61)->minutes();
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_unknown_email_receives_generic_response(): void
    {
        Notification::fake();
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertSessionHasNoErrors()->assertSessionHas('status');
        Notification::assertNothingSent();
    }
}
