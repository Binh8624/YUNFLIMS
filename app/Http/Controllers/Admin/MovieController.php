<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $movies = Movie::query()
            ->with('format')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('genre', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
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
            'formats' => Format::whereIn('slug', ['2d', '3d', 'imax'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'genre' => 'required|string|max:150',
            'duration' => 'required|integer|min:1|max:65535',
            'label' => 'nullable|string|max:50',
            'poster' => 'nullable|url|max:2048',
            'description' => 'nullable|string',
            'trailer_url' => ['nullable', 'url', 'max:2048'],
            'age_rating' => ['required', 'in:P,K,T13,T16,T18,C'],
            'age_rating_verified' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => 'required|in:now_showing,coming_soon,inactive',
            'format_id' => ['required', 'integer', 'exists:formats,id'],
        ]);

        $format = $this->activeCinemaFormat((int) $validated['format_id']);
        $validated['age_rating_verified'] = $request->boolean('age_rating_verified');
        $validated['is_featured'] = $request->boolean('is_featured');
        Movie::create($validated)->formats()->sync([$format->id]);

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Thêm phim thành công!');
    }

    public function edit(Movie $movie)
    {
        return view('admin.movies.edit', [
            'movie' => $movie->load('format'),
            'formats' => Format::whereIn('slug', ['2d', '3d', 'imax'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Movie $movie)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'genre' => 'required|string|max:150',
            'duration' => 'required|integer|min:1|max:65535',
            'label' => 'nullable|string|max:50',
            'poster' => 'nullable|url|max:2048',
            'description' => 'nullable|string',
            'trailer_url' => ['nullable', 'url', 'max:2048'],
            'age_rating' => ['required', 'in:P,K,T13,T16,T18,C'],
            'age_rating_verified' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => 'required|in:now_showing,coming_soon,inactive',
            'format_id' => ['required', 'integer', 'exists:formats,id'],
        ]);

        $format = $this->activeCinemaFormat((int) $validated['format_id']);
        $validated['age_rating_verified'] = $request->boolean('age_rating_verified');
        $validated['is_featured'] = $request->boolean('is_featured');

        DB::transaction(function () use ($movie, $validated, $format) {
            $lockedMovie = Movie::whereKey($movie->id)->lockForUpdate()->firstOrFail();
            if ((int) $lockedMovie->format_id !== (int) $format->id
                && $lockedMovie->showtimes()->where('format_id', '!=', $format->id)->exists()) {
                throw ValidationException::withMessages([
                    'format_id' => 'Không thể đổi định dạng vì phim đã có suất chiếu hoặc vé gắn với định dạng hiện tại.',
                ]);
            }

            $lockedMovie->update($validated);
            $lockedMovie->formats()->sync([$format->id]);
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

    private function activeCinemaFormat(int $formatId): Format
    {
        $format = Format::whereKey($formatId)
            ->whereIn('slug', ['2d', '3d', 'imax'])
            ->where('is_active', true)
            ->first();

        if (! $format) {
            throw ValidationException::withMessages([
                'format_id' => 'Chọn một định dạng hợp lệ: 2D, 3D hoặc IMAX.',
            ]);
        }

        return $format;
    }
}
