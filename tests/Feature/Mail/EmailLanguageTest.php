<?php

use App\Jobs\SendProExpiryReminder;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mime\Email;

/**
 * Last email captured by the "array" mailer used in tests.
 */
function lastSentEmail(): Email
{
    $message = app('mail.manager')->mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();

    expect($message)->toBeInstanceOf(Email::class);

    return $message;
}

test('the verification email is written in Indonesian', function () {
    User::factory()->unverified()->create()->sendEmailVerificationNotification();

    $email = lastSentEmail();

    expect($email->getSubject())->toBe('Verifikasi alamat email kamu')
        ->and($email->getHtmlBody())
        ->toContain('Halo!', 'Verifikasi email', 'abaikan saja email ini', 'Salam,', 'Hak cipta dilindungi.')
        ->not->toContain('Verify', 'Regards', 'All rights reserved', 'trouble clicking');
});

test('the password reset email is written in Indonesian', function () {
    $user = User::factory()->create();

    expect(Password::sendResetLink(['email' => $user->email]))->toBe(Password::RESET_LINK_SENT);

    $email = lastSentEmail();

    expect($email->getSubject())->toBe('Atur ulang kata sandi kamu')
        ->and($email->getHtmlBody())
        ->toContain('Atur ulang kata sandi', 'Tautan ini hanya berlaku 60 menit.', 'Kata sandimu tidak akan berubah.')
        ->not->toContain('Reset Password', 'expire', 'Regards');
});

test('reminder emails print Indonesian month names', function () {
    config(['pricing.enabled' => true]);
    $this->travelTo(now()->setDate(2026, 12, 1)->setTime(7, 10));

    $subscription = Subscription::query()->create([
        'user_id' => User::factory()->create()->id,
        'plan' => 'pro_12bln',
        'active_until' => now()->addDays(7)->setTime(12, 0),
    ]);

    SendProExpiryReminder::dispatchSync($subscription->id, 7);

    expect(lastSentEmail()->getHtmlBody())
        ->toContain('8 Desember 2026', 'Hak cipta dilindungi.')
        ->not->toContain('December');
});
