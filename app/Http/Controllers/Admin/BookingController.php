<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(function ($query) use ($search) {
                    $query->where('booking_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'user',
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
        ]);

        return view('admin.bookings.show', compact('booking'));
    }

    public function cancel(Booking $booking)
    {
        DB::transaction(function () use ($booking) {
            Showtime::whereKey($booking->showtime_id)->lockForUpdate()->firstOrFail();
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status !== 'pending' || $lockedBooking->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'booking' => 'Chỉ có thể hủy đơn đang chờ thanh toán. Đơn đã thanh toán cần quy trình hoàn tiền riêng.',
                ]);
            }

            $lockedBooking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
        });

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Đơn đặt vé đã được hủy.');
    }
}
