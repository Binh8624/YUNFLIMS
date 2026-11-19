<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $isFavorite = DB::transaction(function () use ($request, $movie) {
            $user = User::query()
                ->whereKey($request->user()->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->isFavoriteMovie($movie)) {
                $user->favoriteMovies()->detach($movie);

                return false;
            }

            $user->favoriteMovies()->syncWithoutDetaching([$movie->getKey()]);

            return true;
        });

        return back()->with(
            'success',
            $isFavorite
                ? 'Đã thêm phim vào danh sách yêu thích.'
                : 'Đã xóa phim khỏi danh sách yêu thích.'
        );
    }
}
