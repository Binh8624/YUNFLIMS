<?php

namespace Tests\Feature;

use App\Models\Movie;
use Tests\TestCase;

class MovieDetailTest extends TestCase
{
    public function test_active_movie_detail_renders_schedule_and_reviews(): void
    {
        $movie = Movie::where('status', 'now_showing')->firstOrFail();

        $this->get(route('movies.show', $movie))
            ->assertOk()
            ->assertSee('Đánh giá phim')
            ->assertSee('Chọn ngày và rạp');
    }
}
