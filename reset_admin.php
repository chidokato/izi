<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::find(1);
if ($u) {
    $u->password = \Illuminate\Support\Facades\Hash::make('idc@123');
    $u->save();
    echo "Reset user 1 password to idc@123";
}
