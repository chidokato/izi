<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AttendanceFileReader;
use App\Services\AttendanceImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class AttendanceImportTest extends TestCase
{
    private array $files = [];

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
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        } parent::tearDown();
    }

    private function user(int $permission = 1): User
    {
        return User::create(['name' => 'Admin', 'email' => uniqid().'@example.test', 'password' => bcrypt('password'), 'permission' => $permission]);
    }

    private function file(array $rows = [], string $type = 'Xlsx'): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A1', 'CHI TIẾT CHẤM CÔNG');
        $sheet->mergeCells('A1:H1');
        $sheet->fromArray(['Mã N.viên', 'Tên nhân viên', 'Phòng ban', 'Chức vụ', 'Ngày', 'Thứ', 'Vào', 'Ra', 'Công'], null, 'A3');
        $sheet->fromArray($rows ?: [['000123', 'Nguyễn Văn An', 'VĂN PHÒNG', 'Bỏ qua', '9/3/2026', 'Năm', '08:50', '18:01', 999], ['000123', 'Nguyễn Văn An', 'VĂN PHÒNG', 'Bỏ qua', '9/4/2026', 'Sáu', '11:32', null, 999]], null, 'A4', true);
        $path = tempnam(sys_get_temp_dir(), 'attendance_');
        $this->files[] = $path;
        IOFactory::createWriter($book, $type)->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    private function rows(array $rows = []): array
    {
        return app(AttendanceImporter::class)->inspect(app(AttendanceFileReader::class)->read($this->file($rows), 'm/d/Y'));
    }

    private function batch(array $rows, User $user): int
    {
        return DB::table('attendance_imports')->insertGetId(['filename' => 'test.xlsx', 'uploaded_by' => $user->id, 'total_rows' => count($rows), 'status' => 'pending', 'preview' => json_encode($rows), 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_excel_formats_and_numeric_dates_preserve_seven_fields(): void
    {
        foreach (['Xlsx', 'Xls'] as $type) {
            $rows = app(AttendanceFileReader::class)->read($this->file([], $type), 'm/d/Y');
            $this->assertSame('000123', $rows[0]['code']);
            $this->assertSame('2026-09-03', $rows[0]['date']);
            $this->assertSame('08:50:00', $rows[0]['in']);
            $this->assertSame([], $rows[0]['errors']);
            $this->assertNull($rows[1]['out']);
            $this->assertArrayNotHasKey('Công', $rows[0]);
        }
        $rows = $this->rows([['000123', 'An', 'VP', '', Date::PHPToExcel(new \DateTime('2026-09-03')), 'Năm', 0.5, 0.75]]);
        $this->assertSame('2026-09-03', $rows[0]['date']);
        $this->assertSame('12:00:00', $rows[0]['in']);
    }

    public function test_wrong_date_format_invalid_time_and_internal_duplicates_are_rejected(): void
    {
        $r = app(AttendanceFileReader::class)->read($this->file(), 'd/m/Y');
        $this->assertNotEmpty($r[0]['errors']);
        $r = $this->rows([['001', 'An', 'VP', '', '9/3/2026', 'Năm', '25:00', '18:00'], ['002', 'Bình', 'VP', '', '9/3/2026', 'Năm', '08:00', '18:00'], ['002', 'Bình', 'VP', '', '9/3/2026', 'Năm', '09:00', '18:00']]);
        foreach ($r as $row) {
            $this->assertNotEmpty($row['errors']);
        }
    }

    public function test_import_creates_one_employee_for_multiple_dates_and_reimport_is_idempotent(): void
    {
        $user = $this->user();
        $service = app(AttendanceImporter::class);
        $id = $this->batch($this->rows(), $user);
        $this->assertSame(2, $service->commit($id, $user->id, true, false)['success']);
        $this->assertSame(1, DB::table('employees')->count());
        $this->assertSame(2, DB::table('attendance_entries')->count());
        $this->assertSame(0, DB::table('daily_attendances')->count());
        $this->assertSame(0, DB::table('attendance_logs')->count());
        $id = $this->batch($this->rows(), $user);
        $this->assertSame(2, $service->commit($id, $user->id, true, false)['duplicate']);
        $this->assertSame(2, DB::table('audit_logs')->count());
    }

    public function test_changes_require_opt_in_and_are_audited(): void
    {
        $u = $this->user();
        $s = app(AttendanceImporter::class);
        $s->commit($this->batch($this->rows(), $u), $u->id, true, false);
        $changed = [['000123', 'Nguyễn Văn An', 'VĂN PHÒNG', '', '9/3/2026', 'Năm', '09:00', null]];
        $r = $s->commit($this->batch($this->rows($changed), $u), $u->id, false, false);
        $this->assertSame(1, $r['skipped']);
        $s->commit($this->batch($this->rows($changed), $u), $u->id, false, true);
        $this->assertDatabaseHas('attendance_entries', ['work_date' => '2026-09-03', 'checkin' => '09:00:00', 'checkout' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance_import_update']);
    }

    public function test_period_locked_after_preview_rolls_back_everything(): void
    {
        $u = $this->user();
        $s = app(AttendanceImporter::class);
        $id = $this->batch($this->rows(), $u);
        DB::table('attendance_periods')->insert(['year' => 2026, 'month' => 9, 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'status' => 'locked']);
        try {
            $s->commit($id, $u->id, true, false);
            $this->fail('Expected rejection');
        } catch(ValidationException $e) {
            $this->assertSame(0, DB::table('employees')->count());
            $this->assertSame(0, DB::table('attendance_entries')->count());
        }
    }

    public function test_conflicting_profile_is_not_imported(): void
    {
        $u = $this->user();
        $s = app(AttendanceImporter::class);
        $s->commit($this->batch($this->rows(), $u), $u->id, true, false);
        $rows = $this->rows([['000123', 'Tên khác', 'VĂN PHÒNG', '', '9/3/2026', 'Năm', '09:00', '18:00']]);
        $this->assertNotEmpty($rows[0]['errors']);
    }

    public function test_web_upload_preview_confirm_and_access_control(): void
    {
        $this->get('/admin/attendance')->assertRedirect('/');
        $this->actingAs($this->user(6))->get('/admin/attendance')->assertForbidden();
        $u = $this->user();
        $this->actingAs($u)->get('/admin/attendance')->assertOk()->assertSee('Nhập file chấm công Excel');
        $file = new UploadedFile($this->file(), 'attendance.xlsx', null, null, true);
        $this->post('/admin/attendance/preview', ['file' => $file, 'date_format' => 'm/d/Y'])->assertRedirect();
        $id = DB::table('attendance_imports')->max('id');
        $this->get('/admin/attendance/imports/'.$id)->assertOk()->assertSee('000123');
        $this->actingAs($this->user())->get('/admin/attendance/imports/'.$id)->assertNotFound();
        $this->actingAs($u)->post('/admin/attendance/imports/'.$id.'/confirm', ['confirm' => 1, 'create_employees' => 1])->assertRedirect('/admin/attendance');
        $this->get('/admin/attendance')->assertOk()->assertSee('Nguyễn Văn An');
        $this->post('/admin/attendance/imports/'.$id.'/confirm', ['confirm' => 1, 'create_employees' => 1])->assertSessionHasErrors('file');
    }

    public function test_stale_second_row_rolls_back_first_row_update(): void
    {
        $u = $this->user();
        $s = app(AttendanceImporter::class);
        $s->commit($this->batch($this->rows(), $u), $u->id, true, false);
        $rows = $this->rows([
            ['000123', 'Nguyễn Văn An', 'VĂN PHÒNG', '', '9/3/2026', 'Năm', '09:00', '18:00'],
            ['000123', 'Nguyễn Văn An', 'VĂN PHÒNG', '', '9/4/2026', 'Sáu', '09:00', '18:00'],
        ]);
        $id = $this->batch($rows, $u);
        DB::table('attendance_entries')->where('work_date', '2026-09-04')->update(['checkin' => '10:00:00']);
        try {
            $s->commit($id, $u->id, false, true);
            $this->fail('Expected stale preview rejection');
        } catch(ValidationException $e) {
            $this->assertDatabaseHas('attendance_entries', ['work_date' => '2026-09-03', 'checkin' => '08:50:00']);
            $this->assertDatabaseHas('attendance_imports', ['id' => $id, 'status' => 'pending']);
        }
    }

    public function test_expired_or_foreign_preview_cannot_be_committed(): void
    {
        $u = $this->user();
        $other = $this->user();
        $s = app(AttendanceImporter::class);
        $id = $this->batch($this->rows(), $u);
        foreach ([$other->id, $u->id] as $userId) {
            if ($userId === $u->id) {
                DB::table('attendance_imports')->where('id', $id)->update(['expires_at' => now()->subMinute()]);
            }
            try {
                $s->commit($id, $userId, true, false);
                $this->fail('Expected rejection');
            } catch(ValidationException $e) {
                $this->assertSame(0, DB::table('attendance_entries')->count());
            }
        }
    }

    public function test_blank_hours_are_kept_and_new_employees_require_opt_in(): void
    {
        $u = $this->user();
        $s = app(AttendanceImporter::class);
        $rows = $this->rows([['001', 'An', 'VP', '', '9/3/2026', 'Năm', null, null]]);
        $this->assertSame([], $rows[0]['errors']);
        $this->assertSame(1, $s->commit($this->batch($rows, $u), $u->id, false, false)['skipped']);
        $this->assertSame(0, DB::table('employees')->count());
        $s->commit($this->batch($rows, $u), $u->id, true, false);
        $this->assertDatabaseHas('attendance_entries', ['checkin' => null, 'checkout' => null]);
    }

    public function test_hour_columns_use_selected_schedule_and_effective_employee_assignment(): void
    {
        $u = $this->user();
        app(AttendanceImporter::class)->commit($this->batch($this->rows(), $u), $u->id, true, false);
        $schedule = DB::table('work_schedules')->insertGetId(['code' => 'HC', 'name' => 'Hành chính', 'status' => 'active']);
        DB::table('work_schedule_rules')->insert(['work_schedule_id' => $schedule, 'day_of_week' => 4, 'is_working_day' => true, 'start_time' => '08:30:00', 'end_time' => '17:30:00', 'break_start' => '12:30:00', 'break_end' => '13:30:00']);
        $response = $this->actingAs($u)->get('/admin/attendance?from=2026-09-03&to=2026-09-03');
        $response->assertOk()->assertSee('Công (giờ)')->assertSee('Đi muộn (phút)')->assertSee('Về sớm (phút)')->assertSee('7,67');
        $row = $response->viewData('entries')->first();
        $this->assertSame(20, $row->metrics['late_arrival']);
        $this->assertSame(0, $row->metrics['early_departure']);
        $this->get('/admin/attendance?from=2026-09-03&to=2026-09-03&hours_mode=total')->assertOk()->assertSee('8,18');
        $other = DB::table('work_schedules')->insertGetId(['code' => 'HC2', 'name' => 'Lịch riêng', 'status' => 'active']);
        DB::table('work_schedule_rules')->insert(['work_schedule_id' => $other, 'day_of_week' => 4, 'is_working_day' => true, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'break_start' => '12:30:00', 'break_end' => '13:30:00']);
        $response = $this->get('/admin/attendance?from=2026-09-03&to=2026-09-03');
        $this->assertNull($response->viewData('entries')->first()->metrics['regular_hours']);
        DB::table('employee_schedules')->insert(['employee_id' => DB::table('employees')->value('id'), 'work_schedule_id' => $other, 'effective_from' => '2026-09-03', 'effective_to' => '2026-09-03']);
        $response = $this->get('/admin/attendance?from=2026-09-03&to=2026-09-03&schedule_id='.$schedule);
        $response->assertOk()->assertSee('7,00')->assertSee('Lịch riêng (lịch đã gán)');
        $this->assertSame(0, $response->viewData('entries')->first()->metrics['late_arrival']);
        $this->get('/admin/attendance?schedule_id=9999')->assertSessionHasErrors('schedule_id');
    }
}
