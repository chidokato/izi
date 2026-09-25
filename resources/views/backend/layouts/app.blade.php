<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="default" data-theme-colors="default">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin') | IZI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Admin dashboard" name="description" />
    <meta content="IZI" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('admin-assets/images/favicon.ico') }}">
    <script src="{{ asset('admin-assets/js/layout.js') }}"></script>
    <link href="{{ asset('admin-assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/backend-admin.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('admin-assets/css/my-custom.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
</head>
<body>
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex">
                        <div class="navbar-brand-box horizontal-logo">
                            <a href="{{ route('backend.admin.dashboard') }}" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{ asset('admin-assets/images/logo-sm.png') }}" alt="" height="50">
                                </span>
                                <span class="logo-lg">
                                    <span class="fs-2 fw-bold">IZI</span>
                                </span>
                            </a>
                            <a href="{{ route('backend.admin.dashboard') }}" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="{{ asset('admin-assets/images/logo-sm.png') }}" alt="" height="50">
                                </span>
                                <span class="logo-lg">
                                    <span class="fs-2 fw-bold">IZI</span>
                                </span>
                            </a>
                        </div>

                        <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger material-shadow-none" id="topnav-hamburger-icon">
                            <span class="hamburger-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>

                        <form class="app-search d-none d-md-block">
                            <div class="position-relative">
                                <input type="text" class="form-control" placeholder="Search..." autocomplete="off">
                                <span class="mdi mdi-magnify search-widget-icon"></span>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="dropdown d-md-none topbar-head-dropdown header-item">
                            <button type="button" class="btn btn-icon btn-topbar material-shadow-none btn-ghost-secondary rounded-circle" id="page-header-search-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-search fs-22"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-search-dropdown">
                                <form class="p-3">
                                    <div class="form-group m-0">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search...">
                                            <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="dropdown ms-sm-3 header-item topbar-user">
                            <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-flex align-items-center">
                                    <img class="rounded-circle header-profile-user" src="{{ auth()->user()?->avatar ? asset(auth()->user()->avatar) : asset('admin-assets/images/users/avatar-1.jpg') }}" alt="Header Avatar" style="object-fit: cover;">
                                    <span class="text-start ms-xl-2">
                                        <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ auth()->user()?->name ?? 'Admin' }}</span>
                                        <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">Administrator</span>
                                    </span>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Welcome {{ auth()->user()?->name ?? 'Admin' }}!</h6>
                                @if(auth()->check() && auth()->user()->isAdmin())
                                <a class="dropdown-item" href="{{ route('backend.users.index') }}">
                                    <i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
                                    <span class="align-middle">User</span>
                                </a>
                                @endif
                                <a class="dropdown-item" href="{{ route('backend.admin.logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                                    <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                                    <span class="align-middle">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="app-menu navbar-menu">
            <div class="navbar-brand-box">
                <a href="{{ route('backend.admin.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('admin-assets/images/logo-sm.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <span class="fs-2 fw-bold">IZI</span>
                    </span>
                </a>
                <a href="{{ route('backend.admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('admin-assets/images/logo-sm.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <span class="fs-2 fw-bold">IZI</span>
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar">
                <div class="container-fluid">
                    <div id="two-column-menu"></div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span>Menu</span></li>
                        @php

                            $isAdmin = auth()->check() && auth()->user()->isAdmin();
                            $menuItems = [];
                            
                            if ($isAdmin) {
                                $menuItems[] = ['label' => 'Chấm công', 'icon' => 'ri-calendar-check-line', 'route' => 'backend.attendance.index', 'active' => 'backend.attendance.*'];
                            }
                            
                            $menuItems[] = ['label' => 'Lịch chấm công', 'icon' => 'ri-calendar-2-line', 'route' => 'backend.calendar.index', 'active' => 'backend.calendar.*'];
                            $menuItems[] = ['label' => 'Phiếu yêu cầu', 'icon' => 'ri-file-text-line', 'route' => 'backend.attendance-requests.index', 'active' => 'backend.attendance-requests.*'];

                            if ($isAdmin) {
                                $menuItems[] = ['label' => 'Nhân viên', 'icon' => 'ri-team-line', 'route' => 'backend.employees.index', 'active' => 'backend.employees.*'];
                                $menuItems[] = ['label' => 'Phòng ban', 'icon' => 'ri-building-line', 'route' => 'backend.departments.index', 'active' => 'backend.departments.*'];
                                $menuItems[] = ['label' => 'Cấu hình tháng', 'icon' => 'ri-settings-4-line', 'route' => 'backend.monthly-settings.index', 'active' => 'backend.monthly-settings.*'];
                                $menuItems[] = ['label' => 'Giờ làm việc', 'icon' => 'ri-time-line', 'route' => 'backend.schedules.index', 'active' => 'backend.schedules.*'];
                                $menuItems[] = ['label' => 'Tài khoản', 'icon' => 'ri-user-3-line', 'route' => 'backend.users.index', 'active' => 'backend.users.*'];
                            }
                        @endphp
                        @foreach ($menuItems as $item)
                            <li class="nav-item">
                                <a class="nav-link menu-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                    <i class="{{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="sidebar-background"></div>
        </div>

        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">@yield('page_title', 'Dashboard')</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="{{ route('backend.admin.dashboard') }}">Admin</a></li>
                                        <li class="breadcrumb-item active">@yield('breadcrumb', 'Dashboard')</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    @yield('content')
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            {{ now()->year }} © IZI.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Admin powered by Velzon
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <form id="admin-logout-form" action="{{ route('backend.admin.logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <div
        id="backend-app-config"
        class="d-none"
        data-upload-url="{{ route('backend.admin.uploads.editor-image') }}"
        data-success-message="{{ session('success', '') }}"
        data-error-message="{{ session('error', '') }}"
        data-validation-message="{{ $errors->any() ? collect($errors->all())->implode(' | ') : '' }}"
    ></div>

    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>

    <script src="{{ asset('admin-assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/super-build/ckeditor.js"></script>
    <script src="{{ asset('admin-assets/js/customc-keditor.js') }}"></script>
    <script src="{{ asset('admin-assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('admin-assets/js/app.js') }}"></script>
    <script src="{{ asset('admin-assets/js/backend-admin.js') }}"></script>
    @stack('scripts')
</body>
</html>
