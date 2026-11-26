<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceCalendarTest extends TestCase
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
        \Illuminate\Support\Facades\Schema::table('employees', function($table) {
            $table->string('evaluation_type')->nullable();
            $table->unsignedBigInteger('manager_l2_id')->nullable();
            $table->integer('annual_leave_balance')->default(12);
        });
        $this->admin = User::create(['name' => 'Admin', 'email' => 'calendar@example.test', 'password' => bcrypt('secret'), 'permission' => 1, 'is_active' => true]);
        $this->employee = DB::table('employees')->insertGetId(['employee_code' => '000123', 'name' => 'Nguyễn Văn An', 'status' => 'active']);
    }

    private function entries(): void
    {
        $batch = DB::table('attendance_imports')->insertGetId(['filename' => 'test.xlsx', 'uploaded_by' => $this->admin->id]);
        foreach ([['08:00:00', '17:00:00'], ['08:00:00', null], [null, null], ['22:00:00', '06:00:00'], ['00:00:00', '08:00:00']] as $index => $hours) {
            DB::table('attendance_entries')->insert(['employee_id' => $this->employee, 'employee_name' => 'Nguyễn Văn An', 'department_name' => 'Văn phòng', 'work_date' => '2026-09-0'.($index + 1), 'checkin' => $hours[0], 'checkout' => $hours[1], 'attendance_import_id' => $batch]);
        }
    }

    public function test_month_shows_hours_and_distinguishes_missing_data_without_calculating_absence(): void
    {
        $this->entries();
        $response = $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee.'&month=2026-09');
        $response->assertOk()->assertSee('Nguyễn Văn An')->assertSee('17:00')->assertSee('Thiếu ra')->assertSee('00:00');
        $response->assertViewHas('totals', ['complete' => 2, 'missing' => 1, 'blank' => 1, 'overnight' => 1, 'no_data' => 26, 'leave' => 0, 'adjustment' => 0, 'business' => 0, 'holiday' => 0]);
        $days = $response->viewData('days');
        $this->assertSame('2026-08-10', $days[0]['date']->toDateString());
        $this->assertCount(42, $days);
        $this->assertFalse($days[0]['in_month']);
        $this->assertSame(0, DB::table('daily_attendances')->count());
        $this->assertSame(5, DB::table('attendance_entries')->count());
    }

    public function test_leap_year_six_week_month_and_employee_isolation(): void
    {
        $this->entries();
        $this->actingAs($this->admin);
        $leap = $this->get('/admin/attendance-calendar?month=2024-02');
        $leap->assertOk()->assertViewHas('totals', fn ($totals) => $totals['no_data'] === 31);
        $six = $this->get('/admin/attendance-calendar?month=2026-08');
        $six->assertOk();
        $this->assertCount(35, $six->viewData('days'));
        $other = DB::table('employees')->insertGetId(['employee_code' => '000999', 'name' => 'Other', 'status' => 'active']);
        $this->get('/admin/attendance-calendar?employee_id='.$other.'&month=2026-09')
            ->assertOk()->assertViewHas('totals', fn ($totals) => $totals['no_data'] === 31 && $totals['complete'] === 0);
        $this->get('/admin/employees')->assertOk();
    }

    public function test_default_month_empty_list_and_access_validation(): void
    {
        $this->get('/admin/attendance-calendar')->assertRedirect('/');
        $this->entries();
        $now = \Carbon\CarbonImmutable::now('Asia/Ho_Chi_Minh');
        $expectedMonth = $now->day >= 16 ? $now->startOfMonth()->addMonth()->format('Y-m') : $now->startOfMonth()->format('Y-m');
        $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee)
            ->assertOk()->assertViewHas('month', fn ($month) => $month->format('Y-m') === $expectedMonth);
        $this->get('/admin/attendance-calendar?month=2026-13')->assertSessionHasErrors('month');
        $this->get('/admin/attendance-calendar?employee_id=99999')->assertSessionHasErrors('employee_id');
        DB::table('attendance_entries')->delete();
        DB::table('employees')->delete();
        $this->get('/admin/attendance-calendar')->assertOk()->assertSee('Chưa có nhân viên');
        $guest = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => bcrypt('secret'), 'permission' => 6, 'is_active' => true]);
        $this->actingAs($guest)->get('/admin/attendance-calendar')->assertForbidden();
    }

    public function test_sunday_and_non_working_days_do_not_calculate_cong_for_multi_day_requests(): void
    {
        $schedId = DB::table('work_schedules')->insertGetId([
            'name' => 'Lịch chuẩn',
            'code' => 'STD',
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
        DB::table('work_schedule_rules')->insert([
            'work_schedule_id' => $schedId,
            'day_of_week' => 6,
            'is_working_day' => 1,
            'start_time' => '08:30:00',
            'end_time' => '12:30:00',
            'break_start' => null,
            'break_end' => null,
        ]);
        DB::table('work_schedule_rules')->insert([
            'work_schedule_id' => $schedId,
            'day_of_week' => 0,
            'is_working_day' => 0,
            'start_time' => null,
            'end_time' => null,
        ]);

        DB::table('employee_schedules')->insert([
            'employee_id' => $this->employee,
            'work_schedule_id' => $schedId,
            'effective_from' => '2026-01-01',
        ]);

        // Insert business trip covering 16/10/2026 to 14/11/2026
        DB::table('attendance_requests')->insert([
            'code' => 'REQ-BT-001',
            'employee_id' => $this->employee,
            'type' => 'business_trip',
            'start_date' => '2026-10-16 08:30:00',
            'end_date' => '2026-11-14 17:30:00',
            'status' => 'approved',
            'reason' => 'Công tác cả tháng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee.'&month=2026-11');
        $response->assertOk();

        // Total valid công should be 23.5 (21 weekdays * 1 + 5 saturdays * 0.5 = 23.5; 4 sundays = 0 công)
        $this->assertEquals(23.5, $response->viewData('totalCongTinhLuong'));
        $this->assertEquals(23.5, $response->viewData('totalCongChuan'));
        $response->assertSee('23,5/23,5');
        $response->assertSee('Công đi làm / Công chuẩn');

        $days = collect($response->viewData('days'))->keyBy(fn ($d) => $d['date']->toDateString());

        // Check Sundays: cong must be null, business_hours must be 0
        foreach (['2026-10-18', '2026-10-25', '2026-11-01', '2026-11-08'] as $sunday) {
            $this->assertTrue($days->has($sunday));
            $this->assertNull($days[$sunday]['cong'], "Sunday {$sunday} must have null cong");
            $this->assertTrue($days[$sunday]['is_non_working_day']);
        }

        // Check Saturday: cong must be 0.5
        $this->assertEquals(0.5, $days['2026-10-17']['cong']);
        $this->assertEquals(4, $days['2026-10-17']['request']->business_hours);

        // Check Weekday: cong must be 1.0
        $this->assertEquals(1.0, $days['2026-10-19']['cong']);
        $this->assertEquals(8, $days['2026-10-19']['request']->business_hours);

        // Verify HTML does not show "1 công" badge on Sunday
        $content = $response->getContent();
        $this->assertStringContainsString('(Nghỉ)', $content);
    }

    public function test_previous_cycle_navigation_with_zero_overlap_business_trip_does_not_error(): void
    {
        $schedId = DB::table('work_schedules')->insertGetId([
            'name' => 'Lịch chuẩn 2',
            'code' => 'STD2',
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

        // Insert business trip where start_date and end_date are on the boundary or zero duration
        DB::table('attendance_requests')->insert([
            'code' => 'REQ-BT-ZERO',
            'employee_id' => $this->employee,
            'type' => 'business_trip',
            'start_date' => '2026-10-21 00:00:00',
            'end_date' => '2026-10-21 00:00:00',
            'status' => 'approved',
            'reason' => 'Zero duration trip',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Navigating to previous month (2026-11) should render without Undefined variable $overlap error
        $response = $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee.'&month=2026-11');
        $response->assertOk();

        $days = collect($response->viewData('days'))->keyBy(fn ($d) => $d['date']->toDateString());
        $this->assertTrue($days->has('2026-10-21'));
        $this->assertEquals(0, $days['2026-10-21']['request']->business_hours);
        $this->assertNull($days['2026-10-21']['cong']);
    }

    public function test_late_and_early_violation_counts_and_total_minutes(): void
    {
        $schedId = DB::table('work_schedules')->insertGetId([
            'name' => 'Lịch chuẩn trễ sớm',
            'code' => 'STD-LE',
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

        $batch = DB::table('attendance_imports')->insertGetId(['filename' => 'late_early.xlsx', 'uploaded_by' => $this->admin->id]);
        // 2026-11-17 (Tue): 08:35 - 17:30 -> within 10m grace period -> 0 late, 0 early
        // 2026-11-18 (Wed): 08:50 - 17:30 -> late 20 - 10 = 10m -> 1 late (10m)
        // 2026-11-19 (Thu): 08:25 - 17:15 -> early 15m -> 1 early (15m)
        // 2026-11-20 (Fri): 08:55 - 17:20 -> late 25 - 10 = 15m, early 10m -> 1 late + 1 early (25m)
        // 2026-11-23 (Mon): missing checkout -> not counted
        $rows = [
            ['2026-11-17', '08:35:00', '17:30:00'],
            ['2026-11-18', '08:50:00', '17:30:00'],
            ['2026-11-19', '08:25:00', '17:15:00'],
            ['2026-11-20', '08:55:00', '17:20:00'],
            ['2026-11-23', '09:00:00', null],
        ];
        foreach ($rows as [$date, $in, $out]) {
            DB::table('attendance_entries')->insert([
                'employee_id' => $this->employee,
                'employee_name' => 'Nguyễn Văn An',
                'department_name' => 'Văn phòng',
                'work_date' => $date,
                'checkin' => $in,
                'checkout' => $out,
                'attendance_import_id' => $batch,
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee.'&month=2026-12');
        $response->assertOk()
            ->assertViewHas('lateCount', 2)
            ->assertViewHas('earlyCount', 2)
            ->assertViewHas('lateMinutes', 25)
            ->assertViewHas('earlyMinutes', 25)
            ->assertViewHas('lateEarlyCount', 4)
            ->assertViewHas('lateEarlyMinutes', 50)
            ->assertSee('4 lỗi / 50p')
            ->assertSee('Đi trễ / Về sớm');
    }

    public function test_configured_holiday_counts_as_one_cong_without_punch(): void
    {
        $schedId = DB::table('work_schedules')->insertGetId([
            'name' => 'Lịch chuẩn lễ',
            'code' => 'STD-HOL',
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

        DB::table('holidays')->insert([
            'holiday_date' => '2026-11-24',
            'name' => 'Ngày lễ Văn hóa',
            'type' => 'holiday',
            'work_value' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee.'&month=2026-12');
        $response->assertOk()
            ->assertSee('Ngày lễ Văn hóa')
            ->assertSee('1 công');

        $this->assertEquals(1.0, $response->viewData('totalCongTinhLuong'));
        $days = collect($response->viewData('days'))->keyBy(fn ($d) => $d['date']->toDateString());
        $this->assertEquals(1.0, $days['2026-11-24']['cong']);
        $this->assertSame('holiday', $days['2026-11-24']['status']);

        // Employee who joined AFTER the holiday (2026-11-25) must NOT receive holiday cong
        $newEmp = DB::table('employees')->insertGetId([
            'employee_code' => '000998',
            'name' => 'Nhân viên mới',
            'status' => 'active',
            'join_date' => '2026-11-25',
        ]);
        DB::table('employee_schedules')->insert([
            'employee_id' => $newEmp,
            'work_schedule_id' => $schedId,
            'effective_from' => '2026-01-01',
        ]);
        $newResponse = $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$newEmp.'&month=2026-12');
        $newResponse->assertOk();
        $this->assertEquals(0.0, $newResponse->viewData('totalCongTinhLuong'));
        $newDays = collect($newResponse->viewData('days'))->keyBy(fn ($d) => $d['date']->toDateString());
        $this->assertNull($newDays['2026-11-24']['cong']);
    }
}