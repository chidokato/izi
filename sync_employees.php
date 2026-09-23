<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\User;

$employees = Employee::doesntHave('user')->get();
$count = 0;
foreach ($employees as $employee) {
    if (!$employee->employee_code) continue;
    User::create([
        'name' => $employee->name,
        'email' => strtolower($employee->employee_code) . '@izi.local',
        'password' => Hash::make('123456'),
        'permission' => 3,
        'employee_id' => $employee->id,
    ]);
    $count++;
}
echo "Synced $count employees\n";
