<!doctype html>
<html lang="vi" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>Quên mật khẩu | HCC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Trang cấp lại mật khẩu khu vực quản trị HCC." />
    <meta name="author" content="HCC" />
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
                                                <a href="{{ route('login') }}" class="d-inline-block text-white text-decoration-none">
                                                    <h2 class="nhadat-brand mb-0 text-white fw-bold">H<span>CC</span></h2>
                                                </a>
                                            </div>

                                            <div class="mt-auto text-white">
                                                <span class="badge bg-light-subtle text-success text-uppercase mb-3">HỆ THỐNG NỘI BỘ</span>
                                                <h1 class="display-6 fw-semibold text-white mb-3">Khôi phục mật khẩu</h1>
                                                <p class="fs-15 text-white text-opacity-75 mb-4">
                                                    Đừng lo lắng! Nhập địa chỉ email của bạn đã đăng ký trên hệ thống, chúng tôi sẽ khởi tạo mật khẩu ngẫu nhiên mới và gửi trực tiếp về hòm thư của bạn.
                                                </p>

                                                <div class="feature-note mb-4">
                                                    <div class="d-flex align-items-start gap-3">
                                                        <i class="ri-shield-keyhole-line fs-3"></i>
                                                        <div>
                                                            <h5 class="text-white mb-2">Bảo mật tài khoản</h5>
                                                            <p class="mb-0">Hệ thống chỉ gửi mật khẩu mới nếu email của bạn đã tồn tại trong danh sách nhân sự của hệ thống.</p>
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
                                        <div class="text-center">
                                            <div class="avatar-lg mx-auto mb-3">
                                                <div class="avatar-title bg-light text-primary display-5 rounded-circle shadow-sm" style="width: 72px; height: 72px; line-height: 72px; margin: 0 auto;">
                                                    <i class="ri-mail-send-line text-success fs-1"></i>
                                                </div>
                                            </div>
                                            <h5 class="text-primary fw-bold">Quên mật khẩu?</h5>
                                            <p class="text-muted">Nhập email của bạn để nhận mật khẩu ngẫu nhiên mới.</p>
                                        </div>

                                        @if (session('status'))
                                            <div class="alert alert-success alert-dismissible fade show mt-3 shadow-sm" role="alert">
                                                <i class="ri-check-double-line me-2 align-middle fs-5"></i>
                                                {{ session('status') }}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif

                                        @if (session('forgot_error') || $errors->has('email'))
                                            <div class="alert alert-danger alert-dismissible fade show mt-3 shadow-sm" role="alert">
                                                <i class="ri-error-warning-line me-2 align-middle fs-5"></i>
                                                {{ session('forgot_error') ?? $errors->first('email') }}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        @endif

                                        <div class="mt-4">
                                            <form action="{{ route('password.email') }}" method="POST">
                                                @csrf
                                                <div class="mb-4">
                                                    <label for="email" class="form-label fw-semibold">Địa chỉ Email <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Nhập địa chỉ email của bạn..." required autofocus>
                                                    @error('email')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="text-center mt-4">
                                                    <button class="btn btn-success w-100 py-2 fs-15 shadow-sm fw-semibold" type="submit">
                                                        <i class="ri-send-plane-fill align-middle me-1"></i> Gửi mật khẩu mới qua Email
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="mt-5 text-center">
                                            <p class="mb-0 text-muted">Bạn nhớ lại mật khẩu rồi? 
                                                <a href="{{ route('login') }}" class="fw-bold text-primary text-decoration-underline ms-1">
                                                    <i class="ri-arrow-left-line align-middle"></i> Quay lại Đăng nhập
                                                </a>
                                            </p>
                                        </div>
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
                                &copy; <script>document.write(new Date().getFullYear())</script> HCC Admin
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
