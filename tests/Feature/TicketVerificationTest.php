<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\TicketCheckin;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTestBooking;
use Tests\TestCase;

class TicketVerificationTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTestBooking;

    public function test_paid_ticket_can_be_checked_in_once_and_records_staff_history(): void
    {
        $booking = $this->paidBooking(Carbon::now()->subMinute());
        $staff = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('tickets.checkin', $booking->booking_code))
            ->assertRedirect(route('tickets.verify', $booking->booking_code))
            ->assertSessionHas('success', 'Check-in vé thành công.');

        $this->assertSame('used', $booking->fresh()->ticket_status);
        $this->assertDatabaseHas('ticket_checkins', [
            'booking_id' => $booking->id,
            'checked_by' => $staff->id,
            'cinema_id' => $booking->showtime->room->cinema_id,
        ]);

        $this->post(route('tickets.checkin', $booking->booking_code))
            ->assertRedirect(route('tickets.verify', $booking->booking_code))
            ->assertSessionHasErrors('ticket');

        $this->assertSame(1, TicketCheckin::where('booking_id', $booking->id)->count());
        $this->get(route('tickets.verify', $booking->booking_code))
            ->assertOk()
            ->assertSeeText('Vé đã được sử dụng')
            ->assertSeeText('Đã check-in lúc');
    }

    public function test_unpaid_cancelled_and_future_tickets_cannot_be_checked_in(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $unpaid = $this->makeBooking();
        $this->actingAs($staff)
            ->get(route('tickets.verify', $unpaid->booking_code))
            ->assertOk()
            ->assertSeeText('Vé chưa thanh toán');
        $this->post(route('tickets.checkin', $unpaid->booking_code))
            ->assertSessionHasErrors('ticket');

        $cancelled = $this->paidBooking(Carbon::now()->subMinute());
        $cancelled->update([
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'ticket_status' => 'cancelled',
        ]);
        $this->get(route('tickets.verify', $cancelled->booking_code))
            ->assertOk()
            ->assertSeeText('Vé đã hủy hoặc hoàn tiền');
        $this->post(route('tickets.checkin', $cancelled->booking_code))
            ->assertSessionHasErrors('ticket');

        $future = $this->paidBooking(Carbon::now()->addHour());
        $this->get(route('tickets.verify', $future->booking_code))
            ->assertOk()
            ->assertSeeText('Chưa đến giờ check-in');
        $this->post(route('tickets.checkin', $future->booking_code))
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseCount('ticket_checkins', 0);
    }

    public function test_scanner_lookup_returns_ticket_state_and_unknown_code(): void
    {
        $booking = $this->paidBooking(Carbon::now()->subMinute());
        $staff = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->postJson(route('tickets.lookup'), ['booking_code' => $booking->booking_code])
            ->assertOk()
            ->assertJsonPath('status', 'valid')
            ->assertJsonPath('booking_code', $booking->booking_code);

        $this->postJson(route('tickets.lookup'), ['booking_code' => 'YF-'.Str::random(12)])
            ->assertNotFound()
            ->assertJsonPath('status', 'not_found');
    }

    public function test_employee_cannot_check_in_a_ticket_at_another_cinema(): void
    {
        $booking = $this->paidBooking(Carbon::now()->subMinute());
        $otherCinema = Cinema::create([
            'name' => 'Other cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $employee = User::factory()->create([
            'role' => User::ROLE_TICKET_CHECKER,
            'cinema_id' => $otherCinema->id,
            'is_active' => true,
        ]);

        $this->actingAs($employee)
            ->post(route('tickets.checkin', $booking->booking_code))
            ->assertForbidden();

        $this->assertSame('available', $booking->fresh()->ticket_status);
        $this->assertDatabaseCount('ticket_checkins', 0);
    }

    public function test_unknown_ticket_renders_a_result_instead_of_a_gone_response(): void
    {
        $response = $this->get(route('tickets.verify', 'YF-'.Str::random(12)));

        $response->assertNotFound()
            ->assertSeeText('Không tìm thấy mã vé');
    }

    private function paidBooking(Carbon $startsAt): Booking
    {
        $booking = $this->makeBooking();
        $booking->showtime->update([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(90),
        ]);
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'hold_expires_at' => null,
        ]);

        return $booking->fresh();
    }
}
