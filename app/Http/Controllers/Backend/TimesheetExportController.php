<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\AttendanceHourCalculator;

class TimesheetExportController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $departmentId = $request->input('department');
        
        $now = Carbon::now();
        // default 16 last month to 15 this month
        $defaultFrom = $now->copy()->subMonth()->format('Y-m-16');
        $defaultTo = $now->format('Y-m-15');
        
        $from = $request->input('from', $defaultFrom);
        $to = $request->input('to', $defaultTo);
        
        $startDate = Carbon::parse($from);
        $endDate = Carbon::parse($to);
        
        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        $employeesQuery = DB::table('employees as e')
            ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
            ->select('e.id', 'e.employee_code', 'e.name', 'e.position', 'e.join_date', 'e.status', 'd.name as department_name')
            ->whereIn('e.status', ['active', 'probation']);
            
        if ($q) {
            $employeesQuery->where(function($query) use ($q) {
                $query->where('e.employee_code', 'like', '%'.$q.'%')
                      ->orWhere('e.name', 'like', '%'.$q.'%');
            });
        }
        if ($departmentId) {
            $employeesQuery->where('e.department_id', $departmentId);
        }
        
        $employees = $employeesQuery->orderBy('e.employee_code')->get();
        $employeeIds = $employees->pluck('id');

        $entries = DB::table('attendance_entries')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('work_date', [$from, $to])
            ->get();
            
        $entriesGrouped = $entries->groupBy('employee_id');

        $schedules = DB::table('work_schedules')->get(['id', 'name', 'status', 'grace_period']);
        $activeSchedules = $schedules->where('status', 'active');
        $defaultSchedule = $activeSchedules->firstWhere('name', 'Giờ hành chính');
        $selectedSchedule = $defaultSchedule ? $defaultSchedule->id : ($activeSchedules->count() === 1 ? $activeSchedules->first()->id : null);
        
        $assignments = DB::table('employee_schedules')->whereIn('employee_id', $employeeIds)
            ->orderByDesc('effective_from')->get()->groupBy('employee_id');
        $rules = DB::table('work_schedule_rules')->whereIn('work_schedule_id', $schedules->pluck('id'))->get()
            ->keyBy(fn ($rule) => $rule->work_schedule_id.':'.$rule->day_of_week);
        
        $calculator = app(AttendanceHourCalculator::class);
        
        $requests = collect();
        if ($employeeIds->isNotEmpty()) {
            $requests = DB::table('attendance_requests')
                ->whereIn('employee_id', $employeeIds)
                ->where('status', 'approved')
                ->where(function($q) use ($from, $to) {
                    $q->whereBetween('start_date', [$from, $to])
                      ->orWhereBetween('end_date', [$from, $to])
                      ->orWhere(function($sq) use ($from, $to) {
                          $sq->where('start_date', '<', $from)->where('end_date', '>', $to);
                      });
                })->get();
        }
            
        $departments = DB::table('departments')->orderBy('name')->pluck('name', 'id');

        $reportData = [];
        
        foreach ($employees as $emp) {
            $empData = [
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'department_name' => $emp->department_name,
                'position' => $emp->position,
                'status' => $emp->status,
                'join_date' => $emp->join_date ? Carbon::parse($emp->join_date)->format('d/m/Y') : null,
                'probation_end' => $emp->join_date ? Carbon::parse($emp->join_date)->addMonths(2)->format('d/m/Y') : null,
                'days' => [],
                'total_hours' => 0
            ];
            
            $empEntries = $entriesGrouped->get($emp->id, collect())->keyBy('work_date');
            
            foreach ($dates as $dateStr) {
                $dayOfWeek = Carbon::parse($dateStr)->dayOfWeek;
                $entry = $empEntries->get($dateStr);
                
                $matches = ($assignments->get($emp->id) ?? collect())->filter(fn ($assignment) => $assignment->effective_from <= $dateStr && ($assignment->effective_to === null || $assignment->effective_to >= $dateStr));
                $assignment = $matches->first();
                $scheduleId = $assignment ? $assignment->work_schedule_id : $selectedSchedule;
                $rule = $matches->count() > 1 ? null : $rules->get($scheduleId.':'.$dayOfWeek);
                
                $req = $requests->first(function($r) use ($emp, $dateStr) {
                    return $r->employee_id == $emp->id && $dateStr >= substr($r->start_date, 0, 10) && $dateStr <= substr($r->end_date, 0, 10);
                });
                
                $displayVal = '';
                $hours = 0;
                
                if ($entry) {
                    $metrics = $calculator->calculate($entry, $rule);
                    $hours = $metrics['regular_hours'] ?? 0;
                    $displayVal = $hours;
                } else if ($rule && $rule->is_working_day) {
                    $displayVal = '0';
                    $hours = 0;
                }
                
                if ($req) {
                    if ($req->type == 'paid_leave') {
                        $isHalfDay = ($req->start_session == 'afternoon' && substr($req->start_date,0,10) == $dateStr) || 
                                     ($req->end_session == 'morning' && substr($req->end_date,0,10) == $dateStr);
                        $hours = ($hours ?? 0) + ($isHalfDay ? 4 : 8);
                        if ($hours > 8) $hours = 8;
                        $displayVal = $hours == 8 ? 'P' : ($hours > 0 ? $hours . ' (P)' : 'P');
                    } elseif ($req->type == 'unpaid_leave') {
                        $displayVal = '0';
                    } elseif ($req->type == 'business_trip') {
                        $hours = 8;
                        $displayVal = 8;
                    }
                }
                
                // For holidays we usually put NL, but let's assume we don't have a holidays table right now.
                
                if (is_numeric($displayVal)) {
                    $displayVal = $displayVal == floor($displayVal) ? number_format($displayVal, 0) : rtrim(rtrim(number_format($displayVal, 2, '.', ''), '0'), '.');
                }
                
                if (!$rule || !$rule->is_working_day) {
                    if ($displayVal === '0' || $displayVal === '') {
                        $displayVal = ''; 
                    }
                }
                
                $empData['days'][$dateStr] = [
                    'value' => $displayVal,
                    'is_sunday' => $dayOfWeek == 0,
                ];
                
                $empData['total_hours'] += $hours;
            }
            
            $reportData[] = $empData;
        }

        return view('backend.timesheet_export.index', compact('reportData', 'dates', 'departments', 'from', 'to'));
    }
}
