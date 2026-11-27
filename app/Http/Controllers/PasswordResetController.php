<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Vui lòng đợi trước khi yêu cầu liên kết đặt lại mật khẩu mới.',
            ]);
        }

        return back()->with(
            'status',
            'Nếu email này có tài khoản, hướng dẫn đặt lại mật khẩu sẽ được gửi đến hộp thư.'
        );
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset($credentials, function ($user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Mật khẩu đã được đặt lại. Vui lòng đăng nhập.');
        }

        return back()->withErrors([
            'email' => $status === Password::RESET_THROTTLED
                ? 'Vui lòng đợi trước khi thử lại.'
                : 'Liên kết đặt lại không hợp lệ hoặc đã hết hạn.',
        ]);
    }
}
