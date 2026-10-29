<?php

namespace App\Http\Controllers;

use App\Models\Booking;

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

        return view('tickets.verify', compact('booking'));
    }
}
