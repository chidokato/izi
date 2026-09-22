<?php

$file = 'routes/web.php';
$content = file_get_contents($file);

// Restore customer-inquiries and seo
$badReplacement = <<<EOD
    Route::get('auth/google', [\App\Http\Controllers\GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('auth/google/callback', [\App\Http\Controllers\GoogleController::class, 'handleGoogleCallback'])->name('google.callback');
EOD;

$goodRestore = <<<EOD
    Route::get('customer-inquiries', \$redirectToDashboard)->name('customer-inquiries.index');
    Route::get('seo', \$redirectToDashboard)->name('seo.edit');
EOD;

$content = str_replace($badReplacement, $goodRestore, $content);

// Now remove the dummy route at the bottom
$dummyRoute = <<<EOD
Route::get('auth/google', function() {
    return redirect()->back()->withErrors(['login_identifier' => 'Tính năng đăng nhập Google đang được phát triển. Vui lòng sử dụng phương thức khác.']);
})->name('google.redirect');
EOD;

$realRoutes = <<<EOD
Route::get('auth/google', [\App\Http\Controllers\GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('auth/google/callback', [\App\Http\Controllers\GoogleController::class, 'handleGoogleCallback'])->name('google.callback');
EOD;

$content = str_replace($dummyRoute, $realRoutes, $content);

file_put_contents($file, $content);
echo "Fixed web.php";
