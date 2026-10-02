<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

$users = User::all();
$count = 0;

foreach ($users as $user) {
    if (Hash::check('123456', $user->password)) {
        $user->password = Hash::make('idc@123');
        $user->save();
        $count++;
    }
}

echo "Updated $count users from 123456 to idc@123.";
