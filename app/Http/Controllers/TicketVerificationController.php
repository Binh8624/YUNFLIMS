<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\Booking;
use App\Models\TicketCheckin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketVerificationController extends Controller
{
    public function scan()
    {
        return view('tickets.scan');
    }

    public function lookup(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'booking_code' => ['required', 'string', 'max:64'],
        ]);
        $bookingCode = trim($validated['booking_code']);
        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'payments:id,booking_id,status',
            'ticketCheckins',
        ])->where('booking_code', $bookingCode)->first();

        if (! $booking) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Không tìm thấy mã vé.',
                ], 404);
            }

            return redirect()->route('tickets.scan')
                ->withInput()
                ->withErrors(['booking_code' => 'Không tìm thấy mã vé.']);
        }

        $cinemaId = (int) $booking->showtime->room->cinema_id;
        abort_unless($request->user()->canManageCinema($cinemaId), 403);

        $status = $this->ticketStatus($booking);
        $data = [
            'status' => $status,
            'message' => $this->statusMessage($booking, $status),
            'booking_code' => $booking->booking_code,
            'movie' => $booking->showtime->movie->title,
            'cinema' => $booking->showtime->room->cinema->name,
            'room' => $booking->showtime->room->name,
            'showtime' => $booking->showtime->starts_at->toIso8601String(),
            'seats' => $booking->bookingSeats
                ->map(fn ($item) => $item->seat->seat_code
                    ?? ($item->seat->row_number . $item->seat->seat_number))
                ->values(),
        ];

        if ($request->expectsJson()) {
            return response()->json($data);
        }

        return redirect()->route('tickets.verify', ['bookingCode' => $bookingCode]);
    }

    public function show(string $bookingCode)
    {
        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.seat',
            'payments:id,booking_id,status',
            'ticketCheckins.actor:id,name',
        ])->where('booking_code', $bookingCode)->first();

        if (! $booking) {
            return response()->view('tickets.verify', [
                'booking' => null,
                'ticketStatus' => 'not_found',
                'canCheckIn' => false,
                'ticketCheckins' => collect(),
            ], 404);
        }

        $ticketStatus = $this->ticketStatus($booking);
        $cinemaId = (int) $booking->showtime->room->cinema_id;
        $actor = request()->user();
        $canCheckIn = $actor !== null
            && $actor->hasPermission('check_tickets')
            && $actor->canManageCinema($cinemaId);

        return view('tickets.verify', [
            'booking' => $booking,
            'ticketStatus' => $ticketStatus,
            'canCheckIn' => $canCheckIn,
            'ticketCheckins' => $booking->ticketCheckins,
            'ticketUsedAt' => $booking->ticket_used_at
                ?? $booking->ticketCheckins->first()?->checked_in_at,
        ]);
    }

    public function checkIn(Request $request, string $bookingCode): RedirectResponse
    {
        $bookingId = Booking::where('booking_code', $bookingCode)->value('id');
        if (! $bookingId) {
            return redirect()->route('tickets.scan')
                ->withErrors(['booking_code' => 'Không tìm thấy mã vé.']);
        }

        $result = DB::transaction(function () use ($bookingId, $request) {
            $lockedBooking = Booking::whereKey($bookingId)->lockForUpdate()->firstOrFail();
            $lockedBooking->load([
                'showtime.room.cinema',
                'payments:id,booking_id,status',
                'ticketCheckins',
            ]);
            $cinemaId = (int) $lockedBooking->showtime->room->cinema_id;
            $actor = $request->user();
            abort_unless($actor->canManageCinema($cinemaId), 403);

            $status = $this->ticketStatus($lockedBooking);
            if ($status !== 'valid') {
                return ['status' => $status, 'checked_in' => false];
            }

            $checkedInAt = now();
            $lockedBooking->update([
                'ticket_status' => 'used',
                'ticket_used_at' => $checkedInAt,
                'checkin_count' => ($lockedBooking->checkin_count ?? 0) + 1,
            ]);
            TicketCheckin::create([
                'booking_id' => $lockedBooking->id,
                'checked_by' => $actor->id,
                'cinema_id' => $cinemaId,
                'checked_in_at' => $checkedInAt,
            ]);
            AdminActivityLog::create([
                'actor_id' => $actor->id,
                'action' => 'tickets.checkin',
                'subject_type' => 'Booking',
                'subject_id' => (string) $lockedBooking->id,
                'http_method' => 'POST',
                'response_status' => 302,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'after' => [
                    'booking_code' => $lockedBooking->booking_code,
                    'cinema_id' => $cinemaId,
                    'ticket_used_at' => $checkedInAt->toIso8601String(),
                ],
            ]);
            Log::info('Ticket checked in.', [
                'booking_id' => $lockedBooking->id,
                'cinema_id' => $cinemaId,
                'actor_id' => $actor->id,
            ]);

            return ['status' => 'used', 'checked_in' => true];
        });

        if ($result['checked_in']) {
            return redirect()->route('tickets.verify', ['bookingCode' => $bookingCode])
                ->with('success', 'Check-in vé thành công.');
        }

        return redirect()->route('tickets.verify', ['bookingCode' => $bookingCode])
            ->withErrors(['ticket' => $this->statusMessageForState($result['status'])]);
    }

    private function ticketStatus(Booking $booking): string
    {
        if (
            $booking->status === 'cancelled'
            || $booking->payment_status === 'refunded'
            || $booking->ticket_status === 'cancelled'
            || $booking->payments->contains('status', 'refunded')
        ) {
            return 'cancelled';
        }

        if ($booking->status !== 'confirmed' || $booking->payment_status !== 'paid') {
            return 'unpaid';
        }

        if (
            $booking->ticket_status === 'used'
            || $booking->ticket_used_at !== null
            || $booking->ticketCheckins->isNotEmpty()
        ) {
            return 'used';
        }

        if ($booking->ticket_status !== null && $booking->ticket_status !== 'available') {
            return 'cancelled';
        }

        if ($booking->showtime->starts_at->isFuture()) {
            return 'not_started';
        }

        return 'valid';
    }

    private function statusMessage(Booking $booking, string $status): string
    {
        if ($status === 'used') {
            $usedAt = $booking->ticket_used_at
                ?? $booking->ticketCheckins->first()?->checked_in_at;

            return 'Vé đã được sử dụng'
                . ($usedAt ? ' lúc ' . $usedAt->format('d/m/Y H:i:s') : '.');
        }

        if ($status === 'not_started') {
            return 'Chưa đến giờ check-in. Suất chiếu bắt đầu lúc '
                . $booking->showtime->starts_at->format('d/m/Y H:i') . '.';
        }

        return $this->statusMessageForState($status);
    }

    private function statusMessageForState(string $status): string
    {
        return match ($status) {
            'valid' => 'Vé hợp lệ, đã thanh toán và có thể check-in.',
            'used' => 'Vé đã được sử dụng.',
            'cancelled' => 'Vé đã bị hủy hoặc hoàn tiền.',
            'unpaid' => 'Vé chưa được thanh toán.',
            'not_started' => 'Chưa đến thời gian check-in.',
            default => 'Không tìm thấy mã vé.',
        };
    }
}
