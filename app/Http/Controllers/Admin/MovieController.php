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
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('genre', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.movies.index', compact('movies'));
    }

    public function create()
    {
        return view('admin.movies.create', [
            'formats' => Format::where('is_active', true)->orderBy('name')->get(),
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
            'age_rating' => ['required', 'in:P,T13,T16,T18,C'],
            'status' => 'required|in:now_showing,coming_soon,inactive',
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => ['required', 'integer', 'distinct', 'exists:formats,id,is_active,1'],
        ]);

        $formatIds = $validated['formats'];
        unset($validated['formats']);
        Movie::create($validated)->formats()->sync($formatIds);

        return redirect()
            ->route('admin.movies.index')
            ->with('success', 'Thêm phim thành công!');
    }

    public function edit(Movie $movie)
    {
        return view('admin.movies.edit', [
            'movie' => $movie->load('formats'),
            'formats' => Format::where('is_active', true)->orderBy('name')->get(),
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
            'age_rating' => ['required', 'in:P,T13,T16,T18,C'],
            'status' => 'required|in:now_showing,coming_soon,inactive',
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => ['required', 'integer', 'distinct', 'exists:formats,id,is_active,1'],
        ]);

        $formatIds = $validated['formats'];
        unset($validated['formats']);

        DB::transaction(function () use ($movie, $validated, $formatIds) {
            $lockedMovie = Movie::whereKey($movie->id)->lockForUpdate()->firstOrFail();
            if (
                $lockedMovie->showtimes()
                    ->where('status', 'scheduled')
                    ->whereNotIn('format_id', $formatIds)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'formats' => 'Không thể bỏ định dạng đang được sử dụng bởi suất chiếu đã lên lịch.',
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
}
