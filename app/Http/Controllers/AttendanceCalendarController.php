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
        $employees = \App\Models\Employee::with(['manager', 'managerL2'])->orderBy('name')->orderBy('employee_code')->get();
        if (auth()->check() && !auth()->user()->isAdmin()) {
            $employeeId = auth()->user()->employee_id;
        } else {
            $employeeId = $request->input('employee_id') ?: (auth()->check() && auth()->user()->employee_id ? auth()->user()->employee_id : optional($employees->first())->id);
        }
        $employee = $employeeId ? DB::table('employees as e')->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->where('e.id', $employeeId)->select('e.*', 'd.name as department_name')->first() : null;
        $latest = $employee ? DB::table('attendance_entries')->where('employee_id', $employee->id)->max('work_date') : null;
        if ($request->filled('month')) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $request->input('month'));
        } else {
            $refDate = CarbonImmutable::now('Asia/Ho_Chi_Minh');
            $month = $refDate->day >= 16 ? $refDate->startOfMonth()->addMonth() : $refDate->startOfMonth();
        }
            
        $periodStart = $month->copy()->subMonth()->day(16);
        $periodEnd = $month->copy()->day(15);
        
        $entries = $employee ? DB::table('attendance_entries')->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])->get()->keyBy('work_date') : collect();
            
        $requests = $employee ? DB::table('attendance_requests')
            ->where('employee_id', $employee->id)
            ->where(function($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('start_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                  ->orWhereBetween('end_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            })->get() : collect();

        $schedules = DB::table('work_schedules')->get(['id', 'name', 'status', 'grace_period']);
        $activeSchedules = $schedules->where('status', 'active');
        $defaultSchedule = $activeSchedules->firstWhere('name', 'Giờ hành chính');
        $selectedSchedule = $defaultSchedule ? $defaultSchedule->id : ($activeSchedules->count() === 1 ? $activeSchedules->first()->id : null);
        $assignments = $employee ? DB::table('employee_schedules')->where('employee_id', $employee->id)->orderByDesc('effective_from')->get() : collect();
        $rules = DB::table('work_schedule_rules')->whereIn('work_schedule_id', $schedules->pluck('id'))->get()
            ->keyBy(fn ($rule) => $rule->work_schedule_id.':'.$rule->day_of_week);

        $labels = [
            'complete' => 'Hợp lệ', 'missing' => 'Thiếu một giờ chấm',
            'blank' => 'Vắng', 'overnight' => 'Kiểm tra ca qua đêm', 'no_data' => '',
            'leave' => 'Phép năm/thường', 'adjustment' => 'B.sung công', 'business' => 'Công tác'
        ];
        $totals = array_fill_keys(array_keys($labels), 0);
        $days = [];
        $today = CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString();
        $totalCongThucTe = 0;
        $totalCongTinhLuong = 0;

        for ($date = $periodStart->copy()->startOfWeek(); $date->lte($periodEnd->copy()->endOfWeek()); $date = $date->addDay()) {
            $inMonth = $date->between($periodStart, $periodEnd);
            $dateStr = $date->toDateString();
            $entry = $inMonth ? clone ($entries->get($dateStr) ?? (object)['checkin' => null, 'checkout' => null]) : null;
            if ($entry && !isset($entry->id)) { $entry = null; } // Only keep it if it's a real entry initially
            $status = 'no_data';
            $label = $labels[$status];
            
            // Find applicable approved request
            $req = $requests->first(function($r) use ($dateStr) {
                return $dateStr >= substr($r->start_date, 0, 10) && $dateStr <= substr($r->end_date, 0, 10);
            });

            // Chỉ tính phiếu đã duyệt (hoặc dùng để hiển thị ký hiệu)
            $isApprovedReq = $req && $req->status === 'approved';
            
            $matches = $assignments->filter(fn ($a) => $a->effective_from <= $dateStr && ($a->effective_to === null || $a->effective_to >= $dateStr));
            $scheduleId = $matches->first() ? $matches->first()->work_schedule_id : $selectedSchedule;
            $rule = $matches->count() > 1 ? null : $rules->get($scheduleId.':'.$date->dayOfWeek);
            $schedule = $schedules->firstWhere('id', $scheduleId);
            $gracePeriod = $schedule ? ($schedule->grace_period ?? 0) : 0;
            
            $isNonWorkingDay = ($date->dayOfWeek == 0) || ($rule && !$rule->is_working_day);

            if ($req && $isApprovedReq && $req->type === 'attendance_adjustment' && $inMonth && $rule && !$isNonWorkingDay) {
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
            $workedCong = 0;
            $metrics = null;
            if ($entry && $rule && $inMonth) {
                $metrics = app(\App\Services\AttendanceHourCalculator::class)->calculate($entry, $rule);
                if ($rule->is_working_day && !$isNonWorkingDay) {
                    $cong = $metrics['cong'] ?? 0;
                    $workedCong = $cong;
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
                $label = $status === 'missing' ? ($entry->checkin === null ? 'Thiếu vào' : 'Thiếu ra') : $labels[$status];
            }
            
            $paidCong = 0;
            $dayReq = $req ? clone $req : null;

            // Override status with request if it exists
            if ($dayReq && $inMonth && $isApprovedReq) {
                if ($dayReq->type === 'paid_leave' || $dayReq->type === 'unpaid_leave') {
                    $status = 'leave';
                    $label = ($dayReq->type === 'paid_leave') ? 'Phép năm' : 'Phép thường';
                    if ($dayReq->type === 'paid_leave' && !$isNonWorkingDay) {
                        if ($rule && $rule->is_working_day) {
                            if ($date->dayOfWeek == 6) {
                                $paidCong = 0.5;
                                if ($dayReq->start_session === 'afternoon' && substr($dayReq->start_date, 0, 10) == $dateStr) {
                                    $paidCong = 0;
                                }
                            } else {
                                $paidCong = 1;
                                if (($dayReq->start_session === 'afternoon' && substr($dayReq->start_date, 0, 10) == $dateStr) || ($dayReq->end_session === 'morning' && substr($dayReq->end_date, 0, 10) == $dateStr)) {
                                    $paidCong = 0.5;
                                }
                            }
                        }
                    }
                } elseif ($dayReq->type === 'attendance_adjustment') {
                    if (!$isNonWorkingDay) {
                        $status = 'adjustment';
                        $label = 'B.sung công';
                    }
                } elseif ($dayReq->type === 'business_trip') {
                    $status = 'business';
                    $label = 'Công tác';
                    
                    if ($rule && $rule->is_working_day && !$isNonWorkingDay) {
                        $seconds = fn ($time) => (int) substr($time, 0, 2) * 3600 + (int) substr($time, 3, 2) * 60 + (int) substr($time, 6, 2);
                        
                        $rStart = $seconds($rule->start_time);
                        $rEnd = $seconds($rule->end_time);
                        $bStartSec = $rule->break_start ? $seconds($rule->break_start) : null;
                        $bEndSec = $rule->break_end ? $seconds($rule->break_end) : null;
                        
                        $dayStartStr = $dateStr . ' 00:00:00';
                        $dayEndStr = $dateStr . ' 23:59:59';
                        
                        $reqStartStr = $dayReq->start_date instanceof \Carbon\Carbon ? $dayReq->start_date->format('Y-m-d H:i:s') : (string)$dayReq->start_date;
                        $reqEndStr = $dayReq->end_date instanceof \Carbon\Carbon ? $dayReq->end_date->format('Y-m-d H:i:s') : (string)$dayReq->end_date;
                        
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
                        
                        $totalWorked = ($metrics && isset($metrics['regular_hours'])) ? $metrics['regular_hours'] : 0;
                        $combined = $totalWorked + $bHours;
                        
                        if ($date->dayOfWeek == 6) {
                            $cong = ($combined > 2) ? 0.5 : 0;
                        } else {
                            if ($combined >= 6) {
                                $cong = 1;
                            } elseif ($combined >= 2) {
                                $cong = 0.5;
                            } else {
                                $cong = 0;
                            }
                        }
                        $dayReq->business_hours = $bHours;
                    } elseif (!$isNonWorkingDay) {
                        // Fallback if no rule
                        $dayStartStr = $dateStr . ' 00:00:00';
                        $dayEndStr = $dateStr . ' 23:59:59';
                        $reqStartStr = $dayReq->start_date instanceof \Carbon\Carbon ? $dayReq->start_date->format('Y-m-d H:i:s') : (string)$dayReq->start_date;
                        $reqEndStr = $dayReq->end_date instanceof \Carbon\Carbon ? $dayReq->end_date->format('Y-m-d H:i:s') : (string)$dayReq->end_date;
                        $actStart = max($reqStartStr, $dayStartStr);
                        $actEnd = min($reqEndStr, $dayEndStr);
                        $bHours = 0;
                        if ($actEnd > $actStart) {
                            $bHours = (\Carbon\Carbon::parse($actEnd)->diffInMinutes(\Carbon\Carbon::parse($actStart))) / 60;
                            if ($bHours > 8) $bHours = 8;
                        }
                        $cong = 1;
                        $dayReq->business_hours = $bHours;
                    } else {
                        // Sunday or non-working day: 0 hours, no cong
                        $bHours = 0;
                        $cong = null;
                        $dayReq->business_hours = 0;
                    }
                }
                
                // If it's a paid leave, we also want to display the badge for 'công'
                if ($paidCong > 0 && !$isNonWorkingDay) {
                    if ($cong === null) {
                        $cong = $paidCong;
                    } else {
                        $maxCong = ($rule && $rule->day_of_week == 6) ? 0.5 : 1;
                        $cong = min($maxCong, $cong + $paidCong);
                    }
                }
            }

            if ($isNonWorkingDay) {
                $cong = null;
            }

            if ($inMonth) {
                if (!isset($totals[$status])) $totals[$status] = 0;
                $totals[$status]++;
                
                if ($cong !== null) {
                    if ($req && in_array($req->type, ['paid_leave'])) {
                        $totalCongThucTe += $workedCong;
                    } else {
                        $totalCongThucTe += $cong;
                    }
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
                'request' => $inMonth ? $dayReq : null,
                'cong' => $cong,
                'metrics' => $metrics,
                'grace_period' => $gracePeriod,
                'is_non_working_day' => $isNonWorkingDay,
            ];
        }

        $currentUserEmployee = null;
        if (auth()->check() && auth()->user()->employee_id) {
            $currentUserEmployee = \App\Models\Employee::with(['manager', 'managerL2'])->find(auth()->user()->employee_id);
        }

        return view('backend.attendance.calendar', compact('employees', 'employee', 'currentUserEmployee', 'month', 'periodStart', 'periodEnd', 'days', 'totals', 'labels', 'latest', 'totalCongThucTe', 'totalCongTinhLuong'));
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