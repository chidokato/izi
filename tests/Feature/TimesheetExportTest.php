<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimesheetExportTest extends TestCase
{
    private User $admin;
    private int $employee;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_12_072056_add_permission_to_users_table.php'))->up();
        foreach (glob(database_path('migrations/2026_09_16_*.php')) as $file) {
            (require $file)->up();
        }
        (require database_path('migrations/2026_09_17_000001_create_attendance_entries_table.php'))->up();
        (require database_path('migrations/2026_09_23_104800_add_is_active_to_users_table.php'))->up();
        (require database_path('migrations/2026_10_02_171115_add_grace_period_to_work_schedules_table.php'))->up();
        (require database_path('migrations/2026_10_07_141117_add_deleted_at_to_employees_table.php'))->up();
        \Illuminate\Support\Facades\Schema::table('employees', function ($table) {
            $table->string('evaluation_type')->nullable();
            $table->unsignedBigInteger('manager_l2_id')->nullable();
            $table->integer('annual_leave_balance')->default(12);
        });
        $this->admin = User::create(['name' => 'Admin', 'email' => 'export@example.test', 'password' => bcrypt('secret'), 'permission' => 1, 'is_active' => true]);
        $this->employee = DB::table('employees')->insertGetId(['employee_code' => '000456', 'name' => 'Trần Thị Bình', 'status' => 'active']);
    }

    public function test_timesheet_export_displays_late_early_count_and_minutes_columns(): void
    {
        $schedId = DB::table('work_schedules')->insertGetId([
            'name' => 'Lịch chuẩn',
            'code' => 'STD-EXP',
            'status' => 'active',
            'grace_period' => 10,
        ]);

        for ($dow = 1; $dow <= 5; $dow++) {
            DB::table('work_schedule_rules')->insert([
                'work_schedule_id' => $schedId,
                'day_of_week' => $dow,
                'is_working_day' => 1,
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }

        DB::table('employee_schedules')->insert([
            'employee_id' => $this->employee,
            'work_schedule_id' => $schedId,
            'effective_from' => '2026-01-01',
        ]);

        $batch = DB::table('attendance_imports')->insertGetId(['filename' => 'exp.xlsx', 'uploaded_by' => $this->admin->id]);
        $rows = [
            ['2026-11-17', '08:35:00', '17:30:00'], // within grace period -> 0
            ['2026-11-18', '08:50:00', '17:30:00'], // late 10m -> 1 error, 10m
            ['2026-11-19', '08:25:00', '17:15:00'], // early 15m -> 1 error, 15m
            ['2026-11-20', '08:55:00', '17:20:00'], // late 15m + early 10m -> 2 errors, 25m
        ];
        foreach ($rows as [$date, $in, $out]) {
            DB::table('attendance_entries')->insert([
                'employee_id' => $this->employee,
                'employee_name' => 'Trần Thị Bình',
                'department_name' => 'Văn phòng',
                'work_date' => $date,
                'checkin' => $in,
                'checkout' => $out,
                'attendance_import_id' => $batch,
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/admin/timesheet-export?from=2026-11-16&to=2026-11-20');
        $response->assertOk()
            ->assertSee('Số lần')
            ->assertSee('Số phút')
            ->assertSee('ĐTVS');

        $reportData = $response->viewData('reportData');
        $this->assertCount(1, $reportData);
        $this->assertSame(4, $reportData[0]['late_early_count']);
        $this->assertSame(50, $reportData[0]['late_early_minutes']);

        $exportResponse = $this->actingAs($this->admin)->get('/admin/timesheet-export/export?from=2026-11-16&to=2026-11-20');
        $exportResponse->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $exportResponse->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            'bang-xuat-cong-2026-11-16-den-2026-11-20.xlsx',
            (string) $exportResponse->headers->get('Content-Disposition')
        );
    }
}
