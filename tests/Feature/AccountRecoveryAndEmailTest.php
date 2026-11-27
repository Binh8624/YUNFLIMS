<?php

namespace Tests\Feature;

use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use App\Models\EmailDelivery;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class AccountRecoveryAndEmailTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTestBooking;

    public function test_registration_queues_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'New Member',
            'email' => 'member-'.Str::random(10).'@example.test',
            'password' => 'Str0ng-password!',
            'password_confirmation' => 'Str0ng-password!',
        ])->assertRedirect(route('home'));

        $user = User::where('name', 'New Member')->firstOrFail();
        Notification::assertSentTo($user, \App\Notifications\VerifyEmailNotification::class);
    }

    public function test_password_reset_link_is_sent_and_valid_token_changes_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $token = app('auth.password.broker')->createToken($user);
        $newPassword = 'An0ther-strong-password!';
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_signed_verification_link_marks_email_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active' => true,
        ]);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(10),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success', 'Địa chỉ email đã được xác nhận.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_transactional_email_delivery_is_idempotent_after_success(): void
    {
        Mail::fake();
        $booking = $this->makeBooking();
        $recipient = $booking->user;

        $delivery = EmailDelivery::dispatchOnce('booking_confirmed', $recipient, $booking);
        (new SendTransactionalEmail($delivery->id))->handle();

        EmailDelivery::dispatchOnce('booking_confirmed', $recipient, $booking);
        (new SendTransactionalEmail($delivery->id))->handle();

        $this->assertSame(1, EmailDelivery::where('idempotency_key', $delivery->idempotency_key)->count());
        $this->assertSame('sent', $delivery->fresh()->status);
        Mail::assertSent(TransactionalEmail::class, 1);
    }
}
