<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\MockGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentCallbackController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function return(Request $request, string $gateway)
    {
        $this->abortUnknownGateway($gateway);
        $event = $this->payments->handleCallback($gateway, $request->all(), 'return');
        $payment = $event->payment;

        if ($payment && $request->user()?->id === $payment->booking->user_id) {
            $booking = $payment->booking;

            if ($payment->status === Payment::STATUS_SUCCEEDED) {
                return redirect()->route('bookings.show', $booking)
                    ->with('success', 'Thanh toán thành công. Vé đã được xác nhận.');
            }

            return redirect()->route('bookings.show', $booking)
                ->withErrors(['payment' => 'Giao dịch chưa thành công. Trạng thái: ' . $payment->status . '.']);
        }

        return redirect()->route('home')
            ->with('success', 'Đã ghi nhận kết quả giao dịch.');
    }

    public function webhook(Request $request, string $gateway)
    {
        $this->abortUnknownGateway($gateway);
        $this->payments->handleCallback($gateway, $request->all(), 'webhook');

        if ($gateway === 'vnpay') {
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        }

        return response()->json(['status' => 'ok']);
    }

    public function mockCheckout(Payment $payment)
    {
        $this->guardMock($payment);
        $payment->load('booking.showtime.movie');

        return view('bookings.mock-checkout', compact('payment'));
    }

    public function mockSimulate(Request $request, Payment $payment)
    {
        $this->guardMock($payment);
        $validated = $request->validate([
            'result' => ['required', 'in:success,failed,timeout'],
        ]);

        $payload = [
            'order_ref' => $payment->order_ref,
            'transaction_code' => 'MOCK-' . strtoupper(Str::random(10)),
            'amount' => (string) $payment->amount,
            'status' => $validated['result'],
            'nonce' => Str::random(8),
        ];
        $payload['signature'] = (new MockGateway)->sign($payload);

        $this->payments->handleCallback('mock', $payload, 'webhook');

        return redirect()->to(route('payment.return', ['gateway' => 'mock']) . '?' . http_build_query($payload));
    }

    private function guardMock(Payment $payment): void
    {
        abort_unless(config('payment.gateways.mock.enabled'), 404);
        abort_unless($payment->gateway === 'mock' && $payment->status === Payment::STATUS_PENDING, 404);
        abort_unless($payment->booking->user_id === auth()->id(), 403);
    }

    private function abortUnknownGateway(string $gateway): void
    {
        abort_unless(array_key_exists($gateway, config('payment.gateways', [])), 404);
    }
}