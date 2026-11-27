<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CinemaController extends Controller
{
    public function index(Request $request)
    {
        $cinemas = Cinema::withCount('rooms')
            ->when(! $request->user()->canManageCinema(null), fn ($query) => $query->whereKey($request->user()->cinema_id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.cinemas.index', compact('cinemas'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canManageCinema(null), 403);

        return view('admin.cinemas.form', [
            'cinema' => new Cinema,
        ]);
    }

    public function store(Request $request)
    {
        Cinema::create($this->validateCinema($request));

        return redirect()
            ->route('admin.cinemas.index')
            ->with('success', 'Đã thêm rạp chiếu.');
    }

    public function edit(Cinema $cinema)
    {
        abort_unless(request()->user()->canManageCinema((int) $cinema->id), 403);
        return view('admin.cinemas.form', compact('cinema'));
    }

    public function update(Request $request, Cinema $cinema)
    {
        abort_unless($request->user()->canManageCinema((int) $cinema->id), 403);
        $data = $this->validateCinema($request);

        // Không cho tắt rạp nếu còn suất chiếu tương lai đã bán vé.
        if ($cinema->is_active && ! $data['is_active']) {
            $hasPaidFutureBookings = $cinema->rooms()
                ->whereHas('showtimes', function ($query) {
                    $query->where('starts_at', '>', now())
                        ->whereHas('bookings', function ($bookingQuery) {
                            $bookingQuery
                                ->where('bookings.status', 'confirmed')
                                ->where('bookings.payment_status', 'paid');
                        });
                })
                ->exists();

            if ($hasPaidFutureBookings) {
                throw ValidationException::withMessages([
                    'is_active' => 'Không thể vô hiệu hóa rạp vì vẫn còn suất chiếu tương lai đã có khách thanh toán. Hãy xử lý các vé đã bán trước.',
                ]);
            }
        }

        $cinema->update($data);

        return redirect()
            ->route('admin.cinemas.index')
            ->with('success', 'Đã cập nhật rạp chiếu.');
    }

    public function destroy(Cinema $cinema)
    {
        abort_unless(request()->user()->canManageCinema((int) $cinema->id), 403);
        if ($cinema->rooms()->exists()) {
            throw ValidationException::withMessages([
                'cinema' => 'Không thể xóa rạp còn phòng chiếu. Hãy vô hiệu hóa rạp để giữ lịch sử dữ liệu.',
            ]);
        }

        $cinema->delete();

        return redirect()
            ->route('admin.cinemas.index')
            ->with('success', 'Đã xóa rạp chiếu.');
    }

    private function validateCinema(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
