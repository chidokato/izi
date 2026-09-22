<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IziSsoController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $payload = $request->filled('token')
            ? $this->decodeLegacyToken((string) $request->query('token'))
            : $this->decodeSignedPayload($request);

        $validator = Validator::make($payload, [
            'id' => ['nullable', 'numeric'],
            'email' => ['nullable', 'email', 'max:255'],
            'employee_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timestamp' => ['required', 'integer'],
        ], [
            'employee_code.required' => 'Tài khoản của bạn chưa được cập nhật Mã Nhân Viên. Vui lòng cập nhật bên Indochine trước khi vào IZI.',
        ]);

        if ($validator->fails()) {
            abort(403, 'Lỗi dữ liệu SSO: ' . $validator->errors()->first());
        }

        $data = $validator->validated();

        abort_if(abs(now()->timestamp - (int) $data['timestamp']) > 300, 403, 'Liên kết SSO đã hết hạn.');

        $employeeCode = trim($data['employee_code']);

        // 1. Đồng bộ Employee
        $employee = \App\Models\Employee::where('employee_code', $employeeCode)->first();
        if (!$employee) {
            $employee = \App\Models\Employee::create([
                'employee_code' => $employeeCode,
                'name' => $data['name'],
                'status' => 'active',
            ]);
        }

        // 2. Đồng bộ User
        $user = User::where('employee_id', $employee->id)->first();
        
        if (!$user && !empty($data['email'])) {
            // Tìm theo email xem có user nào chưa được gán employee_id không
            $user = User::where('email', strtolower($data['email']))->first();
        }

        if (!$user) {
            // Tạo mới user
            $user = User::create([
                'name' => $data['name'],
                'email' => !empty($data['email']) ? strtolower($data['email']) : ($employeeCode . '@sso.local'),
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make(Str::random(16)),
                'permission' => 3, // Cấp quyền Moderator để vào được hệ thống
                'employee_id' => $employee->id,
            ]);
        } else {
            // Cập nhật lại thông tin
            $user->fill([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $user->phone,
                'employee_id' => $employee->id,
            ]);
            $user->save();
        }

        // Đảm bảo user có quyền truy cập
        if (!in_array($user->permission, [1, 2, 3])) {
            $user->permission = 3;
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('backend.admin.dashboard');
    }

    private function decodeLegacyToken(string $token): array
    {
        $sourceAppKey = (string) config('services.izi_sso.source_app_key');
        abort_if($sourceAppKey === '', 503, 'Chưa cấu hình khóa SSO từ web nguồn.');

        if (Str::startsWith($sourceAppKey, 'base64:')) {
            $sourceAppKey = base64_decode(Str::after($sourceAppKey, 'base64:'), true) ?: '';
        }

        abort_if($sourceAppKey === '', 503, 'Khóa SSO từ web nguồn không hợp lệ.');

        try {
            $json = (new Encrypter($sourceAppKey, config('app.cipher')))->decryptString($token);
        } catch (DecryptException $exception) {
            abort(403, 'Token SSO không hợp lệ.');
        }

        $payload = json_decode($json, true);
        abort_unless(is_array($payload), 403, 'Dữ liệu SSO không hợp lệ.');

        return $payload;
    }

    private function decodeSignedPayload(Request $request): array
    {
        $encodedPayload = (string) $request->query('payload');
        $signature = (string) $request->query('signature');
        $secret = (string) config('services.izi_sso.shared_secret');

        abort_if($secret === '', 503, 'SSO IZI chưa được cấu hình.');
        abort_unless($encodedPayload !== '' && $signature !== '', 403, 'Yêu cầu SSO không hợp lệ.');
        abort_unless(
            hash_equals(hash_hmac('sha256', $encodedPayload, $secret), $signature),
            403,
            'Chữ ký SSO không hợp lệ.'
        );

        $json = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
        abort_if($json === false, 403, 'Dữ liệu SSO không hợp lệ.');

        $payload = json_decode($json, true);
        abort_unless(is_array($payload), 403, 'Dữ liệu SSO không hợp lệ.');

        return $payload;
    }
}