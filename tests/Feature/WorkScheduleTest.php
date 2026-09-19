<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkScheduleTest extends TestCase
{
    private User $admin;

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
        (require database_path('migrations/2026_09_17_000002_add_overtime_to_work_schedule_rules.php'))->up();
        $this->admin = User::create(['name' => 'Admin', 'email' => 'schedule@example.test', 'password' => bcrypt('secret'), 'permission' => 1]);
    }

    private function payload(): array
    {
        $rules = [];
        for ($i = 0; $i <= 6; $i++) {
            $rules[$i] = ['is_working_day' => 0, 'ot_next_day' => 0, 'start_time' => null, 'end_time' => null, 'break_start' => null, 'break_end' => null, 'ot_start' => null, 'ot_end' => null];
        }
        $rules[1] = ['is_working_day' => 1, 'start_time' => '08:00', 'end_time' => '17:00', 'break_start' => '12:00', 'break_end' => '13:00', 'ot_start' => '18:00', 'ot_end' => '20:00', 'ot_next_day' => 0];
        $rules[6]['ot_start'] = '22:00';
        $rules[6]['ot_end'] = '02:00';
        $rules[6]['ot_next_day'] = 1;

        return ['code' => 'HC01', 'name' => 'Hành chính', 'description' => 'Lịch thử', 'status' => 'active', 'rules' => $rules];
    }

    public function test_create_edit_and_list_schedule_with_break_and_overnight_ot(): void
    {
        $this->actingAs($this->admin)->get('/admin/work-schedules/create')->assertOk();
        $data = $this->payload();
        $this->post('/admin/work-schedules', $data)->assertRedirect('/admin/work-schedules');
        $id = DB::table('work_schedules')->value('id');
        $this->assertSame(7, DB::table('work_schedule_rules')->count());
        $this->assertDatabaseHas('work_schedule_rules', ['day_of_week' => 1, 'required_minutes' => 480]);
        $this->assertDatabaseHas('work_schedule_rules', ['day_of_week' => 6, 'required_minutes' => 0, 'ot_next_day' => 1]);
        $this->get('/admin/work-schedules')->assertOk()->assertSee('8 giờ 0 phút')->assertSee('(+1 ngày)');
        $this->get('/admin/work-schedules/'.$id.'/edit')->assertOk()->assertSee('value="08:00"', false);
        $data['rules'][1]['end_time'] = '16:00';
        $data['status'] = 'inactive';
        $this->put('/admin/work-schedules/'.$id, $data)->assertRedirect('/admin/work-schedules');
        $this->assertDatabaseHas('work_schedule_rules', ['day_of_week' => 1, 'required_minutes' => 420]);
        $this->assertDatabaseHas('work_schedules', ['status' => 'inactive']);
        $this->assertSame(7, DB::table('work_schedule_rules')->count());
        $this->assertSame(2, DB::table('audit_logs')->count());
    }

    public function test_invalid_hours_overlaps_and_missing_days_do_not_save(): void
    {
        $this->actingAs($this->admin);
        $variants = [];
        $data = $this->payload();
        $data['rules'][1]['break_end'] = '19:00';
        $variants[] = $data;
        $data = $this->payload();
        $data['rules'][1]['ot_start'] = '16:00';
        $variants[] = $data;
        $data = $this->payload();
        $data['rules'][1]['ot_end'] = null;
        $variants[] = $data;
        $data = $this->payload();
        $data['rules'][6]['ot_next_day'] = 0;
        $variants[] = $data;
        $data = $this->payload();
        unset($data['rules'][2]);
        $variants[] = $data;
        $data = $this->payload();
        $data['rules'][1]['start_time'] = '25:00';
        $variants[] = $data;
        $data = $this->payload();
        $data['rules'][0]['ot_start'] = '22:00';
        $data['rules'][0]['ot_end'] = '09:00';
        $data['rules'][0]['ot_next_day'] = 1;
        $variants[] = $data;
        foreach ($variants as $variant) {
            $this->post('/admin/work-schedules', $variant)->assertSessionHasErrors();
        }
        $this->assertSame(0, DB::table('work_schedules')->count());
        $this->assertSame(0, DB::table('work_schedule_rules')->count());
    }

    public function test_assigned_schedule_is_protected_and_code_must_be_unique(): void
    {
        $this->actingAs($this->admin)->post('/admin/work-schedules', $this->payload())->assertRedirect();
        $id = DB::table('work_schedules')->value('id');
        $this->post('/admin/work-schedules', $this->payload())->assertSessionHasErrors('code');
        $employee = DB::table('employees')->insertGetId(['employee_code' => '001', 'name' => 'An']);
        DB::table('employee_schedules')->insert(['employee_id' => $employee, 'work_schedule_id' => $id, 'effective_from' => '2026-09-01']);
        $data = $this->payload();
        $data['name'] = 'Changed';
        $this->put('/admin/work-schedules/'.$id, $data)->assertSessionHasErrors('name');
        $this->assertDatabaseHas('work_schedules', ['id' => $id, 'name' => 'Hành chính']);
    }

    public function test_schedule_management_requires_admin(): void
    {
        $this->get('/admin/work-schedules')->assertRedirect('/');
        $this->post('/admin/work-schedules', $this->payload())->assertRedirect('/');
        $guest = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => bcrypt('secret'), 'permission' => 6]);
        $this->actingAs($guest)->get('/admin/work-schedules')->assertForbidden();
        $this->post('/admin/work-schedules', $this->payload())->assertForbidden();
    }
}
