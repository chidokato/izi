<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HolidayTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_12_072056_add_permission_to_users_table.php'))->up();
        (require database_path('migrations/2026_09_16_000007_create_holidays_table.php'))->up();
        (require database_path('migrations/2026_09_23_104800_add_is_active_to_users_table.php'))->up();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'holiday@example.test',
            'password' => bcrypt('secret'),
            'permission' => 1,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_create_range_and_delete_holidays(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/holidays')
            ->assertOk()
            ->assertSee('Cấu hình ngày nghỉ lễ');

        // Create a 2-day holiday range
        $this->actingAs($this->admin)
            ->post('/admin/holidays', [
                'start_date' => '2026-04-30',
                'end_date' => '2026-05-01',
                'name' => 'Nghỉ lễ 30/4 - 1/5',
                'type' => 'holiday',
                'work_value' => 1,
                'note' => 'Nghỉ lễ toàn quốc',
            ])
            ->assertRedirect('/admin/holidays');

        $this->assertDatabaseCount('holidays', 2);
        $this->assertDatabaseHas('holidays', ['holiday_date' => '2026-04-30', 'name' => 'Nghỉ lễ 30/4 - 1/5']);
        $this->assertDatabaseHas('holidays', ['holiday_date' => '2026-05-01', 'name' => 'Nghỉ lễ 30/4 - 1/5']);

        $holiday = Holiday::where('holiday_date', '2026-04-30')->firstOrFail();

        $this->actingAs($this->admin)
            ->delete('/admin/holidays/' . $holiday->id)
            ->assertRedirect('/admin/holidays');

        $this->assertDatabaseCount('holidays', 1);
    }
}
