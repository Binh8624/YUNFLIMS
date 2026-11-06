<div class="movie-card h-100">
    <a href="{{ route('movies.show', $movie) }}" class="text-decoration-none text-white">
        <img src="{{ $movie->poster ?? 'https://placehold.co/600x900/1b1b22/e50914?text=YUNFLIMS' }}" alt="{{ $movie->title }}" class="movie-poster" loading="lazy">
    </a>

    <div class="movie-info">
        <span class="badge badge-red mb-2">{{ $movie->label ?? 'PHIM' }}</span>
        <h3 class="movie-title">
            <a href="{{ route('movies.show', $movie) }}" class="text-white text-decoration-none">{{ $movie->title }}</a>
        </h3>

        <p class="movie-meta mb-3">
            <i class="bi bi-film"></i> {{ $movie->genre }}
            <br>
            <i class="bi bi-clock"></i> {{ $movie->duration }} phút
        </p>

        <div class="d-grid gap-2">
            <a href="{{ route('movies.show', $movie) }}" class="btn btn-outline-light btn-sm">Chi tiết</a>
            <a href="{{ route('movies.show', $movie) }}#schedule" class="btn btn-red btn-sm">Đặt vé</a>
        </div>
    </div>
</div>
