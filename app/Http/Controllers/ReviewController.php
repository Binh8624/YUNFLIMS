<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Movie $movie)
    {
        abort_if(
            $movie->status === 'inactive' || $movie->age_rating === 'C',
            404
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $canReview = $movie->bookings()
            ->where('bookings.user_id', $request->user()->id)
            ->where('bookings.status', 'confirmed')
            ->where('bookings.payment_status', 'paid')
            ->where('bookings.ticket_status', 'used')
            ->exists();

        if (! $canReview) {
            return back()
                ->withErrors([
                    'rating' => 'Bạn cần mua vé, thanh toán và sử dụng vé thành công để đánh giá phim này.',
                ])
                ->withInput();
        }

        Review::updateOrCreate(
            [
                'movie_id' => $movie->id,
                'user_id' => $request->user()->id,
            ],
            $data
        );

        return redirect()
            ->route('movies.show', $movie)
            ->with('success', 'Đã lưu đánh giá của bạn.');
    }
}
