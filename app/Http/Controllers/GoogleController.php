<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        $google = Socialite::driver('google');

        if ($request->boolean('select_account')) {
            $google = $google->with([
                'prompt' => 'select_account',
            ]);
        }

        return $google->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['auth_error' => 'Đăng nhập Google thất bại: ' . $e->getMessage()]);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['auth_error' => 'Hệ thống lưu hành nội bộ. Vui lòng liên hệ bộ phận nhân sự hoặc liên hệ trực tiếp kỹ thuật (zalo: 0977572947) để được hỗ trợ.']);
        }

        // Cập nhật avatar nếu đã có tài khoản nhưng chưa có avatar
        if (!$user->avatar && $googleUser->getAvatar()) {
            $user->avatar = $googleUser->getAvatar();
            $user->save();
        }

        if (!$user->is_active) {
            return redirect()->route('login')->withErrors(['auth_error' => 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.']);
        }

        if (in_array($user->permission, [1, 2, 3])) {
            Auth::login($user, true);
            return redirect()->route('backend.admin.dashboard');
        }

        return redirect()->route('login')->withErrors(['auth_error' => 'Truy cập bị từ chối: Tài khoản của bạn không có quyền Quản trị viên.']);
    }
}
