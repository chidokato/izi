<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = \App\Models\Employee::where('employee_code', '990200000190')->first();
$u = \App\Models\User::where('employee_id', $e->id)->first();
if ($u) {
    $u->delete();
    echo "Deleted user 990200000190 so it can be recreated on next login.";
} else {
    echo "NO USER";
}
