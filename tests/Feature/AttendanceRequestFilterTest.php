<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceRequestFilterTest extends TestCase
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
        (require database_path('migrations/2026_09_23_104800_add_is_active_to_users_table.php'))->up();
        (require database_path('migrations/2026_10_07_141117_add_deleted_at_to_employees_table.php'))->up();
        \Illuminate\Support\Facades\Schema::table('employees', function ($table) {
            $table->string('evaluation_type')->nullable();
            $table->unsignedBigInteger('manager_l2_id')->nullable();
            $table->integer('annual_leave_balance')->default(12);
        });
        $this->admin = User::create(['name' => 'Admin', 'email' => 'reqfilter@example.test', 'password' => bcrypt('secret'), 'permission' => 1, 'is_active' => true]);
        $this->employee = DB::table('employees')->insertGetId(['employee_code' => '000789', 'name' => 'Đỗ Văn Nghiệm', 'status' => 'active']);
    }

    public function test_date_filter_matches_request_dates_not_created_at(): void
    {
        // Request with start_date/end_date on 2026-11-19, but created_at on 2026-11-23
        DB::table('attendance_requests')->insert([
            'code' => 'REQ-OLD-TRIP',
            'employee_id' => $this->employee,
            'type' => 'business_trip',
            'start_date' => '2026-11-19 08:30:00',
            'end_date' => '2026-11-19 13:35:00',
            'status' => 'pending',
            'reason' => 'Đi công tác ngày 19/11',
            'created_at' => '2026-11-23 16:44:00',
            'updated_at' => '2026-11-23 16:44:00',
        ]);

        // Request with start_date/end_date on 2026-11-24, created_at on 2026-11-20
        DB::table('attendance_requests')->insert([
            'code' => 'REQ-TARGET-TRIP',
            'employee_id' => $this->employee,
            'type' => 'business_trip',
            'start_date' => '2026-11-24 08:30:00',
            'end_date' => '2026-11-24 17:30:00',
            'status' => 'pending',
            'reason' => 'Đi công tác ngày 24/11',
            'created_at' => '2026-11-20 09:00:00',
            'updated_at' => '2026-11-20 09:00:00',
        ]);

        // Filter 2026-11-23 đến 2026-11-24 (Vietnamese flatpickr separator)
        $response = $this->actingAs($this->admin)->get('/admin/attendance-requests?status=pending&date='.urlencode('2026-11-23 đến 2026-11-24'));
        $response->assertOk()
            ->assertSee('Đi công tác ngày 24/11')
            ->assertDontSee('Đi công tác ngày 19/11');
    }

    public function test_attendance_adjustment_limit_is_counted_per_payroll_cycle(): void
    {
        // Create 2 approved, 1 pending, and 1 rejected adjustment request in cycle 11 (16/10 - 15/11)
        foreach ([
            ['2026-10-20', 'approved'],
            ['2026-11-05', 'approved'],
            ['2026-11-10', 'pending'],
            ['2026-11-11', 'rejected'],
        ] as $idx => [$date, $status]) {
            DB::table('attendance_requests')->insert([
                'code' => 'ADJ-C11-' . $idx,
                'employee_id' => $this->employee,
                'type' => 'attendance_adjustment',
                'start_date' => $date . ' 08:30:00',
                'end_date' => $date . ' 08:30:00',
                'status' => $status,
                'reason' => 'Quên chấm công',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Rejected request in cycle 12 (16/11 - 15/12) should not count either
        DB::table('attendance_requests')->insert([
            'code' => 'ADJ-C12-REJ',
            'employee_id' => $this->employee,
            'type' => 'attendance_adjustment',
            'start_date' => '2026-11-18 08:30:00',
            'end_date' => '2026-11-18 08:30:00',
            'status' => 'rejected',
            'reason' => 'Quên chấm công bị từ chối',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Checking date 2026-11-12 (still in cycle 16/10 - 15/11) -> 0 remaining
        $this->actingAs($this->admin)
            ->getJson('/admin/attendance-requests/check-limit?employee_id=' . $this->employee . '&date=2026-11-12')
            ->assertOk()
            ->assertJson([
                'used' => 3,
                'remaining' => 0,
                'total' => 3,
                'month' => 11,
                'cycle_label' => '16/10 - 15/11/2026',
            ]);

        // Checking date 2026-11-20 (in cycle 16/11 - 15/12) -> full 3 remaining
        $this->actingAs($this->admin)
            ->getJson('/admin/attendance-requests/check-limit?employee_id=' . $this->employee . '&date=2026-11-20')
            ->assertOk()
            ->assertJson([
                'used' => 0,
                'remaining' => 3,
                'total' => 3,
                'month' => 12,
                'cycle_label' => '16/11 - 15/12/2026',
            ]);
    }
}
