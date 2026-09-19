<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceCalendarController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable|integer|exists:employees,id',
            'month' => ['nullable', 'date_format:Y-m', 'regex:/^(19|20|21)\d{2}-(0[1-9]|1[0-2])$/'],
        ]);
        $employees = DB::table('employees')->orderBy('name')->orderBy('employee_code')->get(['id', 'name', 'employee_code']);
        $employeeId = $request->input('employee_id') ?: optional($employees->first())->id;
        $employee = $employeeId ? DB::table('employees as e')->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $employeeId)->select('e.*', 'd.name as department_name')->first() : null;
        $latest = $employee ? DB::table('attendance_entries')->where('employee_id', $employee->id)->max('work_date') : null;
        $month = $request->filled('month')
            ? CarbonImmutable::createFromFormat('!Y-m', $request->input('month'))
            : CarbonImmutable::parse($latest ?: now('Asia/Ho_Chi_Minh'))->startOfMonth();
        $entries = $employee ? DB::table('attendance_entries')->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])->get()->keyBy('work_date') : collect();
            
        $requests = $employee ? DB::table('attendance_requests')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function($q) use ($month) {
                $q->whereBetween('start_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
                  ->orWhereBetween('end_date', [$month->toDateString(), $month->endOfMonth()->toDateString()]);
            })->get() : collect();

        $schedules = DB::table('work_schedules')->get(['id', 'name', 'status']);
        $activeSchedules = $schedules->where('status', 'active');
        $defaultSchedule = $activeSchedules->firstWhere('name', 'Giờ hành chính');
        $selectedSchedule = $defaultSchedule ? $defaultSchedule->id : ($activeSchedules->count() === 1 ? $activeSchedules->first()->id : null);
        $assignments = $employee ? DB::table('employee_schedules')->where('employee_id', $employee->id)->orderByDesc('effective_from')->get() : collect();
        $rules = DB::table('work_schedule_rules')->whereIn('work_schedule_id', $schedules->pluck('id'))->get()
            ->keyBy(fn ($rule) => $rule->work_schedule_id.':'.$rule->day_of_week);

        $labels = [
            'complete' => 'Đủ giờ vào/ra', 'missing' => 'Thiếu một giờ chấm',
            'blank' => 'Chưa có giờ chấm', 'overnight' => 'Kiểm tra ca qua đêm', 'no_data' => 'Chưa có dữ liệu',
            'leave' => 'Nghỉ phép', 'adjustment' => 'Bổ sung công', 'business' => 'Công tác'
        ];
        $totals = array_fill_keys(array_keys($labels), 0);
        $days = [];
        $today = CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString();
        $totalCongThucTe = 0;
        $totalCongTinhLuong = 0;

        for ($date = $month->startOfWeek(); $date->lte($month->endOfMonth()->endOfWeek()); $date = $date->addDay()) {
            $inMonth = $date->format('Y-m') === $month->format('Y-m');
            $dateStr = $date->toDateString();
            $entry = $inMonth ? clone ($entries->get($dateStr) ?? (object)['checkin' => null, 'checkout' => null]) : null;
            if ($entry && !isset($entry->id)) { $entry = null; } // Only keep it if it's a real entry initially
            $status = 'no_data';
            $label = $labels[$status];
            
            // Find applicable approved request
            $req = $requests->first(function($r) use ($dateStr) {
                return $dateStr >= substr($r->start_date, 0, 10) && $dateStr <= substr($r->end_date, 0, 10);
            });

            $matches = $assignments->filter(fn ($a) => $a->effective_from <= $dateStr && ($a->effective_to === null || $a->effective_to >= $dateStr));
            $scheduleId = $matches->first() ? $matches->first()->work_schedule_id : $selectedSchedule;
            $rule = $matches->count() > 1 ? null : $rules->get($scheduleId.':'.$date->dayOfWeek);
            
            if ($req && $req->type === 'attendance_adjustment' && $inMonth && $rule) {
                if (!$entry) $entry = (object)['checkin' => null, 'checkout' => null];
                
                $adjSession = $req->start_session;
                
                if ($adjSession === 'morning' || $adjSession === 'full') {
                    $entry->checkin = substr($rule->start_time, 0, 5);
                }
                if ($adjSession === 'afternoon' || $adjSession === 'full') {
                    $entry->checkout = substr($rule->end_time, 0, 5);
                }
            }
            
            $cong = null;
            $metrics = null;
            if ($entry && $rule && $inMonth) {
                $metrics = app(\App\Services\AttendanceHourCalculator::class)->calculate($entry, $rule);
                $regular = $metrics['regular_hours'] ?? null;
                if ($regular !== null) {
                    if ($regular == 8) $cong = 1;
                    elseif ($regular == 4) $cong = 0.5;
                    else $cong = 0;
                }
            }

            if ($entry) {
                if ($entry->checkin === null && $entry->checkout === null) {
                    $status = 'blank';
                } elseif ($entry->checkin === null || $entry->checkout === null) {
                    $status = 'missing';
                } elseif ($entry->checkout < $entry->checkin) {
                    $status = 'overnight';
                } else {
                    $status = 'complete';
                }
                $label = $status === 'missing' ? ($entry->checkin === null ? 'Thiếu giờ vào' : 'Thiếu giờ ra') : $labels[$status];
            }
            
            $paidCong = 0;
            // Override status with request if it exists
            if ($req && $inMonth) {
                if ($req->type === 'paid_leave' || $req->type === 'unpaid_leave') {
                    $status = 'leave';
                    $label = 'Nghỉ phép';
                    if ($req->type === 'paid_leave') {
                        $paidCong = 1;
                        if (($req->start_session === 'afternoon' && substr($req->start_date, 0, 10) == $dateStr) || ($req->end_session === 'morning' && substr($req->end_date, 0, 10) == $dateStr)) {
                            $paidCong = 0.5;
                        }
                    }
                } elseif ($req->type === 'attendance_adjustment') {
                    $status = 'adjustment';
                    $label = 'Bổ sung công';
                } elseif ($req->type === 'business_trip') {
                    $status = 'business';
                    $label = 'Công tác';
                    
                    if ($rule && $rule->is_working_day) {
                        $seconds = fn ($time) => (int) substr($time, 0, 2) * 3600 + (int) substr($time, 3, 2) * 60 + (int) substr($time, 6, 2);
                        
                        $rStart = $seconds($rule->start_time);
                        $rEnd = $seconds($rule->end_time);
                        $bStartSec = $rule->break_start ? $seconds($rule->break_start) : null;
                        $bEndSec = $rule->break_end ? $seconds($rule->break_end) : null;
                        
                        $dayStartStr = $dateStr . ' 00:00:00';
                        $dayEndStr = $dateStr . ' 23:59:59';
                        
                        $reqStartStr = $req->start_date instanceof \Carbon\Carbon ? $req->start_date->format('Y-m-d H:i:s') : (string)$req->start_date;
                        $reqEndStr = $req->end_date instanceof \Carbon\Carbon ? $req->end_date->format('Y-m-d H:i:s') : (string)$req->end_date;
                        
                        $actStart = max($reqStartStr, $dayStartStr);
                        $actEnd = min($reqEndStr, $dayEndStr);
                        
                        $bHours = 0;
                        if ($actEnd > $actStart) {
                            $t1 = $seconds(substr($actStart, 11, 8));
                            $t2 = $seconds(substr($actEnd, 11, 8));
                            
                            $overlap = fn ($a, $b, $c, $d) => max(0, min($b, $d) - max($a, $c));
                            $workOverlap = $overlap($t1, $t2, $rStart, $rEnd);
                            $breakOverlap = ($bStartSec && $bEndSec) ? $overlap($t1, $t2, $bStartSec, $bEndSec) : 0;
                            
                            $bHours = ($workOverlap - $breakOverlap) / 3600;
                        }
                        
                        $totalWorked = ($metrics && isset($metrics['total_hours'])) ? $metrics['total_hours'] : 0;
                        $combined = $totalWorked + $bHours;
                        
                        $targetHours = ($rule->required_minutes ?? ($rule->day_of_week == 6 ? 240 : 480)) / 60;
                        
                        if ($combined >= $targetHours - 0.1) {
                            $cong = ($targetHours <= 4) ? 0.5 : 1;
                        } elseif ($combined >= ($targetHours / 2) - 0.1) {
                            $cong = 0.5;
                        } else {
                            $cong = 0;
                        }
                    } else {
                        // Fallback if no rule
                        $dayStartStr = $dateStr . ' 00:00:00';
                        $dayEndStr = $dateStr . ' 23:59:59';
                        $reqStartStr = $req->start_date instanceof \Carbon\Carbon ? $req->start_date->format('Y-m-d H:i:s') : (string)$req->start_date;
                        $reqEndStr = $req->end_date instanceof \Carbon\Carbon ? $req->end_date->format('Y-m-d H:i:s') : (string)$req->end_date;
                        $actStart = max($reqStartStr, $dayStartStr);
                        $actEnd = min($reqEndStr, $dayEndStr);
                        $bHours = 0;
                        if ($actEnd > $actStart) {
                            $bHours = (\Carbon\Carbon::parse($actEnd)->diffInMinutes(\Carbon\Carbon::parse($actStart))) / 60;
                            if ($bHours > 8) $bHours = 8;
                        }
                        $cong = 1;
                    }
                    $req->business_hours = $bHours ?? 8;
                }
                
                // If it's a paid leave, we also want to display the badge for 'công'
                if ($paidCong > 0 && $cong === null) {
                    $cong = $paidCong;
                }
            }

            if ($inMonth) {
                if (!isset($totals[$status])) $totals[$status] = 0;
                $totals[$status]++;
                
                if ($cong !== null) {
                    $totalCongThucTe += ($req && in_array($req->type, ['paid_leave'])) ? 0 : $cong;
                    $totalCongTinhLuong += $cong;
                }
            }
            $days[] = [
                'date' => clone $date,
                'in_month' => $inMonth,
                'today' => $dateStr === $today,
                'entry' => $entry,
                'status' => $status,
                'label' => $label,
                'request' => $inMonth ? $req : null,
                'cong' => $cong,
                'metrics' => $metrics,
            ];
        }

        return view('backend.attendance.calendar', compact('employees', 'employee', 'month', 'days', 'totals', 'labels', 'latest', 'totalCongThucTe', 'totalCongTinhLuong'));
    }

    public function swapPunch(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:attendance_entries,id'
        ]);

        $entry = DB::table('attendance_entries')->where('id', $request->input('id'))->first();
        if ($entry) {
            DB::table('attendance_entries')->where('id', $entry->id)->update([
                'checkin' => $entry->checkout,
                'checkout' => $entry->checkin,
            ]);
            return response()->json([
                'success' => true,
                'checkin' => $entry->checkout !== null ? substr($entry->checkout, 0, 5) : '—',
                'checkout' => $entry->checkin !== null ? substr($entry->checkin, 0, 5) : '—'
            ]);
        }
        return response()->json(['success' => false], 404);
    }
}