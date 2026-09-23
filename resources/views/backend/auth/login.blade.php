<!doctype html>
<html lang="vi" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>Dang nhap Admin | IZI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Trang dang nhap khu vuc quan tri IZI." />
    <meta name="author" content="IZI" />
    <link rel="shortcut icon" href="{{ asset('admin-assets/images/favicon.ico') }}">

    <script src="{{ asset('admin-assets/js/layout.js') }}"></script>
    <link href="{{ asset('admin-assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        .nhadat-brand {
            letter-spacing: 0.08em;
        }

        .nhadat-brand span {
            color: #0ab39c;
        }

        .auth-one-bg {
            background-image:
                linear-gradient(135deg, rgba(10, 179, 156, 0.92), rgba(64, 81, 137, 0.86)),
                url('{{ asset('images/slider/slider-5.jpg') }}');
            background-size: cover;
            background-position: center;
        }

        .feature-note {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            color: rgba(255, 255, 255, 0.92);
        }
    </style>
</head>
<body>
    <div class="auth-page-wrapper auth-bg-cover py-5 d-flex justify-content-center align-items-center min-vh-100">
        <div class="bg-overlay"></div>

        <div class="auth-page-content overflow-hidden pt-lg-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-10 col-lg-11">
                        <div class="card overflow-hidden border-0 shadow-lg">
                            <div class="row g-0">
                                <div class="col-lg-6">
                                    <div class="p-lg-5 p-4 auth-one-bg h-100">
                                        <div class="bg-overlay opacity-50"></div>
                                        <div class="position-relative h-100 d-flex flex-column">
                                            <div class="mb-4">
                                                <a href="{{ route('login', [], false) }}" class="d-inline-block text-white text-decoration-none">
                                                    <h2 class="nhadat-brand mb-0 text-white fw-bold">I<span>ZI</span></h2>
                                                </a>
                                            </div>

                                            <div class="mt-auto text-white">
                                                <span class="badge bg-light-subtle text-success text-uppercase mb-3">HỆ THỐNG NỘI BỘ</span>
                                                <h1 class="display-6 fw-semibold text-white mb-3">Hệ thống quản lý chấm công & đánh giá nhân sự</h1>
                                                <p class="fs-15 text-white text-opacity-75 mb-4">
                                                    Khu vực này dành riêng cho nhân viên để quản lý chấm công, làm phiếu yêu cầu, đánh giá công việc và các nghiệp vụ vận hành khác.
                                                </p>

                                                <div class="feature-note mb-4">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <i class="ri-shield-check-line fs-3"></i>
                                                        <div>
                                                            <h5 class="text-white mb-2">Bảo mật và an toàn</h5>
                                                            <p class="mb-0">Hệ thống chỉ cho phép các tài khoản nội bộ có phân quyền được phép truy cập và xử lý dữ liệu.</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row text-center g-3">
                                                    <div class="col-4">
                                                        <h4 class="text-white mb-1"><i class="ri-calendar-check-line"></i></h4>
                                                        <p class="mb-0 text-white text-opacity-75 small">Chấm công</p>
                                                    </div>
                                                    <div class="col-4">
                                                        <h4 class="text-white mb-1"><i class="ri-file-list-3-line"></i></h4>
                                                        <p class="mb-0 text-white text-opacity-75 small">Làm phiếu</p>
                                                    </div>
                                                    <div class="col-4">
                                                        <h4 class="text-white mb-1"><i class="ri-star-smile-line"></i></h4>
                                                        <p class="mb-0 text-white text-opacity-75 small">Đánh giá</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="p-lg-5 p-4">
                                        @if(session('setup_user_id'))
                                        <div class="mt-4">
                                            @php
                                                $setupUser = \App\Models\User::with('employee')->find(session('setup_user_id'));
                                            @endphp
                                            <form action="{{ route('backend.admin.first_time_setup', [], false) }}" method="POST">
                                                @csrf
                                                <div class="mb-2">
                                                    <h5 class="mb-1 text-primary">Thông tin đăng nhập</h5>
                                                    <p class="text-muted mb-3">Từ lần sau, bạn có thể dùng 1 trong 3 thông tin dưới đây kết hợp với mật khẩu để đăng nhập vào hệ thống.</p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Mã nhân viên</label>
                                                    <input type="text" class="form-control" value="{{ $setupUser->employee->employee_code ?? 'N/A' }}" disabled readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', session('setup_from_sso') ? $setupUser->email : '') }}" {{ session('setup_from_sso') ? 'readonly style=background-color:#f3f6f9;' : '' }} required placeholder="Nhập email của bạn...">
                                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Số điện thoại</label>
                                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required placeholder="Nhập số điện thoại...">
                                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <hr class="my-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Mật khẩu mới</label>
                                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                                        <input type="password" class="form-control pe-5 password-input @error('new_password') is-invalid @enderror" id="new-password-input" name="new_password" required minlength="6" placeholder="Nhập mật khẩu mới">
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon shadow-none" type="button" id="new-password-addon">
                                                            <i class="ri-eye-fill align-middle"></i>
                                                        </button>
                                                    </div>
                                                    @error('new_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Xác nhận mật khẩu</label>
                                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                                        <input type="password" class="form-control pe-5 password-input" id="new-password-confirmation-input" name="new_password_confirmation" required minlength="6" placeholder="Nhập lại mật khẩu mới">
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon shadow-none" type="button" id="new-password-confirmation-addon">
                                                            <i class="ri-eye-fill align-middle"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="mt-4">
                                                    <button class="btn btn-primary w-100" type="submit">Hoàn tất cấu hình</button>
                                                </div>
                                            </form>
                                        </div>
                                        @else
                                        <div>
                                            <h5 class="text-primary">Đăng nhập quản trị</h5>
                                            <p class="text-muted">Nhập thông tin để vào hệ thống nội bộ IZI.</p>
                                        </div>

                                        <div class="mt-4">
                                            
                                            

                                            <form action="{{ route('backend.admin.authenticate', [], false) }}" method="POST">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="login_identifier" class="form-label">Mã nhân viên, Email hoặc Số điện thoại</label>
                                                    <input type="text" class="form-control @error('login_identifier') is-invalid @enderror" id="login_identifier" name="login_identifier" value="{{ old('login_identifier') }}" placeholder="Nhập mã nhân viên, email hoặc số điện thoại..." autocomplete="username">
                                                    @error('login_identifier')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <div class="float-end">
                                                        <a href="#" class="text-muted">Quên mật khẩu?</a>
                                                    </div>
                                                    <label class="form-label" for="password-input">Mật khẩu</label>
                                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                                        <input type="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" id="password-input" name="password" autocomplete="current-password" placeholder="Nhập mật khẩu">
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon shadow-none" type="button" id="password-addon">
                                                            <i class="ri-eye-fill align-middle"></i>
                                                        </button>
                                                    </div>
                                                    @error('password')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" id="auth-remember-check" name="remember">
                                                    <label class="form-check-label" for="auth-remember-check">Ghi nhớ đăng nhập</label>
                                                </div>

                                                <div class="mt-4">
                                                    <button class="btn btn-success w-100" type="submit">Đăng nhập vào hệ thống</button>
                                                </div>
                                            </form>
                                            <div class="d-flex align-items-center mt-3">
                                                <hr class="flex-grow-1">
                                                <span class="px-3 text-muted small text-uppercase">Hoặc đăng nhập bằng</span>
                                                <hr class="flex-grow-1">
                                            </div>
                                            <div class="mt-3 text-center">
                                                <a href="{{ route('google.redirect', [], false) }}" class="text-decoration-none">
                                                    <button type="button" class="btn btn-light w-100 d-flex justify-content-center align-items-center shadow-sm border rounded-pill" style="height: 48px;">
                                                        <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google Logo" class="me-2" width="24" height="24">
                                                        <span class="fw-semibold text-dark">Đăng nhập bằng GOOGLE</span>
                                                    </button>
                                                </a>
                                            </div>
                                            
                                        </div>
                                        @endif

                                        <div class="mt-4"></div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer border-0">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">
                                &copy; <script>document.write(new Date().getFullYear())</script> IZI Admin
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="{{ asset('admin-assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('admin-assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('admin-assets/js/plugins.js') }}"></script>
    <script src="{{ asset('admin-assets/js/pages/password-addon.init.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if ($errors->has('auth_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Từ chối truy cập',
                text: '{{ $errors->first("auth_error") }}',
                confirmButtonText: 'Đóng'
            });
        });
    </script>
    @endif
</body>
</html>
