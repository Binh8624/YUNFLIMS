<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount('bookings')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', 'in:customer,admin']]);

        if ($request->user()->is($user) && $data['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Không thể tự hạ quyền admin của tài khoản đang đăng nhập.',
            ]);
        }

        DB::transaction(function () use ($user, $data) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (
                $lockedUser->role === 'admin'
                && $lockedUser->is_active
                && $data['role'] !== 'admin'
                && User::where('role', 'admin')->where('is_active', true)->lockForUpdate()->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'role' => 'Không thể hạ quyền admin cuối cùng đang hoạt động.',
                ]);
            }

            $lockedUser->update($data);
        });

        return back()->with('success', 'Đã cập nhật quyền người dùng.');
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Không thể khóa tài khoản admin đang đăng nhập.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->role === 'admin' && $lockedUser->is_active) {
                $activeAdmins = User::where('role', 'admin')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->count();

                if ($activeAdmins <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'Không thể khóa admin cuối cùng đang hoạt động.',
                    ]);
                }
            }

            $lockedUser->update(['is_active' => ! $lockedUser->is_active]);
            $user->is_active = $lockedUser->is_active;
        });

        return back()->with('success', $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }
}
