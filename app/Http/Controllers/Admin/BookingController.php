<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\PaymentRefund;
use App\Models\Showtime;
use App\Services\Payment\RefundService;
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

    public function cancel(Request $request, Booking $booking, RefundService $refunds)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($booking->status === 'confirmed' && $booking->payment_status === 'paid') {
            if (empty($validated['reason'])) {
                throw ValidationException::withMessages([
                    'reason' => 'Bắt buộc nhập lý do hủy khi hoàn tiền đơn đã thanh toán.',
                ]);
            }

            $refund = $refunds->request($booking, $request->user(), $validated['reason']);

            if ($refund->status === PaymentRefund::STATUS_SUCCEEDED) {
                return redirect()->route('admin.bookings.show', $booking)
                    ->with('success', 'Đã hủy đơn và hoàn tiền thành công qua cổng thanh toán.');
            }

            return redirect()->route('admin.bookings.show', $booking)
                ->withErrors(['booking' => 'Hoàn tiền thất bại. Đơn giữ nguyên trạng thái đã thanh toán.']);
        }

        DB::transaction(function () use ($booking, $validated, $request) {
            Showtime::whereKey($booking->showtime_id)->lockForUpdate()->firstOrFail();
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->status !== 'pending' || $lockedBooking->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'booking' => 'Chỉ có thể hủy trực tiếp đơn đang chờ thanh toán.',
                ]);
            }

            $lockedBooking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'ticket_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancel_reason' => $validated['reason'] ?? 'Admin hủy đơn chờ thanh toán',
            ]);
            BookingSeat::where('booking_id', $lockedBooking->id)
                ->whereNull('released_at')
                ->update(['released_at' => now()]);
        });

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Đơn đặt vé đã được hủy.');
    }
}
