<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'movies' => Movie::count(),
            'cinemas' => Cinema::count(),
            'rooms' => Room::count(),
            'showtimes' => Showtime::count(),
            'users' => User::count(),
            'bookings' => Booking::count(),
            'revenue' => Booking::where('payment_status', 'paid')->sum('total_amount'),
        ];

        $recentBookings = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema'])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentBookings'));
    }
}
