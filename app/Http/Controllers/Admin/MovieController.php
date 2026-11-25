<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $movies = Movie::query()
            ->with(['format', 'formats'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());

                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('genre', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->input('status'))
            )
            ->when(
                $request->input('age_rating_verified') === '0',
                fn ($query) => $query->where('age_rating_verified', false)
            )
            ->when(
                $request->input('age_rating_verified') === '1',
                fn ($query) => $query->where('age_rating_verified', true)
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.movies.index', compact('movies'));
    }

    public function create()
    {
        return view('admin.movies.create', [
            'formats' => $this->availableFormats(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMovie($request);
        $this->validateSafeUrls($validated);

        $formats = $this->activeCinemaFormats($validated['format_ids']);
        $formatIds = $formats->pluck('id')->map(fn ($id) => (int) $id)->all();

        $validated['age_rating_verified'] = $request->boolean('age_rating_verified');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_dubbed'] = $request->boolean('is_dubbed');
        $validated['format_id'] = $formatIds[0];
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title']);

        unset($validated['format_ids'], $validated['gallery_urls']);

        $validated['gallery'] = $this->cleanGallery($request->input('gallery_urls', ''));

        DB::transaction(function () use ($validated, $formatIds) {
            $movie = Movie::create($validated);
            $movie->formats()->sync($formatIds);
        });

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Thêm phim thành công!');
    }

    public function edit(Movie $movie)
    {
        return view('admin.movies.edit', [
            'movie' => $movie->load(['format', 'formats']),
            'formats' => $this->availableFormats(),
        ]);
    }

    public function update(Request $request, Movie $movie)
    {
        $validated = $this->validateMovie($request, $movie);
        $this->validateSafeUrls($validated);

        $formats = $this->activeCinemaFormats($validated['format_ids']);
        $formatIds = $formats->pluck('id')->map(fn ($id) => (int) $id)->all();

        $validated['age_rating_verified'] = $request->boolean('age_rating_verified');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_dubbed'] = $request->boolean('is_dubbed');
        $validated['format_id'] = $formatIds[0];

        $validated['slug'] = $this->uniqueSlug(
            $validated['slug'] ?: $validated['title'],
            $movie->id
        );

        unset($validated['format_ids'], $validated['gallery_urls']);

        $validated['gallery'] = $this->cleanGallery($request->input('gallery_urls', ''));

        DB::transaction(function () use ($movie, $validated, $formatIds) {
            $lockedMovie = Movie::whereKey($movie->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Không cho ẩn phim khi còn suất tương lai đã bán vé.
            if (
                $lockedMovie->status !== 'inactive'
                && $validated['status'] === 'inactive'
            ) {
                $hasPaidFutureBookings = $lockedMovie->showtimes()
                    ->where('starts_at', '>', now())
                    ->whereHas('bookings', function ($query) {
                        $query->where('bookings.status', 'confirmed')
                            ->where('bookings.payment_status', 'paid');
                    })
                    ->exists();

                if ($hasPaidFutureBookings) {
                    throw ValidationException::withMessages([
                        'status' => 'Không thể ẩn phim vì vẫn còn suất chiếu tương lai đã có khách thanh toán. Hãy xử lý các vé đã bán trước.',
                    ]);
                }
            }

            // Không cho bỏ định dạng đang được suất chiếu sử dụng.
            $hasIncompatibleShowtime = $lockedMovie->showtimes()
                ->whereNotIn('format_id', $formatIds)
                ->exists();

            if ($hasIncompatibleShowtime) {
                throw ValidationException::withMessages([
                    'format_ids' => 'Không thể bỏ định dạng đang được suất chiếu sử dụng. Hãy xử lý các suất chiếu liên quan trước.',
                ]);
            }

            $lockedMovie->update($validated);
            $lockedMovie->formats()->sync($formatIds);
        });

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Cập nhật phim thành công!');
    }

    public function destroy(Movie $movie)
    {
        if ($movie->showtimes()->exists()) {
            throw ValidationException::withMessages([
                'movie' => 'Phim đã có suất chiếu. Hãy chuyển trạng thái sang Ẩn để giữ lịch sử.',
            ]);
        }

        $movie->delete();

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Xóa phim thành công!');
    }

    private function validateSafeUrls(array $validated): void
    {
        foreach (['poster', 'trailer_url'] as $field) {
            $url = $validated[$field] ?? null;
            if ($url && ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw ValidationException::withMessages([
                    $field => 'Chỉ được sử dụng liên kết HTTP hoặc HTTPS.',
                ]);
            }
        }
    }

    private function validateMovie(Request $request, ?Movie $movie = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'slug' => [
                'nullable',
                'string',
                'max:220',
                Rule::unique('movies', 'slug')->ignore($movie?->id),
            ],
            'genre' => 'required|string|max:150',
            'duration' => 'required|integer|min:1|max:65535',
            'label' => 'nullable|string|max:50',
            'poster' => 'nullable|url|max:2048',
            'gallery_urls' => 'nullable|string|max:20000',
            'description' => 'nullable|string',
            'trailer_url' => ['nullable', 'url', 'max:2048'],
            'age_rating' => ['required', 'in:P,K,T13,T16,T18,C'],
            'age_rating_verified' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_dubbed' => ['nullable', 'boolean'],
            'release_date' => ['nullable', 'date'],
            'country' => ['nullable', 'string', 'max:100'],
            'director' => ['nullable', 'string', 'max:150'],
            'cast_members' => ['nullable', 'string', 'max:10000'],
            'original_language' => ['nullable', 'string', 'max:100'],
            'subtitle_languages' => ['nullable', 'string', 'max:255'],
            'status' => 'required|in:now_showing,coming_soon,inactive',
            'format_ids' => ['required', 'array', 'min:1'],
            'format_ids.*' => ['required', 'integer', 'distinct', 'exists:formats,id'],
        ], [
            'format_ids.required' => 'Vui lòng chọn ít nhất một định dạng phim.',
            'format_ids.min' => 'Phim phải có ít nhất một định dạng.',
            'format_ids.*.distinct' => 'Không được chọn trùng định dạng.',
            'format_ids.*.exists' => 'Định dạng phim không hợp lệ.',
            'slug.unique' => 'Đường dẫn SEO này đã được sử dụng.',
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'movie';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Movie::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $counter++;
            $slug = $baseSlug . '-' . $counter;
        }

        return $slug;
    }

    private function cleanGallery(?string $galleryUrls): array
    {
        if (! $galleryUrls) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $galleryUrls))
            ->map(fn ($url) => trim($url))
            ->filter(fn ($url) => $url !== '' && filter_var($url, FILTER_VALIDATE_URL))
            ->unique()
            ->values()
            ->all();
    }

    private function availableFormats()
    {
        return Format::whereIn('slug', ['2d', '3d', 'imax'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function activeCinemaFormats(array $formatIds)
    {
        $formatIds = array_values(array_unique(array_map('intval', $formatIds)));

        $formats = Format::whereIn('id', $formatIds)
            ->whereIn('slug', ['2d', '3d', 'imax'])
            ->where('is_active', true)
            ->get();

        if ($formats->count() !== count($formatIds)) {
            throw ValidationException::withMessages([
                'format_ids' => 'Có định dạng không hợp lệ hoặc đã ngừng hoạt động. Vui lòng kiểm tra lại.',
            ]);
        }

        return $formats;
    }
}