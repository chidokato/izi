<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function login(): View|RedirectResponse
    {
        if (Auth::user()?->isAdmin()) {
            return redirect()->route('backend.admin.dashboard');
        }

        return view('backend.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['permission'] = [1, 2, 3];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Thông tin đăng nhập không chính xác hoặc tài khoản không có quyền quản trị.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('backend.admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard(): View
    {
        $stats = [
            'Nhân viên' => DB::table('employees')->count(),
            'Phòng ban' => DB::table('departments')->count(),
            'Tài khoản' => DB::table('users')->count(),
            'Phiếu chờ duyệt' => DB::table('attendance_requests')->where('status', 'pending')->count(),
        ];

        return view('backend.admin.dashboard_content', compact('stats'));
    }
}
