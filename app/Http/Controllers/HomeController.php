<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Support\MovieGenres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    public function index()
    {
        $hasPopularityData = Movie::query()
            ->whereHas('bookings', fn (Builder $bookings) => $bookings->where('bookings.status', 'confirmed'))
            ->exists()
            || DB::table('reviews')->exists();

        $featuredQuery = Movie::query()
            ->with('format')
            ->where('status', 'now_showing')
            ->where('age_rating', '<>', 'C')
            ->whereNotNull('title')
            ->where('title', '<>', '')
            ->whereNotNull('poster')
            ->where('poster', '<>', '')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount([
                'bookings as bookings_count' => fn (Builder $bookings) => $bookings
                    ->where('bookings.status', 'confirmed'),
            ]);

        if ($hasPopularityData) {
            $featuredQuery
                ->where(fn (Builder $query) => $query
                    ->whereHas('bookings', fn (Builder $bookings) => $bookings->where('bookings.status', 'confirmed'))
                    ->orWhereHas('reviews'))
                ->orderByDesc('bookings_count')
                ->orderByDesc('reviews_avg_rating');
        } else {
            $featuredQuery->where('is_featured', true);
        }

        $featured = $featuredQuery->orderByDesc('created_at')->limit(5)->get();

        $movies = Movie::query()
            ->with('format')
            ->where('status', 'now_showing')
            ->where('age_rating', '<>', 'C')
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
            ->with('format')
            ->where('status', 'coming_soon')
            ->where('age_rating', '<>', 'C')
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
        $publicMovies = Movie::query()
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->where('age_rating', '<>', 'C');
        $genres = MovieGenres::options((clone $publicMovies)->pluck('genre'));
        $ageRatings = (clone $publicMovies)
            ->where('age_rating_verified', true)
            ->whereNotNull('age_rating')
            ->where('age_rating', '<>', '')
            ->distinct()
            ->orderBy('age_rating')
            ->pluck('age_rating');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:now_showing,coming_soon'],
            'genre' => ['nullable', 'string', Rule::in($genres)],
            'age_rating' => ['nullable', 'string', Rule::in($ageRatings->all())],
            'sort' => ['nullable', 'in:newest,popular,rated,title'],
        ]);

        $query = (clone $publicMovies)
            ->with('format')
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
            MovieGenres::applyFilter($query, $filters['genre']);
        }

        if (! empty($filters['age_rating'])) {
            $query->where('age_rating_verified', true)
                ->where('age_rating', $filters['age_rating']);
        }

        match ($filters['sort'] ?? 'newest') {
            'popular' => $query->orderByDesc('bookings_count'),
            'rated' => $query->orderByDesc('reviews_avg_rating'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('created_at'),
        };

        $movies = $query->paginate(12)->withQueryString();

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
            ->where('age_rating', '<>', 'C')
            ->where('title', 'like', "%{$search}%")
            ->orderBy('title')
            ->limit(8)
            ->pluck('title');

        return response()->json($titles);
    }

    public function show(Request $request, Movie $movie)
    {
        abort_if($movie->status === 'inactive' || $movie->age_rating === 'C', 404);
        $validated = $request->validate([
            'date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $date = $validated['date'] ?? null;

        $movie->load([
            'format',
            'reviews' => fn ($query) => $query->with('user')->latest()->limit(20),
        ]);

        $movie->loadCount('reviews')->loadAvg('reviews', 'rating');
        $relatedGenre = $movie->genre_labels[0] ?? null;
        $relatedMovies = Movie::query()
            ->with('format')
            ->where('id', '<>', $movie->getKey())
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->where('age_rating', '<>', 'C')
            ->when(
                $relatedGenre !== null,
                fn (Builder $query) => MovieGenres::applyFilter($query, $relatedGenre)
            )
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->limit(4)
            ->get();
        $myReview = auth()->check()
            ? $movie->reviews()->where('user_id', auth()->id())->first()
            : null;

        return view('movies.show', compact('movie', 'relatedMovies', 'myReview', 'relatedGenre', 'date'));
    }
}

