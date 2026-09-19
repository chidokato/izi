<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();

\ = DB::table('work_schedule_rules')->where('work_schedule_id', 1)->where('day_of_week', 4)->first();
\ = App\Models\AttendanceRequest::find(17);
\ = '2026-09-10';

\ = fn (\) => (int) substr(\, 0, 2) * 3600 + (int) substr(\, 3, 2) * 60 + (int) substr(\, 6, 2);

\ = \(\->start_time);
\ = \(\->end_time);
\ = \->break_start ? \(\->break_start) : null;
\ = \->break_end ? \(\->break_end) : null;

\ = \ . ' 00:00:00';
\ = \ . ' 23:59:59';

\ = max(\->start_date, \);
\ = min(\->end_date, \);

\ = \(substr(\, 11, 8));
\ = \(substr(\, 11, 8));
\ = fn (\, \, \, \) => max(0, min(\, \) - max(\, \));
\ = \(\, \, \, \);
\ = (\ && \) ? \(\, \, \, \) : 0;
\ = (\ - \) / 3600;

echo json_encode([
    'req' => \->start_date,
    'dayStartStr' => \,
    'actStart' => \,
    'actEnd' => \,
    'bHours' => \
]);
