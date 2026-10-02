<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = \App\Models\Employee::where('employee_code', '990200000190')->first();
$u = \App\Models\User::where('employee_id', $e->id)->first();
if ($u) {
    echo "User exists. active: " . $u->is_active . "\n";
    if (\Illuminate\Support\Facades\Hash::check('123456', $u->password)) {
        echo "Pass: 123456";
    } elseif (\Illuminate\Support\Facades\Hash::check('idc@123', $u->password)) {
        echo "Pass: idc@123";
    } else {
        echo "Pass: OTHER";
    }
} else {
    echo "NO USER";
}
