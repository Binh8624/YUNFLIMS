<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Cinema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $featured = Movie::query()
            ->where('status', 'now_showing')
            ->whereNotNull('title')
            ->where('title', '<>', '')
            ->whereNotNull('poster')
            ->where('poster', '<>', '')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount([
                'bookings as bookings_count' => fn (Builder $bookings) => $bookings
                    ->where('bookings.status', 'confirmed'),
            ])
            ->orderByDesc('bookings_count')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $movies = Movie::query()
            ->where('status', 'now_showing')
            ->whereNotNull('title')
            ->where('title', '<>', '')
            ->whereNotNull('poster')
            ->where('poster', '<>', '')
            ->whereNotIn('id', $featured->modelKeys())
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $comingSoon = Movie::query()
            ->where('status', 'coming_soon')
            ->whereNotNull('title')
            ->where('title', '<>', '')
            ->whereNotNull('poster')
            ->where('poster', '<>', '')
            ->latest()
            ->limit(5)
            ->get();

        return view('home', compact('movies', 'featured', 'comingSoon'));
    }

    public function movies(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:now_showing,coming_soon'],
            'genre' => ['nullable', 'string', 'max:150'],
            'age_rating' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'in:newest,popular,rated,title'],
        ]);

        $query = Movie::query()
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount([
                'bookings' => fn (Builder $bookings) => $bookings
                    ->where('bookings.status', 'confirmed'),
            ]);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where('title', 'like', "%{$search}%");
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['genre'])) {
            $query->where('genre', $filters['genre']);
        }

        if (! empty($filters['age_rating'])) {
            $query->where('age_rating', $filters['age_rating']);
        }

        match ($filters['sort'] ?? 'newest') {
            'popular' => $query->orderByDesc('bookings_count'),
            'rated' => $query->orderByDesc('reviews_avg_rating'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('created_at'),
        };

        $movies = $query->paginate(12)->withQueryString();
        $genres = Movie::whereIn('status', ['now_showing', 'coming_soon'])
            ->whereNotNull('genre')
            ->where('genre', '<>', '')
            ->distinct()
            ->orderBy('genre')
            ->pluck('genre');
        $ageRatings = Movie::whereIn('status', ['now_showing', 'coming_soon'])
            ->whereNotNull('age_rating')
            ->where('age_rating', '<>', '')
            ->distinct()
            ->orderBy('age_rating')
            ->pluck('age_rating');

        return view('movies.index', compact('movies', 'genres', 'ageRatings'));
    }

    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $search = trim($validated['search']);

        $titles = Movie::query()
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->where('title', 'like', "%{$search}%")
            ->orderBy('title')
            ->limit(8)
            ->pluck('title');

        return response()->json($titles);
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
        $showDates = collect(range(0, 13))
            ->map(fn (int $offset) => now()->startOfDay()->addDays($offset));

        if (! $showDates->contains(fn ($showDate) => $showDate->toDateString() === $date)) {
            $showDates->push(\Illuminate\Support\Carbon::parse($date)->startOfDay());
        }

        $showDates = $showDates
            ->sortBy(fn ($showDate) => $showDate->timestamp)
            ->values();

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
                    ->whereHas('movie.formats', fn ($format) => $format
                        ->whereColumn('formats.id', 'showtimes.format_id')
                        ->where('formats.is_active', true))
                    ->whereHas('room.formats', fn ($format) => $format
                        ->whereColumn('formats.id', 'showtimes.format_id')
                        ->where('formats.is_active', true))
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
        $relatedMovies = Movie::query()
            ->where('id', '<>', $movie->getKey())
            ->where('genre', $movie->genre)
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->limit(4)
            ->get();
        $myReview = auth()->check()
            ? $movie->reviews()->where('user_id', auth()->id())->first()
            : null;

        return view('movies.show', compact(
            'movie',
            'date',
            'showDates',
            'cinemas',
            'myReview',
            'relatedMovies'
        ));
    }
}
