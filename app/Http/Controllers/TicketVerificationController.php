<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class TicketVerificationController extends Controller
{
    public function show(string $bookingCode)
    {
        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
        ])->where('booking_code', $bookingCode)
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->firstOrFail();

        if (($booking->ticket_status ?? 'available') === 'used') {
            abort(410, 'Vé đã được quét và sử dụng.');
        }

        return view('tickets.verify', compact('booking'));
    }

    public function checkIn(string $bookingCode)
    {
        $result = DB::transaction(function () use ($bookingCode) {
            $lockedBooking = Booking::where('booking_code', $bookingCode)
                ->where('status', 'confirmed')
                ->where('payment_status', 'paid')
                ->lockForUpdate()
                ->first();

            if (! $lockedBooking) {
                abort(404);
            }

            $alreadyUsed = ($lockedBooking->ticket_status ?? 'available') === 'used';

            if ($alreadyUsed) {
                return ['already_used' => true];
            }

            $lockedBooking->update([
                'ticket_status' => 'used',
                'ticket_used_at' => now(),
                'checkin_count' => ($lockedBooking->checkin_count ?? 0) + 1,
            ]);

            return ['already_used' => false];
        });

        if ($result['already_used']) {
            return redirect()->route('tickets.verify', ['bookingCode' => $bookingCode])
                ->withErrors(['ticket' => 'Vé này đã được quét trước đó.']);
        }

        return redirect()->route('tickets.verify', ['bookingCode' => $bookingCode])
            ->with('success', 'Vé đã được xác nhận thành công.');
    }
}
