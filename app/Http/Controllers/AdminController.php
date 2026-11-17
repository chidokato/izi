<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
                    'email' => strtolower($employee->employee_code) . '@hcc.local',
                    'password' => Hash::make('idc@123'),
                    'permission' => 3,
                    'employee_id' => $employee->id,
                    'is_active' => true,
                ]);
            }
        }

        if ($user && in_array($user->permission, [1, 2, 3])) {
            if (Auth::attempt(['email' => $user->email, 'password' => $password], $request->boolean('remember'))) {
                if (Hash::check('idc@123', $user->password)) {
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
        
        $user = \App\Models\User::find($setupUserId);
        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $setupUserId,
            'phone' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                function ($attribute, $value, $fail) use ($user) {
                    if (Hash::check($value, $user->password)) {
                        $fail('Mật khẩu mới phải khác mật khẩu mặc định.');
                    }
                }
            ],
        ]);

        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->new_password);
        $user->is_active = true;
        $user->save();
            
        session()->forget('setup_user_id');
        Auth::login($user, true);
        return redirect()->route('backend.admin.dashboard');
    }

    public function showForgotPassword(): View|RedirectResponse
    {
        if (Auth::check() && in_array(Auth::user()->permission, [1, 2, 3])) {
            return redirect()->route('backend.admin.dashboard');
        }

        return view('backend.auth.forgot-password');
    }

    public function sendResetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
        ]);

        $email = trim(strtolower($request->input('email')));

        // 1. Tìm user theo email
        $user = \App\Models\User::with('employee')->where('email', $email)->first();

        // 2. Nếu không tìm thấy trong users, thử tìm trong employees
        if (!$user) {
            $employee = \App\Models\Employee::where('email', $email)->first();
            if ($employee) {
                $user = \App\Models\User::where('employee_id', $employee->id)->first();
                if (!$user) {
                    $user = \App\Models\User::create([
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'password' => Hash::make('idc@123'),
                        'permission' => 3,
                        'employee_id' => $employee->id,
                        'is_active' => true,
                    ]);
                } else {
                    $user->email = $email;
                    $user->save();
                }
                $user->load('employee');
            }
        }

        // 3. Nếu vẫn không có -> Email không tồn tại trên hệ thống
        if (!$user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Địa chỉ email này không tồn tại trên hệ thống.'])
                ->with('forgot_error', 'Địa chỉ email này không tồn tại trên hệ thống.');
        }

        // 4. Kiểm tra tài khoản có bị khóa không
        if (!$user->is_active) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Tài khoản này đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.'])
                ->with('forgot_error', 'Tài khoản này đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.');
        }

        // 5. Tạo mật khẩu ngẫu nhiên (8 ký tự: số + chữ rõ ràng)
        $digits = '23456789';
        $letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $newPassword = substr(str_shuffle($digits), 0, 2) . substr(str_shuffle($letters), 0, 6);
        $newPassword = str_shuffle($newPassword);

        // 6. Cập nhật mật khẩu mới
        $user->password = Hash::make($newPassword);
        $user->save();

        // 7. Gửi email
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $newPassword));
        } catch (\Throwable $e) {
            Log::error('Send reset password mail failed: ' . $e->getMessage());
            return back()
                ->withInput()
                ->withErrors(['email' => 'Hệ thống không thể gửi email lúc này. Vui lòng liên hệ quản trị viên.'])
                ->with('forgot_error', 'Không thể gửi email lúc này. Lỗi: ' . $e->getMessage());
        }

        return redirect()->route('login')
            ->with('status', 'Mật khẩu mới đã được tạo và gửi thành công về email: ' . $user->email . '. Vui lòng kiểm tra hộp thư!');
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
