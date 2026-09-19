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
        $this->admin = User::create(['name' => 'Admin', 'email' => 'calendar@example.test', 'password' => bcrypt('secret'), 'permission' => 1]);
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
        $response->assertOk()->assertSee('Nguyễn Văn An')->assertSee('17:00')->assertSee('Thiếu giờ ra')->assertSee('00:00');
        $response->assertViewHas('totals', ['complete' => 2, 'missing' => 1, 'blank' => 1, 'overnight' => 1, 'no_data' => 25]);
        $days = $response->viewData('days');
        $this->assertSame('2026-08-31', $days[0]['date']->toDateString());
        $this->assertCount(35, $days);
        $this->assertFalse($days[0]['in_month']);
        $this->assertSame(0, DB::table('daily_attendances')->count());
        $this->assertSame(5, DB::table('attendance_entries')->count());
    }

    public function test_leap_year_six_week_month_and_employee_isolation(): void
    {
        $this->entries();
        $this->actingAs($this->admin);
        $leap = $this->get('/admin/attendance-calendar?month=2024-02');
        $leap->assertOk()->assertViewHas('totals', fn ($totals) => $totals['no_data'] === 29);
        $six = $this->get('/admin/attendance-calendar?month=2026-08');
        $six->assertOk();
        $this->assertCount(42, $six->viewData('days'));
        $other = DB::table('employees')->insertGetId(['employee_code' => '000999', 'name' => 'Other', 'status' => 'active']);
        $this->get('/admin/attendance-calendar?employee_id='.$other.'&month=2026-09')
            ->assertOk()->assertViewHas('totals', fn ($totals) => $totals['no_data'] === 30 && $totals['complete'] === 0);
        $this->get('/admin/employees')->assertOk()->assertSee('Xem lịch');
    }

    public function test_default_month_empty_list_and_access_validation(): void
    {
        $this->get('/admin/attendance-calendar')->assertRedirect('/');
        $this->entries();
        $this->actingAs($this->admin)->get('/admin/attendance-calendar?employee_id='.$this->employee)
            ->assertOk()->assertViewHas('month', fn ($month) => $month->format('Y-m') === '2026-09');
        $this->get('/admin/attendance-calendar?month=2026-13')->assertSessionHasErrors('month');
        $this->get('/admin/attendance-calendar?employee_id=99999')->assertSessionHasErrors('employee_id');
        DB::table('attendance_entries')->delete();
        DB::table('employees')->delete();
        $this->get('/admin/attendance-calendar')->assertOk()->assertSee('Chưa có nhân viên');
        $guest = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => bcrypt('secret'), 'permission' => 6]);
        $this->actingAs($guest)->get('/admin/attendance-calendar')->assertForbidden();
    }
}