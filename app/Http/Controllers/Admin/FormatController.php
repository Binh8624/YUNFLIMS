<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormatController extends Controller
{
    public function index()
    {
        $formats = Format::withCount(['movies', 'rooms', 'showtimes'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.formats.index', compact('formats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:formats,name'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['name']);

        if (Format::where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Tên định dạng này có slug đã tồn tại.',
            ]);
        }

        Format::create($data);

        return back()->with('success', 'Đã thêm định dạng phim.');
    }

    public function update(Request $request, Format $format)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:formats,name,' . $format->id],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['name']);

        if (Format::where('slug', $data['slug'])->whereKeyNot($format->id)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Tên định dạng này có slug đã tồn tại.',
            ]);
        }

        if (
            ! $data['is_active']
            && $format->showtimes()->where('status', 'scheduled')->where('starts_at', '>', now())->exists()
        ) {
            throw ValidationException::withMessages([
                'is_active' => 'Không thể tắt định dạng đang được dùng cho suất chiếu tương lai; hãy hủy hoặc đổi các suất đó trước.',
            ]);
        }

        $format->update($data);

        return back()->with('success', 'Đã cập nhật định dạng phim.');
    }

    public function destroy(Format $format)
    {
        if ($format->showtimes()->exists() || $format->rooms()->exists() || $format->movies()->exists()) {
            throw ValidationException::withMessages([
                'format' => 'Định dạng đang được sử dụng. Hãy tạm ngưng thay vì xóa.',
            ]);
        }

        $format->delete();

        return back()->with('success', 'Đã xóa định dạng phim.');
    }
}
