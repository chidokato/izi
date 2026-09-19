<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceImporter
{
    public function inspect(array $rows): array
    {
        foreach ($rows as &$r) {
            $r['old'] = null;
            $r['new_employee'] = false;
            $r['duplicate'] = false;
            if ($r['errors']) {
                continue;
            }
            $employee = DB::table('employees')->where('employee_code', $r['code'])->first();
            $r['new_employee'] = ! $employee;
            if ($employee) {
                $department = DB::table('departments')->where('id', $employee->department_id)->value('name');
                if ($employee->name !== $r['name'] || $department !== $r['department']) {
                    $r['errors'][] = 'Tên/phòng ban không khớp hồ sơ nhân viên. Hãy sửa hồ sơ hoặc file trước.';
                }
                if ($employee->status !== 'active') {
                    $r['errors'][] = 'Nhân viên đã ngừng hoạt động.';
                }
                $old = DB::table('attendance_entries')->where('employee_id', $employee->id)->where('work_date', $r['date'])->first();
                $r['old'] = $old ? (array) $old : null;
                $r['duplicate'] = $old && $old->checkin === $r['in'] && $old->checkout === $r['out'] && $old->employee_name === $r['name'] && $old->department_name === $r['department'];
                if (DB::table('daily_attendances')->where('employee_id', $employee->id)->where('work_date', $r['date'])->where('has_request', true)->exists()) {
                    $r['errors'][] = 'Ngày đã có phiếu điều chỉnh; cần đối soát riêng.';
                }
            }
            if ($this->locked($r['date'])) {
                $r['errors'][] = 'Kỳ công đã khóa.';
            }
        }

        return $rows;
    }

    private function locked(string $date): bool
    {
        return DB::table('attendance_periods')->where('status', 'locked')->where(function ($q) use ($date) {
            $q->where(function ($q) use ($date) {
            $q->where('start_date', '<=', $date)->where('end_date', '>=', $date);
            })
              ->orWhere(function ($q) use ($date) {
              $q->where('year', (int) substr($date, 0, 4))->where('month', (int) substr($date, 5, 2));
              });
        })->exists();
    }

    public function commit(int $id, int $user, bool $create, bool $replace): array
    {
        return DB::transaction(function () use ($id, $user, $create, $replace) {
            $batch = DB::table('attendance_imports')->where('id', $id)->where('uploaded_by', $user)->lockForUpdate()->first();
            if (! $batch || $batch->status !== 'pending' || ! $batch->preview || now()->gt($batch->expires_at)) {
                throw ValidationException::withMessages(['file' => 'Bản xem trước đã hết hạn hoặc đã nhập. Hãy tải file lại.']);
            }
            $rows = json_decode($batch->preview, true);
            $success = 0;
            $duplicate = 0;
            $skipped = 0;
            $errors = 0;
            $created = [];
            // Recheck every row inside the transaction. Lock employee rows to serialize imports for the same employee.
            foreach ($rows as $r) {
                if ($r['errors']) {
                    $errors++;

                    continue;
                }
                if (($r['new_employee'] && ! $create) || ($r['old'] && ! $r['duplicate'] && ! $replace)) {
                    $skipped++;

                    continue;
                }
                $period = \Carbon\Carbon::parse($r['date']);
                DB::table('attendance_periods')->insertOrIgnore(['year' => $period->year, 'month' => $period->month, 'start_date' => $period->copy()->startOfMonth()->toDateString(), 'end_date' => $period->copy()->endOfMonth()->toDateString(), 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
                DB::table('attendance_periods')->where('year', (int) substr($r['date'], 0, 4))->where('month', (int) substr($r['date'], 5, 2))->lockForUpdate()->get();
                $employee = DB::table('employees')->where('employee_code', $r['code'])->lockForUpdate()->first();
                $current = $this->inspect([$r])[0];
                if ($current['errors'] || $current['old'] != $r['old'] || ($current['new_employee'] !== $r['new_employee'] && ! isset($created[$r['code']]))) {
                    throw ValidationException::withMessages(['file' => 'Dữ liệu đã thay đổi từ lúc xem trước (dòng '.$r['line'].'). Chưa nhập dòng nào. Hãy tải file lại.']);
                }
                if ($current['duplicate']) {
                    $duplicate++;

                    continue;
                }
                $now = now();
                if (! $employee) {
                    $dept = DB::table('departments')->where('name', $r['department'])->value('id');
                    if (! $dept) {
                        $dept = DB::table('departments')->insertGetId(['name' => $r['department'], 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
                    }
                    $employeeId = DB::table('employees')->insertGetId(['employee_code' => $r['code'], 'name' => $r['name'], 'department_id' => $dept, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
                    $created[$r['code']] = true;
                } else {
                    $employeeId = $employee->id;
                }
                $values = ['employee_name' => $r['name'], 'department_name' => $r['department'], 'checkin' => $r['in'], 'checkout' => $r['out'], 'attendance_import_id' => $id, 'updated_at' => $now];
                if ($r['old']) {
                    $entryId = $r['old']['id'];
                    DB::table('attendance_entries')->where('id', $entryId)->update($values);
                } else {
                    $entryId = DB::table('attendance_entries')->insertGetId($values + ['employee_id' => $employeeId, 'work_date' => $r['date'], 'created_at' => $now]);
                }
                DB::table('audit_logs')->insert(['user_id' => $user, 'action' => $r['old'] ? 'attendance_import_update' : 'attendance_import_create', 'model_type' => 'attendance_entries', 'model_id' => $entryId, 'old_values' => $r['old'] ? json_encode($r['old']) : null, 'new_values' => json_encode($values + ['employee_id' => $employeeId, 'work_date' => $r['date']]), 'created_at' => $now, 'updated_at' => $now]);
                $success++;
            }
            DB::table('attendance_imports')->where('id', $id)->update(['status' => 'completed', 'success_rows' => $success, 'duplicate_rows' => $duplicate, 'error_rows' => $errors, 'processed_rows' => count($rows), 'error_message' => $skipped.' dòng bỏ qua theo lựa chọn.', 'preview' => null, 'started_at' => now(), 'completed_at' => now(), 'updated_at' => now()]);

            return compact('success', 'duplicate', 'skipped', 'errors');
        }, 3);
    }
}
