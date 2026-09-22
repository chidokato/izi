<?php

$path = 'routes/web.php';
$content = file_get_contents($path);

if (strpos($content, 'auth/google') === false) {
    $content .= "\n\nRoute::get('auth/google', function() {\n";
    $content .= "    return redirect()->back()->withErrors(['login_identifier' => 'Tính năng đăng nhập Google đang được phát triển. Vui lòng sử dụng phương thức khác.']);\n";
    $content .= "})->name('google.redirect');\n";
    file_put_contents($path, $content);
    echo "Added route";
} else {
    echo "Route exists";
}
