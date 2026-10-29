<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Movie $movie)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        Review::updateOrCreate(
            ['movie_id' => $movie->id, 'user_id' => $request->user()->id],
            $data
        );

        return redirect()->route('movies.show', $movie)->with('success', 'Đã lưu đánh giá của bạn.');
    }
}
