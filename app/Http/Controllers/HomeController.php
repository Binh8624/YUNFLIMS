<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Cinema;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:now_showing,coming_soon'],
        ]);
        $query = Movie::whereIn('status', ['now_showing', 'coming_soon']);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('genre', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $movies = $query->orderByDesc('created_at')->get();

        $featured = Movie::where('status', 'now_showing')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $comingSoon = Movie::where('status', 'coming_soon')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return view('home', compact('movies', 'featured', 'comingSoon'));
    }

    public function show(Request $request, Movie $movie)
    {
        abort_if($movie->status === 'inactive', 404);

        $validated = $request->validate([
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
        ]);
        $date = $validated['date'] ?? now()->toDateString();
        $dayStart = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $movie->load([
            'formats' => fn ($query) => $query->where('formats.is_active', true),
            'showtimes' => function ($query) use ($dayStart, $dayEnd, $validated) {
                $query->with(['room.cinema', 'format'])
                    ->where('status', 'scheduled')
                    ->where('starts_at', '>', now())
                    ->where('starts_at', '>=', $dayStart)
                    ->where('starts_at', '<', $dayEnd)
                    ->whereHas('format', fn ($format) => $format->where('is_active', true))
                    ->whereHas('room', fn ($room) => $room->where('is_active', true)
                        ->whereHas('cinema', fn ($cinema) => $cinema->where('is_active', true)))
                    ->whereHas('movie.formats', fn ($format) => $format->whereColumn('formats.id', 'showtimes.format_id'))
                    ->when(
                        isset($validated['cinema_id']),
                        fn ($query) => $query->whereHas(
                            'room.cinema',
                            fn ($cinema) => $cinema->whereKey($validated['cinema_id'])
                        )
                    )
                    ->orderBy('starts_at');
            },
            'reviews' => fn ($query) => $query->with('user')->latest()->limit(20),
        ]);

        $movie->loadCount('reviews')->loadAvg('reviews', 'rating');
        $cinemas = Cinema::where('is_active', true)->orderBy('name')->get();
        $myReview = auth()->check()
            ? $movie->reviews()->where('user_id', auth()->id())->first()
            : null;

        return view('movies.show', compact('movie', 'date', 'cinemas', 'myReview'));
    }
}
