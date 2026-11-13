<article class="movie-card h-100">
    <div class="position-relative">
        <a href="{{ route('movies.show', $movie) }}" class="movie-poster-link text-decoration-none" aria-label="Xem thông tin phim {{ $movie->title }}">
            <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $movie->title }}" class="movie-poster" loading="lazy">
            <span class="badge badge-red">{{ $movie->label ?? ($movie->status === 'coming_soon' ? 'SẮP CHIẾU' : 'ĐANG CHIẾU') }}</span>
            <span class="movie-age" title="{{ $movie->age_rating_verified ? 'Phân loại đã xác minh' : 'Phân loại đang chờ xác minh' }}">{{ $movie->ageRatingLabel() }}</span>
        </a>
        @auth
            <form method="POST" action="{{ route('movies.favorite', $movie) }}" class="position-absolute top-0 end-0 p-2">
                @csrf
                <button type="submit" class="btn btn-sm rounded-circle {{ auth()->user()->isFavoriteMovie($movie) ? 'btn-light text-danger' : 'btn-dark border border-light text-light' }}" aria-label="{{ auth()->user()->isFavoriteMovie($movie) ? 'Bỏ thích phim' : 'Thêm phim yêu thích' }}">
                    <i class="bi {{ auth()->user()->isFavoriteMovie($movie) ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                </button>
            </form>
        @endauth
    </div>

    <div class="movie-info">
        <h3 class="movie-title">
            <a href="{{ route('movies.show', $movie) }}" class="text-white text-decoration-none">{{ $movie->title }}</a>
        </h3>

        <p class="movie-meta mb-3">
            <i class="bi bi-film"></i> {{ implode(' · ', array_slice($movie->genre_labels, 0, 2)) ?: 'Đang cập nhật' }}
            <br><span class="badge bg-secondary">{{ $movie->format?->name ?? 'Định dạng chưa cập nhật' }}</span>
            <br>
            @if ($movie->duration)
                <i class="bi bi-clock"></i> {{ $movie->duration }} phút
            @endif
            @if ($movie->status === 'coming_soon')
                <br><span>Ngày khởi chiếu chưa cập nhật</span>
            @endif
            @if (($movie->reviews_count ?? 0) > 0)
                <br>
                <span class="movie-rating"><i class="bi bi-star-fill" aria-hidden="true"></i> {{ number_format((float) $movie->reviews_avg_rating, 1) }}/5</span>
            @endif
        </p>

        <div class="movie-card-actions">
            <a href="{{ route('movies.show', $movie) }}" class="btn btn-outline-light btn-sm">
                Chi tiết
            </a>
            @if ($movie->status === 'coming_soon')
                @if ($movie->trailer_url)
                    <a href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-red btn-sm" aria-label="Xem trailer phim {{ $movie->title }}">
                        <i class="bi bi-play-fill" aria-hidden="true"></i> Trailer
                    </a>
                @else
                    <span class="badge text-bg-secondary align-self-center">Sắp chiếu</span>
                @endif
            @else
                <a href="{{ route('showtimes.index', ['movie_id' => $movie->id]) }}" class="btn btn-red btn-sm">Mua vé</a>
                @if ($movie->trailer_url)
                    <a href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm" aria-label="Xem trailer phim {{ $movie->title }}">
                        <i class="bi bi-play-fill" aria-hidden="true"></i> Trailer
                    </a>
                @endif
            @endif
        </div>
    </div>
</article>
