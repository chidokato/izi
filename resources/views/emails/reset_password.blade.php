<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấp lại mật khẩu</title>
    <style>
        @media only screen and (max-width: 480px) {
            .email-container {
                width: 100% !important;
                border-radius: 8px !important;
            }
            .header-cell {
                padding: 18px 15px !important;
            }
            .header-title {
                font-size: 18px !important;
            }
            .body-cell {
                padding: 18px 16px !important;
            }
            .pass-box {
                padding: 12px 10px !important;
                margin: 15px 0 !important;
            }
            .pass-code {
                font-size: 22px !important;
                letter-spacing: 2px !important;
            }
            .btn-login {
                padding: 10px 24px !important;
                font-size: 13px !important;
                display: block !important;
                width: auto !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f4f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2d3748; line-height: 1.5; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f4f8; padding: 15px 8px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 500px; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.06);">
                    <!-- Header gọn gàng -->
                    <tr>
                        <td align="center" class="header-cell" style="background: linear-gradient(135deg, #0ab39c 0%, #405189 100%); padding: 22px 20px; text-align: center;">
                            <h1 class="header-title" style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                                HCC - HỆ THỐNG NỘI BỘ
                            </h1>
                            <p style="margin: 4px 0 0 0; color: rgba(255,255,255,0.85); font-size: 13px;">
                                Cấp lại mật khẩu đăng nhập tài khoản
                            </p>
                        </td>
                    </tr>

                    <!-- Nội dung chính -->
                    <tr>
                        <td class="body-cell" style="padding: 22px 20px;">
                            <p style="font-size: 14px; margin: 0 0 10px 0; color: #1a202c;">
                                Xin chào <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="font-size: 13px; margin: 0 0 16px 0; color: #4a5568; line-height: 1.5;">
                                Hệ thống nhận được yêu cầu cấp lại mật khẩu đăng nhập. Mật khẩu ngẫu nhiên mới của bạn đã được khởi tạo:
                            </p>

                            <!-- Hộp hiển thị mật khẩu nhỏ gọn -->
                            <div class="pass-box" style="background-color: #f0fbf8; border: 1.5px dashed #0ab39c; border-radius: 8px; padding: 14px 12px; text-align: center; margin: 16px 0;">
                                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                    MẬT KHẨU MỚI CỦA BẠN
                                </div>
                                <div class="pass-code" style="font-family: 'SFMono-Regular', Consolas, 'Courier New', Courier, monospace; font-size: 23px; font-weight: bold; color: #0ab39c; letter-spacing: 2.5px; word-break: break-all; line-height: 1.2;">
                                    {{ $newPassword }}
                                </div>
                            </div>

                            <!-- Bảng thông tin đăng nhập không bị rớt dòng -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #edf2f7; border-radius: 6px; margin: 14px 0; font-size: 13px;">
                                <tr>
                                    <td style="white-space: nowrap; width: 1%; color: #64748b; font-weight: 600; padding: 8px 10px 8px 12px;">Email:</td>
                                    <td style="color: #1a202c; font-weight: 600; word-break: break-all; padding: 8px 12px 8px 0;">{{ $user->email }}</td>
                                </tr>
                                @if(!empty($user->employee->employee_code))
                                <tr style="border-top: 1px solid #edf2f7;">
                                    <td style="white-space: nowrap; width: 1%; color: #64748b; font-weight: 600; padding: 8px 10px 8px 12px; border-top: 1px solid #edf2f7;">Mã NV:</td>
                                    <td style="color: #1a202c; font-weight: 600; padding: 8px 12px 8px 0; border-top: 1px solid #edf2f7;">{{ $user->employee->employee_code }}</td>
                                </tr>
                                @endif
                            </table>

                            <!-- Lưu ý bảo mật -->
                            <div style="background-color: #fffbeb; border-left: 3px solid #f59e0b; padding: 8px 12px; border-radius: 4px; margin: 14px 0;">
                                <p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.45;">
                                    <strong>Lưu ý:</strong> Bạn nên đổi lại mật khẩu cá nhân sau khi đăng nhập thành công để bảo mật tài khoản.
                                </p>
                            </div>

                            <!-- Nút bấm Đăng nhập -->
                            <div style="text-align: center; margin: 20px 0 10px 0;">
                                <a href="{{ route('login') }}" class="btn-login" style="background-color: #0ab39c; color: #ffffff; text-decoration: none; padding: 10px 28px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-block; box-shadow: 0 2px 5px rgba(10, 179, 156, 0.3);">
                                    Đăng nhập ngay
                                </a>
                            </div>

                            <p style="font-size: 11px; color: #a0aec0; margin: 18px 0 0 0; border-top: 1px solid #edf2f7; padding-top: 12px; text-align: center;">
                                Nếu bạn không yêu cầu, vui lòng bỏ qua thư này hoặc báo cho Quản trị viên.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 12px 15px; text-align: center; font-size: 11px; color: #a0aec0; border-top: 1px solid #edf2f7;">
                            &copy; {{ date('Y') }} HCC Internal System
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
