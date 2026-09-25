<?php

use App\Http\Controllers\AdminChatbotController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'login'])->name('login');
Route::redirect('/login', '/');
Route::get('/run-sync-employees', function () {
    $employees = \App\Models\Employee::doesntHave('user')->get();
    $count = 0;
    foreach ($employees as $employee) {
        if (!$employee->employee_code) continue;
        \App\Models\User::create([
            'name' => $employee->name,
            'email' => strtolower($employee->employee_code) . '@izi.local',
            'password' => \Illuminate\Support\Facades\Hash::make('123456'),
            'permission' => 3,
            'employee_id' => $employee->id,
            'is_active' => false,
        ]);
        $count++;
    }
    return "Synced $count employees";
});

Route::get('/sso/izi', [\App\Http\Controllers\IziSsoController::class, 'login'])
    ->middleware('throttle:20,1')
    ->name('sso.izi.login');
Route::get('/admin/login', [AdminController::class, 'login'])->name('backend.admin.login');
Route::post('/admin/login', [AdminController::class, 'authenticate'])
    ->middleware('throttle:5,1')->name('backend.admin.authenticate');
Route::post('/admin/first-time-setup', [AdminController::class, 'firstTimeSetup'])
    ->name('backend.admin.first_time_setup');

Route::prefix('admin')->name('backend.')->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
    Route::controller(AdminController::class)->name('admin.')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::post('logout', 'logout')->name('logout');
        Route::post('uploads/editor-image', fn () => response()->json([
            'url' => asset('admin-assets/images/logo-sm.png'),
        ]))->name('uploads.editor-image');
    });

    $redirectToDashboard = fn () => redirect()->route('backend.admin.dashboard');

    Route::controller(PostController::class)->group(function () {
        Route::get('news', 'index')->name('news.index')->defaults('type', 'news');
        Route::get('news/create', 'create')->name('news.create')->defaults('type', 'news');
        Route::post('news', 'store')->name('news.store')->defaults('type', 'news');
        Route::get('news/{post}/edit', 'edit')->name('news.edit')->defaults('type', 'news');
        Route::put('news/{post}', 'update')->name('news.update')->defaults('type', 'news');
        Route::delete('news/{post}', 'destroy')->name('news.destroy')->defaults('type', 'news');
        Route::patch('news/{post}/toggle-status', 'toggleStatus')->name('news.toggle-status')->defaults('type', 'news');

        Route::get('courses', 'index')->name('courses.index')->defaults('type', 'course');
        Route::get('courses/create', 'create')->name('courses.create')->defaults('type', 'course');
        Route::post('courses', 'store')->name('courses.store')->defaults('type', 'course');
        Route::get('courses/{post}/edit', 'edit')->name('courses.edit')->defaults('type', 'course');
        Route::put('courses/{post}', 'update')->name('courses.update')->defaults('type', 'course');
        Route::delete('courses/{post}', 'destroy')->name('courses.destroy')->defaults('type', 'course');
        Route::patch('courses/{post}/toggle-status', 'toggleStatus')->name('courses.toggle-status')->defaults('type', 'course');
        Route::patch('courses/{post}/toggle-featured', 'toggleFeatured')->name('courses.toggle-featured')->defaults('type', 'course');

        Route::get('products', fn () => redirect()->route('backend.courses.index'))->name('products.index');
        Route::get('products/create', fn () => redirect()->route('backend.courses.create'))->name('products.create');
    });

    Route::get('customer-inquiries', $redirectToDashboard)->name('customer-inquiries.index');
    Route::get('seo', $redirectToDashboard)->name('seo.edit');

    Route::controller(CategoryController::class)->group(function () {
        Route::get('categories', 'index')->name('categories.index');
        Route::get('categories/create', 'create')->name('categories.create');
        Route::post('categories', 'store')->name('categories.store');
        Route::get('categories/{category}/edit', 'edit')->name('categories.edit');
        Route::put('categories/{category}', 'update')->name('categories.update');
        Route::delete('categories/{category}', 'destroy')->name('categories.destroy');
        Route::patch('categories/{category}/toggle-status', 'toggleStatus')->name('categories.toggle-status');
        Route::patch('categories/{category}/sort-order', 'updateSortOrder')->name('categories.update-sort-order');
    });

    Route::controller(MenuController::class)->group(function () {
        Route::get('menus', 'index')->name('menus.index');
        Route::get('menus/create', 'create')->name('menus.create');
        Route::post('menus', 'store')->name('menus.store');
        Route::get('menus/{menu}/edit', 'edit')->name('menus.edit');
        Route::put('menus/{menu}', 'update')->name('menus.update');
        Route::delete('menus/{menu}', 'destroy')->name('menus.destroy');
        Route::patch('menus/{menu}/toggle-status', 'toggleStatus')->name('menus.toggle-status');
        Route::patch('menus/{menu}/sort-order', 'updateSortOrder')->name('menus.update-sort-order');
    });

    Route::controller(SettingController::class)->group(function () {
        Route::get('settings', 'edit')->name('settings.edit');
        Route::put('settings', 'update')->name('settings.update');
    });

    Route::controller(AdminChatbotController::class)->group(function () {
        Route::get('chatbot', 'edit')->name('chatbot.edit');
        Route::put('chatbot', 'update')->name('chatbot.update');
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('users', 'index')->name('users.index');
        Route::get('users/create', 'create')->name('users.create');
        Route::post('users', 'store')->name('users.store');
        Route::get('users/{user}/edit', 'edit')->name('users.edit');
        Route::put('users/{user}', 'update')->name('users.update');
        Route::delete('users/{user}', 'destroy')->name('users.destroy');
        Route::patch('users/{user}/toggle-active', 'toggleActive')->name('users.toggle-active');
    });
});

Route::prefix('admin/attendance')->name('backend.attendance.')->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
    Route::get('/', [\App\Http\Controllers\AttendanceImportController::class, 'index'])->name('index');
    Route::post('/preview', [\App\Http\Controllers\AttendanceImportController::class, 'preview'])->middleware('throttle:10,1')->name('preview');
    Route::get('/imports/{id}', [\App\Http\Controllers\AttendanceImportController::class, 'show'])->whereNumber('id')->name('show');
    Route::post('/imports/{id}/confirm', [\App\Http\Controllers\AttendanceImportController::class, 'confirm'])->whereNumber('id')->name('confirm');
});
Route::get('admin/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.employees.index');
Route::patch('admin/employees/{id}/status', [\App\Http\Controllers\EmployeeController::class, 'changeStatus'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.employees.change-status');
Route::patch('admin/employees/{id}/position', [\App\Http\Controllers\EmployeeController::class, 'changePosition'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.employees.change-position');
Route::patch('admin/employees/{id}/manager', [\App\Http\Controllers\EmployeeController::class, 'changeManager'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.employees.change-manager');
Route::patch('admin/employees/bulk-manager', [\App\Http\Controllers\EmployeeController::class, 'bulkManager'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.employees.bulk-manager');
Route::get('admin/departments', [\App\Http\Controllers\DepartmentController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.departments.index');
Route::get('admin/attendance-calendar', [\App\Http\Controllers\AttendanceCalendarController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.calendar.index');
Route::post('admin/attendance-calendar/swap', [\App\Http\Controllers\AttendanceCalendarController::class, 'swapPunch'])
    ->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.calendar.swap');

Route::get('admin/monthly-settings', [\App\Http\Controllers\MonthlyWorkSettingController::class, 'index'])->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.monthly-settings.index');
Route::post('admin/monthly-settings', [\App\Http\Controllers\MonthlyWorkSettingController::class, 'store'])->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->name('backend.monthly-settings.store');

Route::prefix('admin/work-schedules')->name('backend.schedules.')->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
    Route::get('/', [\App\Http\Controllers\WorkScheduleController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\WorkScheduleController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\WorkScheduleController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\WorkScheduleController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\WorkScheduleController::class, 'update'])->whereNumber('id')->name('update');
});

Route::prefix('admin/attendance-requests')->name('backend.attendance-requests.')->middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
    Route::get('/', [\App\Http\Controllers\AttendanceRequestController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\AttendanceRequestController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\AttendanceRequestController::class, 'store'])->name('store');
    Route::get('/check-limit', [\App\Http\Controllers\AttendanceRequestController::class, 'checkLimit'])->name('check-limit');
    Route::post('/bulk-approve', [\App\Http\Controllers\AttendanceRequestController::class, 'bulkApprove'])->name('bulk-approve');
    Route::get('/{attendanceRequest}/edit', [\App\Http\Controllers\AttendanceRequestController::class, 'edit'])->name('edit');
    Route::put('/{attendanceRequest}', [\App\Http\Controllers\AttendanceRequestController::class, 'update'])->name('update');
    Route::delete('/{attendanceRequest}', [\App\Http\Controllers\AttendanceRequestController::class, 'destroy'])->name('destroy');
});

Route::get('auth/google', [\App\Http\Controllers\GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('auth/google/callback', [\App\Http\Controllers\GoogleController::class, 'handleGoogleCallback'])->name('google.callback');