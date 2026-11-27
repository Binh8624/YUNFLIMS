<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function storeStaff(Request $request)
    {
        Gate::authorize('manage-users');

        $staffRoles = [
            User::ROLE_ADMIN,
            User::ROLE_SUPER_ADMIN,
            User::ROLE_BRANCH_MANAGER,
            User::ROLE_TICKET_SELLER,
            User::ROLE_TICKET_CHECKER,
            User::ROLE_CONTENT_STAFF,
        ];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:'.implode(',', $staffRoles)],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
        ]);

        if (! in_array($request->user()->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true)
            && $data['role'] !== User::ROLE_CUSTOMER) {
            abort(403);
        }

        $cinemaRole = in_array($data['role'], [User::ROLE_BRANCH_MANAGER, User::ROLE_TICKET_SELLER, User::ROLE_TICKET_CHECKER], true);
        if ($cinemaRole && empty($data['cinema_id'])) {
            throw ValidationException::withMessages(['cinema_id' => 'Vai trò chi nhánh bắt buộc chọn rạp.']);
        }

        $user = new User;
        $user->name = $data['name'];
        $user->email = strtolower(trim($data['email']));
        $user->password = Hash::make($data['password']);
        $user->role = $data['role'];
        $user->is_active = true;
        $user->cinema_id = $cinemaRole ? $data['cinema_id'] : null;
        $user->save();

        return back()->with('success', 'Đã tạo tài khoản nhân viên.');
    }
    public function index(Request $request)
    {
        $users = User::withCount('bookings')
            ->withSum([
                'bookings as paid_total' => fn ($query) => $query->where('payment_status', 'paid'),
            ], 'total_amount')
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

    public function show(User $user)
    {
        $user->loadCount('bookings')
            ->loadSum([
                'bookings as paid_total' => fn ($query) => $query->where('payment_status', 'paid'),
            ], 'total_amount');
        $bookings = $user->bookings()
            ->with(['showtime.movie', 'showtime.room.cinema'])
            ->latest()
            ->paginate(10);

        return view('admin.users.show', compact('user', 'bookings'));
    }

    public function updateRole(Request $request, User $user)
    {
        Gate::authorize('manage-users');

        $data = $request->validate([
            'role' => ['required', 'in:'.implode(',', User::allowedRoleValues())],
            'cinema_id' => ['nullable', 'integer', 'exists:cinemas,id'],
        ]);

        $role = User::normalizeRole($data['role']);

        if ($request->user()->is($user) && $role !== User::ROLE_SUPER_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'Không thể tự hạ quyền Super Admin của tài khoản đang đăng nhập.',
            ]);
        }

        DB::transaction(function () use ($user, $role) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $normalizedCurrentRole = User::normalizeRole($lockedUser->role);

            if (
                $normalizedCurrentRole === User::ROLE_SUPER_ADMIN
                && $lockedUser->is_active
                && $role !== User::ROLE_SUPER_ADMIN
                && User::where('role', User::ROLE_SUPER_ADMIN)->where('is_active', true)->lockForUpdate()->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'role' => 'Không thể hạ quyền Super Admin cuối cùng đang hoạt động.',
                ]);
            }

            $lockedUser->role = $role;
            $branchRoles = [
                User::ROLE_BRANCH_MANAGER,
                User::ROLE_TICKET_SELLER,
                User::ROLE_TICKET_CHECKER,
            ];
            if (in_array($role, $branchRoles, true)) {
                if (empty($data['cinema_id'])) {
                    throw ValidationException::withMessages([
                        'cinema_id' => 'Vai trò chi nhánh bắt buộc chọn rạp.',
                    ]);
                }
                $lockedUser->cinema_id = (int) $data['cinema_id'];
            } else {
                $lockedUser->cinema_id = null;
            }
            $lockedUser->save();
        });

        return back()->with('success', 'Đã cập nhật quyền người dùng.');
    }

    public function toggleActive(Request $request, User $user)
    {
        Gate::authorize('manage-users');

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Không thể khóa tài khoản quản trị đang đăng nhập.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $normalizedRole = User::normalizeRole($lockedUser->role);

            if ($normalizedRole === User::ROLE_SUPER_ADMIN && $lockedUser->is_active) {
                $activeSuperAdmins = User::where('role', User::ROLE_SUPER_ADMIN)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->count();

                if ($activeSuperAdmins <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'Không thể khóa Super Admin cuối cùng đang hoạt động.',
                    ]);
                }
            }

            $lockedUser->update(['is_active' => ! $lockedUser->is_active]);
            $user->is_active = $lockedUser->is_active;
        });

        return back()->with('success', $user->is_active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }
}
