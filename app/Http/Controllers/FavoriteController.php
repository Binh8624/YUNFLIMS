<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()
            ->favoriteMovies()
            ->with('format')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest('movie_favorites.created_at')
            ->paginate(12)
            ->withQueryString();

        return view('favorites.index', compact('favorites'));
    }

    public function toggle(Request $request, Movie $movie)
    {
        $user = $request->user();

        if ($user->isFavoriteMovie($movie)) {
            $user->favoriteMovies()->detach($movie);

            return back()->with('success', 'Đã xóa phim khỏi danh sách yêu thích.');
        }

        $user->favoriteMovies()->syncWithoutDetaching([$movie->id]);

        return back()->with('success', 'Đã thêm phim vào danh sách yêu thích.');
    }
}
