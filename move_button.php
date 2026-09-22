<?php

$file = 'resources/views/backend/auth/login.blade.php';
$content = file_get_contents($file);

// Extract the Google button block
$buttonBlockRegex = '/<div class="d-flex align-items-center mt-3">\s*<hr class="flex-grow-1">\s*<span class="px-3 text-muted small text-uppercase">Hoặc đăng nhập bằng<\/span>.*?<\/div>\s*<\/div>/s';

if (preg_match($buttonBlockRegex, $content, $matches)) {
    $buttonHTML = $matches[0];
    
    // Remove it from the bottom
    $content = str_replace($buttonHTML, '', $content);
    
    // Now we want to insert it ABOVE the form
    // The form starts around here:
    // <div class="mt-4">
    //     <form action="{{ route('backend.admin.authenticate') }}" method="POST">
    
    $newHTML = <<<HTML
<div class="mt-3 text-center mb-4">
    <a href="#" onclick="alert('Tính năng đăng nhập Google đang được phát triển. Bạn cần cung cấp Google Client ID và Secret để kích hoạt.')" class="text-decoration-none">
        <button type="button" class="btn btn-light w-100 d-flex justify-content-center align-items-center shadow-sm border rounded-pill" style="height: 48px;">
            <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google Logo" class="me-2" width="24" height="24">
            <span class="fw-semibold text-dark">Đăng nhập bằng GOOGLE</span>
        </button>
    </a>
</div>

<div class="d-flex align-items-center mb-4">
    <hr class="flex-grow-1">
    <span class="px-3 text-muted small text-uppercase">Hoặc đăng nhập bằng</span>
    <hr class="flex-grow-1">
</div>
HTML;
    
    $content = str_replace('<form action="{{ route(\'backend.admin.authenticate\') }}" method="POST">', $newHTML . "\n\n<form action=\"{{ route('backend.admin.authenticate') }}\" method=\"POST\">", $content);
    
    file_put_contents($file, $content);
    echo "Moved button to top.";
} else {
    echo "Button block not found.";
}
