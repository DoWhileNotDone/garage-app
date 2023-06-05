<?php

namespace Tests\Feature\Auth;

use App\Events\UserImported;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordSetTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_password_can_be_rendered_for_imported_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        event(new UserImported($user));

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/set-password/' . $notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_set_for_imported_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        event(new UserImported($user));

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {

            Event::fake();

            $response = $this->post('/set-password/', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertStatus(302);

            Event::assertDispatched(PasswordReset::class);
            Event::assertDispatched(Verified::class);
            $this->assertTrue($user->fresh()->hasVerifiedEmail());
            $response->assertRedirect('/login');

            return true;
        });
    }

    public function test_password_cannot_be_set_with_incorrect_password_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        event(new UserImported($user));

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {

            Event::fake();

            $response = $this->post('/set-password/', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password123',
            ]);

            $response->assertStatus(302);

            $response->assertSessionHasErrors([
                'password' => 'The password field confirmation does not match.'
            ]);

            return true;
        });
    }
}
