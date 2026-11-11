<article class="movie-card h-100">
    <a href="{{ route('movies.show', $movie) }}" class="movie-poster-link text-decoration-none" aria-label="Xem thông tin phim {{ $movie->title }}">
        <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $movie->title }}" class="movie-poster" loading="lazy">
        <span class="badge badge-red">{{ $movie->label ?? ($movie->status === 'coming_soon' ? 'SẮP CHIẾU' : 'ĐANG CHIẾU') }}</span>
        @if ($movie->age_rating)
            <span class="movie-age">{{ $movie->age_rating }}</span>
        @endif
    </a>

    <div class="movie-info">
        <h3 class="movie-title">
            <a href="{{ route('movies.show', $movie) }}" class="text-white text-decoration-none">{{ $movie->title }}</a>
        </h3>

        <p class="movie-meta mb-3">
            <i class="bi bi-film"></i> {{ $movie->genre ?: 'Đang cập nhật' }}
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
                <a href="{{ route('movies.show', $movie) }}#schedule" class="btn btn-red btn-sm">Đặt vé</a>
                @if ($movie->trailer_url)
                    <a href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm" aria-label="Xem trailer phim {{ $movie->title }}">
                        <i class="bi bi-play-fill" aria-hidden="true"></i> Trailer
                    </a>
                @endif
            @endif
        </div>
    </div>
</article>
