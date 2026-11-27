<?php

namespace App\Mail;

use App\Models\EmailDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionalEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmailDelivery $delivery)
    {
        $this->delivery->loadMissing([
            'booking.showtime.movie',
            'booking.showtime.room.cinema',
            'showtime.movie',
            'showtime.room.cinema',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy()['subject']);
    }

    public function content(): Content
    {
        $showtime = $this->delivery->booking?->showtime ?? $this->delivery->showtime;
        $booking = $this->delivery->booking;
        $details = [];

        if ($booking) {
            $details = [
                'Mã đặt vé' => $booking->booking_code,
                'Phim' => $showtime?->movie?->title ?? 'Chưa cập nhật',
                'Suất chiếu' => $showtime?->starts_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật',
                'Rạp' => $showtime?->room?->cinema?->name ?? 'Chưa cập nhật',
                'Tổng tiền' => number_format((float) $booking->total_amount, 0, ',', '.').' đ',
            ];
        } elseif ($showtime) {
            $details = [
                'Phim' => $showtime->movie?->title ?? 'Chưa cập nhật',
                'Suất chiếu' => $showtime->starts_at?->format('d/m/Y H:i') ?? 'Chưa cập nhật',
                'Rạp' => $showtime->room?->cinema?->name ?? 'Chưa cập nhật',
            ];
        }

        $copy = $this->copy();

        return new Content(
            view: 'emails.transactional',
            with: [
                'title' => $copy['subject'],
                'introText' => $copy['message'],
                'recipientName' => $this->delivery->user?->name ?? 'Quý khách',
                'details' => $details,
            ],
        );
    }

    private function copy(): array
    {
        return match ($this->delivery->type) {
            'booking_confirmed' => [
                'subject' => 'Xác nhận đặt vé YUNFLIMS',
                'message' => 'Đặt vé của bạn đã được xác nhận. Vui lòng lưu mã đặt vé để sử dụng khi đến rạp.',
            ],
            'payment_succeeded' => [
                'subject' => 'Thanh toán thành công - YUNFLIMS',
                'message' => 'YUNFLIMS đã nhận được thanh toán cho đơn đặt vé của bạn.',
            ],
            'booking_cancelled' => [
                'subject' => 'Thông báo hủy đặt vé - YUNFLIMS',
                'message' => 'Đơn đặt vé của bạn đã được hủy.',
            ],
            'refund_succeeded' => [
                'subject' => 'Thông báo hoàn tiền - YUNFLIMS',
                'message' => 'Yêu cầu hoàn tiền cho đơn đặt vé đã được xử lý thành công.',
            ],
            'showtime_cancelled' => [
                'subject' => 'Thông báo hủy suất chiếu - YUNFLIMS',
                'message' => 'Suất chiếu liên quan đến đơn đặt vé của bạn đã bị hủy. Vui lòng liên hệ rạp để được hỗ trợ.',
            ],
            default => throw new \InvalidArgumentException('Unsupported transactional email type: '.$this->delivery->type),
        };
    }
}
