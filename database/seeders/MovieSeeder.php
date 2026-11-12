<?php

namespace Database\Seeders;

use App\Models\Format;
use App\Models\Movie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            [
                'title' => 'Avengers: Endgame',
                'genre' => 'Hành động, Khoa học viễn tưởng',
                'duration' => 181,
                'label' => 'BOM TẤN',
                'poster' => 'https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg',
                'description' => 'Cuộc chiến quyết định số phận của vũ trụ.',
                'status' => 'now_showing',
            ],
            [
                'title' => 'Spider-Man: No Way Home',
                'genre' => 'Hành động, Phiêu lưu',
                'duration' => 148,
                'label' => 'NỔI BẬT',
                'poster' => 'https://image.tmdb.org/t/p/w500/1g0dhYtq4irTY1GPXvft6k4YLjm.jpg',
                'description' => 'Một cuộc phiêu lưu đặc biệt của Spider-Man.',
                'status' => 'now_showing',
            ],
            [
                'title' => 'Interstellar',
                'genre' => 'Khoa học viễn tưởng',
                'duration' => 169,
                'label' => 'KINH ĐIỂN',
                'poster' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
                'description' => 'Hành trình khám phá không gian và tương lai nhân loại.',
                'status' => 'now_showing',
            ],
            [
                'title' => 'Inception',
                'genre' => 'Hành động, Tâm lý',
                'duration' => 148,
                'label' => 'ĐÁNG XEM',
                'poster' => 'https://image.tmdb.org/t/p/w500/oYuLEt3zVCKq57qu2F8dT7NIa6f.jpg',
                'description' => 'Một nhiệm vụ xâm nhập vào thế giới giấc mơ.',
                'status' => 'now_showing',
            ],
        ];

        $catalog = json_decode(
            file_get_contents(database_path('seeders/data/real_movies.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($movies, $catalog): void {
            $defaultFormat = Schema::hasTable('movie_format')
                ? Format::where('slug', '2d')->first()
                : null;

            foreach (array_merge($movies, $catalog) as $movie) {
                $legacyTitle = $movie['legacy_title'] ?? null;
                unset($movie['legacy_title']);

                if ($legacyTitle) {
                    $savedMovie = Movie::where('title', $legacyTitle)->first();

                    if ($savedMovie) {
                        $savedMovie->update($movie);
                    } else {
                        $savedMovie = Movie::firstOrCreate(
                            ['title' => $movie['title']],
                            $movie
                        );
                    }
                } else {
                    $savedMovie = Movie::firstOrCreate(
                        ['title' => $movie['title']],
                        $movie
                    );

                    if (! $savedMovie->poster && $movie['poster']) {
                        $savedMovie->update(['poster' => $movie['poster']]);
                    }
                }

                if ($defaultFormat) {
                    if (! $savedMovie->format_id) {
                        $savedMovie->update(['format_id' => $defaultFormat->id]);
                    }

                    $savedMovie->formats()->sync([(int) $savedMovie->format_id]);
                }
            }
        });
    }
}
