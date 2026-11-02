<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceImporter
{
    public function inspect(array $rows): array
    {
        $codes = [];
        $dates = [];
        foreach ($rows as $r) {
            if (!empty($r['code'])) {
                $codes[(string) $r['code']] = true;
            }
            if (!empty($r['date'])) {
                $dates[(string) $r['date']] = true;
            }
        }

        $codes = array_keys($codes);
        $dates = array_keys($dates);

        $employees = [];
        foreach (array_chunk($codes, 1000) as $chunk) {
            $records = DB::table('employees')->whereIn('employee_code', $chunk)->get();
            foreach ($records as $emp) {
                $employees[(string) $emp->employee_code] = $emp;
            }
        }

        $employeeIds = array_map(fn($e) => $e->id, $employees);

        $oldEntries = [];
        $dailyAdjustments = [];
        if (!empty($employeeIds) && !empty($dates)) {
            $minDate = min($dates);
            $maxDate = max($dates);
            foreach (array_chunk($employeeIds, 1000) as $chunk) {
                $entries = DB::table('attendance_entries')
                    ->whereIn('employee_id', $chunk)
                    ->whereBetween('work_date', [$minDate, $maxDate])
                    ->get();
                foreach ($entries as $e) {
                    $oldEntries[$e->employee_id . '_' . $e->work_date] = $e;
                }

                $adjustments = DB::table('daily_attendances')
                    ->whereIn('employee_id', $chunk)
                    ->whereBetween('work_date', [$minDate, $maxDate])
                    ->where('has_request', true)
                    ->get();
                foreach ($adjustments as $d) {
                    $dailyAdjustments[$d->employee_id . '_' . $d->work_date] = $d;
                }
            }
        }

        $lockedPeriods = DB::table('attendance_periods')->where('status', 'locked')->get();

        foreach ($rows as &$r) {
            $r['old'] = null;
            $r['new_employee'] = false;
            $r['duplicate'] = false;
            if (!empty($r['errors'])) {
                continue;
            }
            $codeStr = (string) $r['code'];
            $employee = $employees[$codeStr] ?? null;
            $r['new_employee'] = ! $employee;
            if ($employee) {
                if ($employee->name !== $r['name']) {
                    $r['errors'][] = "Tên trong file ({$r['name']}) không khớp hồ sơ ({$employee->name}).";
                }
                if ($employee->status !== 'active') {
                    $r['warnings'][] = 'Nhân viên đã ngừng hoạt động (vẫn nhập)';
                }
                $key = $employee->id . '_' . $r['date'];
                $old = $oldEntries[$key] ?? null;
                $r['old'] = $old ? (array) $old : null;
                $r['duplicate'] = $old && $old->checkin === $r['in'] && $old->checkout === $r['out'] && $old->employee_name === $r['name'] && (isset($old->department_id) ? $old->department_id === $employee->department_id : true);
                if (isset($dailyAdjustments[$key])) {
                    $r['errors'][] = 'Ngày đã có phiếu điều chỉnh; cần đối soát riêng.';
                }
            }
            if ($r['date']) {
                $dYear = (int) substr($r['date'], 0, 4);
                $dMonth = (int) substr($r['date'], 5, 2);
                $isLocked = $lockedPeriods->contains(function ($p) use ($r, $dYear, $dMonth) {
                    return ($p->start_date && $p->end_date && $p->start_date <= $r['date'] && $p->end_date >= $r['date'])
                        || ($p->year == $dYear && $p->month == $dMonth);
                });
                if ($isLocked) {
                    $r['errors'][] = 'Kỳ công đã khóa.';
                }
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
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

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

            // 1. Ensure and lock periods for all unique months in the import once
            $months = [];
            foreach ($rows as $r) {
                if (!empty($r['date']) && empty($r['errors'])) {
                    $months[substr($r['date'], 0, 7)] = $r['date'];
                }
            }
            foreach ($months as $ym => $date) {
                $period = \Carbon\Carbon::parse($date);
                DB::table('attendance_periods')->insertOrIgnore([
                    'year' => $period->year,
                    'month' => $period->month,
                    'start_date' => $period->copy()->startOfMonth()->toDateString(),
                    'end_date' => $period->copy()->endOfMonth()->toDateString(),
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('attendance_periods')
                    ->where('year', $period->year)
                    ->where('month', $period->month)
                    ->lockForUpdate()
                    ->get();
            }

            // 2. Lock and retrieve employees
            $validCodes = array_values(array_unique(array_filter(array_column($rows, 'code'))));
            $employees = [];
            foreach (array_chunk($validCodes, 1000) as $chunk) {
                $records = DB::table('employees')->whereIn('employee_code', $chunk)->lockForUpdate()->get();
                foreach ($records as $emp) {
                    $employees[(string) $emp->employee_code] = $emp;
                }
            }

            $auditLogs = [];
            $now = now();

            foreach ($rows as $r) {
                if (!empty($r['errors'])) {
                    $errors++;
                    continue;
                }
                if (($r['new_employee'] && ! $create) || ($r['old'] && ! $r['duplicate'] && ! $replace)) {
                    $skipped++;
                    continue;
                }

                $codeStr = (string) $r['code'];
                $employee = $employees[$codeStr] ?? null;
                $current = $this->inspect([$r])[0];
                if ($current['errors'] || $current['old'] != $r['old'] || ($current['new_employee'] !== $r['new_employee'] && ! isset($created[$codeStr]))) {
                    throw ValidationException::withMessages(['file' => 'Dữ liệu đã thay đổi từ lúc xem trước (dòng '.$r['line'].'). Chưa nhập dòng nào. Hãy tải file lại.']);
                }
                if ($current['duplicate']) {
                    $duplicate++;
                    continue;
                }

                if (! $employee) {
                    $employeeId = DB::table('employees')->insertGetId([
                        'employee_code' => $r['code'],
                        'name' => $r['name'],
                        'department_id' => null,
                        'manager_id' => null,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $created[$codeStr] = true;
                    $employeeDeptId = null;
                    $employees[$codeStr] = (object)[
                        'id' => $employeeId,
                        'employee_code' => $r['code'],
                        'name' => $r['name'],
                        'department_id' => null,
                        'status' => 'active'
                    ];
                } else {
                    $employeeId = $employee->id;
                    $employeeDeptId = $employee->department_id;
                }

                $values = [
                    'employee_name' => $r['name'],
                    'department_id' => $employeeDeptId,
                    'checkin' => $r['in'],
                    'checkout' => $r['out'],
                    'attendance_import_id' => $id,
                    'updated_at' => $now,
                ];

                if ($r['old']) {
                    $entryId = $r['old']['id'];
                    DB::table('attendance_entries')->where('id', $entryId)->update($values);
                } else {
                    $entryId = DB::table('attendance_entries')->insertGetId($values + [
                        'employee_id' => $employeeId,
                        'work_date' => $r['date'],
                        'created_at' => $now,
                    ]);
                }

                $auditLogs[] = [
                    'user_id' => $user,
                    'action' => $r['old'] ? 'attendance_import_update' : 'attendance_import_create',
                    'model_type' => 'attendance_entries',
                    'model_id' => $entryId,
                    'old_values' => $r['old'] ? json_encode($r['old']) : null,
                    'new_values' => json_encode($values + ['employee_id' => $employeeId, 'work_date' => $r['date']]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($auditLogs) >= 500) {
                    DB::table('audit_logs')->insert($auditLogs);
                    $auditLogs = [];
                }

                $success++;
            }

            if (!empty($auditLogs)) {
                DB::table('audit_logs')->insert($auditLogs);
            }

            DB::table('attendance_imports')->where('id', $id)->update([
                'status' => 'completed',
                'success_rows' => $success,
                'duplicate_rows' => $duplicate,
                'error_rows' => $errors,
                'processed_rows' => count($rows),
                'error_message' => $skipped.' dòng bỏ qua theo lựa chọn.',
                'preview' => null,
                'started_at' => now(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return compact('success', 'duplicate', 'skipped', 'errors');
        }, 3);
    }
}
