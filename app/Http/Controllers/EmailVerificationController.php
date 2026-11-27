<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless((string) $user->getKey() === $id, 403);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('profile')
            ->with('success', 'Địa chỉ email đã được xác nhận.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('success', 'Địa chỉ email của bạn đã được xác nhận.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Đã gửi lại liên kết xác nhận email.');
    }
}
