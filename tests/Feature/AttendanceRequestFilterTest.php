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
}
