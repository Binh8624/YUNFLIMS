<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'member_tier' => 'new',
            'loyalty_points' => 0,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'Đăng ký tài khoản thành công!');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower($credentials['email']))->first();
        $lockKey = 'login-lock:'.Str::lower($credentials['email']).':'.$request->ip();

        if ($user?->locked_until && $user->locked_until->isFuture()) {
            return back()->withErrors([
                'email' => 'Tài khoản tạm thời bị khóa do đăng nhập sai quá nhiều lần.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($lockKey);
            if ($user) {
                $user->locked_until = null;
                $user->save();
            }
            if (! Auth::user()->is_active) {
                Auth::logout();

                RateLimiter::hit($lockKey, 900);
                if (RateLimiter::attempts($lockKey) >= 5 && $user) {
                    $user->locked_until = now()->addMinutes(15);
                    $user->save();
                }

                return back()
                    ->withErrors(['email' => 'Tài khoản này hiện đang bị khóa.'])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            if (Auth::user()->isAdminStaff()) {
                AdminActivityLog::create([
                    'actor_id' => Auth::id(),
                    'action' => 'admin.auth.login',
                    'http_method' => 'POST',
                    'response_status' => 302,
                ]);

                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('home'));
        }

        RateLimiter::hit($lockKey, 900);
        if (RateLimiter::attempts($lockKey) >= 5 && $user) {
            $user->locked_until = now()->addMinutes(15);
            $user->save();
        }

        return back()
            ->withErrors([
                'email' => 'Email hoặc mật khẩu không chính xác.',
            ])
            ->onlyInput('email');
    }

    public function profile()
    {
        $user = Auth::user();
        $favorites = $user->favoriteMovies()->with('format')->latest('movie_favorites.created_at')->limit(6)->get();

        return view('auth.profile', compact('user', 'favorites'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
        ]);

        if (strtolower($validated['email']) !== strtolower($user->email)) {
            $validated['email_verified_at'] = null;
        }
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (isset($validated['email_verified_at'])) {
            $user->email_verified_at = null;
        }
        $user->save();

        return back()->with('success', 'Cập nhật thông tin tài khoản thành công.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        DB::table('sessions')->where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Mật khẩu đã được cập nhật thành công.');
    }

    public function logout(Request $request)
    {
        if ($request->user()?->isAdminStaff()) {
            AdminActivityLog::create([
                'actor_id' => $request->user()->getKey(),
                'action' => 'admin.auth.logout',
                'http_method' => 'POST',
                'response_status' => 302,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Bạn đã đăng xuất.');
    }
}
