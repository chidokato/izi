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
        if (Auth::check() && in_array(Auth::user()->permission, [1, 2, 3])) {
            if (Auth::user()->is_active) {
                return redirect()->route('backend.admin.dashboard');
            } else {
                Auth::logout();
            }
        }

        return view('backend.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'login_identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login_identifier = $request->input('login_identifier');
        $password = $request->input('password');

        $user = \App\Models\User::where('email', $login_identifier)
            ->orWhere('phone', $login_identifier)
            ->orWhereHas('employee', function($q) use ($login_identifier) {
                $q->where('employee_code', $login_identifier);
            })->first();

        if (!$user) {
            $employee = \App\Models\Employee::where('employee_code', $login_identifier)->first();
            if ($employee) {
                $user = \App\Models\User::create([
                    'name' => $employee->name,
                    'email' => strtolower($employee->employee_code) . '@izi.local',
                    'password' => \Illuminate\Support\Facades\Hash::make('123456'),
                    'permission' => 3,
                    'employee_id' => $employee->id,
                    'is_active' => true,
                ]);
            }
        }

        if ($user && in_array($user->permission, [1, 2, 3])) {
            if (Auth::attempt(['email' => $user->email, 'password' => $password], $request->boolean('remember'))) {
                if (\Illuminate\Support\Facades\Hash::check('123456', $user->password)) {
                    Auth::logout();
                    session(['setup_user_id' => $user->id]);
                    return redirect()->route('login');
                }
                
                if (!$user->is_active) {
                    Auth::logout();
                    return back()
                        ->withErrors(['login_identifier' => 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.'])
                        ->onlyInput('login_identifier');
                }

                $request->session()->regenerate();
                return redirect()->route('backend.admin.dashboard');
            }
        }

        return back()
            ->withErrors(['login_identifier' => 'Thông tin đăng nhập không chính xác hoặc tài khoản không có quyền quản trị.'])
            ->onlyInput('login_identifier');
    }

    public function firstTimeSetup(Request $request): RedirectResponse
    {
        $setupUserId = session('setup_user_id');
        if (!$setupUserId) {
            return redirect()->route('login');
        }

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $setupUserId,
            'phone' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = \App\Models\User::find($setupUserId);
        if ($user) {
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
            $user->is_active = true;
            $user->save();
            
            session()->forget('setup_user_id');
            Auth::login($user, true);
            return redirect()->route('backend.admin.dashboard');
        }

        return redirect()->route('login');
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
