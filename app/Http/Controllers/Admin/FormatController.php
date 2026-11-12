<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormatController extends Controller
{
    public function index()
    {
        $formats = Format::whereIn('slug', ['2d', '3d', 'imax'])
            ->withCount(['movies', 'rooms', 'showtimes'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.formats.index', compact('formats'));
    }

    public function store(Request $request)
    {
        throw ValidationException::withMessages([
            'name' => 'Hệ thống chỉ hỗ trợ cố định các định dạng 2D, 3D và IMAX.',
        ]);
    }

    public function update(Request $request, Format $format)
    {
        throw ValidationException::withMessages([
            'format' => 'Ba định dạng 2D, 3D và IMAX là cố định, không thể đổi tên hoặc tắt.',
        ]);
    }

    public function destroy(Format $format)
    {
        throw ValidationException::withMessages([
            'format' => 'Không thể xóa định dạng hệ thống.',
        ]);
    }
}
