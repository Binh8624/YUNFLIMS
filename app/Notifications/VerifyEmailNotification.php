<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public array $backoff = [10, 60, 300, 900];

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Xác nhận địa chỉ email YUNFLIMS')
            ->greeting('Xin chào '.$notifiable->name.'!')
            ->line('Vui lòng xác nhận địa chỉ email để hoàn tất đăng ký tài khoản YUNFLIMS.')
            ->action('Xác nhận email', $url)
            ->line('Liên kết có hiệu lực trong 60 phút.');
    }
}
