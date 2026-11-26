<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\AttendanceHourCalculator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TimesheetExportController extends Controller
{
    public function index(Request $request)
    {
        return view('backend.timesheet_export.index', $this->buildReportData($request));
    }

    public function export(Request $request)
    {
        $data = $this->buildReportData($request);
        $reportData = $data['reportData'];
        $dates = $data['dates'];
        $from = $data['from'];
        $to = $data['to'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bảng xuất công');

        $fixedLeft = ['Mã NV DXMB', 'HỌ TÊN', 'PHÒNG BAN', 'TÌNH TRẠNG', 'Ngày vào làm', 'Ngày hết thử việc'];
        $dateCount = count($dates);
        $totalColIndex = 6 + $dateCount + 1;

        foreach ($fixedLeft as $idx => $heading) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($col . '1', $heading);
            $sheet->mergeCells($col . '1:' . $col . '2');
        }

        if ($dateCount > 0) {
            $startDateCol = Coordinate::stringFromColumnIndex(7);
            $endDateCol = Coordinate::stringFromColumnIndex(6 + $dateCount);
            $sheet->setCellValue($startDateCol . '1', 'THỨ/NGÀY');
            if ($dateCount > 1) {
                $sheet->mergeCells($startDateCol . '1:' . $endDateCol . '1');
            }

            $dayLabels = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
            foreach ($dates as $i => $dateStr) {
                $d = Carbon::parse($dateStr);
                $col = Coordinate::stringFromColumnIndex(7 + $i);
                $sheet->setCellValue($col . '2', $d->format('d/m') . "\n" . $dayLabels[$d->dayOfWeek]);
                $sheet->getStyle($col . '2')->getAlignment()->setWrapText(true);
                if ($d->dayOfWeek == 0) {
                    $sheet->getStyle($col . '2')->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF405189');
                    $sheet->getStyle($col . '2')->getFont()->getColor()->setARGB('FFFFFFFF');
                }
            }
        }

        $rightHeadings = ['Tổng công tính lương', "Số lần\nĐTVS", "Số phút\nĐTVS"];
        foreach ($rightHeadings as $offset => $heading) {
            $col = Coordinate::stringFromColumnIndex($totalColIndex + $offset);
            $sheet->setCellValue($col . '1', $heading);
            $sheet->mergeCells($col . '1:' . $col . '2');
        }

        $lastCol = Coordinate::stringFromColumnIndex($totalColIndex + 2);
        $sheet->getStyle('A1:' . $lastCol . '2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF3F6F9'],
            ],
        ]);
        // Re-apply Sunday header fill after header range fill
        foreach ($dates as $i => $dateStr) {
            if (Carbon::parse($dateStr)->dayOfWeek == 0) {
                $col = Coordinate::stringFromColumnIndex(7 + $i);
                $sheet->getStyle($col . '2')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF405189');
                $sheet->getStyle($col . '2')->getFont()->getColor()->setARGB('FFFFFFFF');
            }
        }

        $statusMap = [
            'active' => 'CT',
            'probation' => 'HTV',
            'collaborator' => 'CTV',
            'ctv' => 'CTV',
            'freelancer' => 'CTV',
        ];

        $rowNum = 3;
        foreach ($reportData as $emp) {
            $sheet->setCellValueExplicit('A' . $rowNum, (string) $emp['employee_code'], DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $rowNum, $emp['name']);
            $sheet->setCellValue('C' . $rowNum, $emp['department_name']);
            $sheet->setCellValue('D' . $rowNum, $statusMap[$emp['status']] ?? strtoupper((string) $emp['status']));
            $sheet->setCellValue('E' . $rowNum, $emp['join_date']);
            $sheet->setCellValue('F' . $rowNum, $emp['probation_end']);

            foreach ($dates as $i => $dateStr) {
                $col = Coordinate::stringFromColumnIndex(7 + $i);
                $dayData = $emp['days'][$dateStr];
                $val = $dayData['value'];
                if ($val !== '' && is_numeric($val)) {
                    $sheet->setCellValue($col . $rowNum, (float) $val);
                } else {
                    $sheet->setCellValue($col . $rowNum, $val);
                }
                $sheet->getStyle($col . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $totalCongCol = Coordinate::stringFromColumnIndex($totalColIndex);
            $lateCountCol = Coordinate::stringFromColumnIndex($totalColIndex + 1);
            $lateMinCol = Coordinate::stringFromColumnIndex($totalColIndex + 2);

            $sheet->setCellValue($totalCongCol . $rowNum, round($emp['total_hours'] / 8, 2));
            $sheet->getStyle($totalCongCol . $rowNum)->getNumberFormat()->setFormatCode('0.00');
            $sheet->setCellValue($lateCountCol . $rowNum, (int) $emp['late_early_count']);
            $sheet->setCellValue($lateMinCol . $rowNum, (int) $emp['late_early_minutes']);
            $sheet->getStyle($totalCongCol . $rowNum . ':' . $lateMinCol . $rowNum)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        $lastRow = max(2, $rowNum - 1);
        $sheet->getStyle('A1:' . $lastCol . $lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFDEE2E6');

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(32);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);
        for ($i = 0; $i < $dateCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(7 + $i))->setWidth(7);
        }
        for ($offset = 0; $offset < 3; $offset++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($totalColIndex + $offset))->setWidth(14);
        }

        $sheet->freezePane('C3');

        $filename = 'bang-xuat-cong-' . $from . '-den-' . $to . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildReportData(Request $request): array
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
            
        $departments = DB::table('departments')->where('status', 'active')->orderBy('name')->pluck('name', 'id');

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
                'total_hours' => 0,
                'late_early_count' => 0,
                'late_early_minutes' => 0,
            ];
            
            $empEntries = $entriesGrouped->get($emp->id, collect())->keyBy('work_date');
            
            foreach ($dates as $dateStr) {
                $dayOfWeek = Carbon::parse($dateStr)->dayOfWeek;
                $entry = $empEntries->get($dateStr);
                
                $matches = ($assignments->get($emp->id) ?? collect())->filter(fn ($assignment) => $assignment->effective_from <= $dateStr && ($assignment->effective_to === null || $assignment->effective_to >= $dateStr));
                $assignment = $matches->first();
                $scheduleId = $assignment ? $assignment->work_schedule_id : $selectedSchedule;
                $rule = $matches->count() > 1 ? null : $rules->get($scheduleId.':'.$dayOfWeek);
                $schedule = $schedules->firstWhere('id', $scheduleId);
                $gracePeriod = $schedule ? ($schedule->grace_period ?? 0) : 0;
                
                $req = $requests->first(function($r) use ($emp, $dateStr) {
                    return $r->employee_id == $emp->id && $dateStr >= substr($r->start_date, 0, 10) && $dateStr <= substr($r->end_date, 0, 10);
                });
                
                $displayVal = '';
                $hours = 0;
                $metrics = null;
                
                if ($entry) {
                    $metrics = $calculator->calculate($entry, $rule);
                    $hours = $metrics['regular_hours'] ?? 0;
                    $displayVal = $hours;
                } else if ($rule && $rule->is_working_day) {
                    $displayVal = '0';
                    $hours = 0;
                }
                
                $isNonWorkingDay = ($dayOfWeek == 0) || (!$rule || !$rule->is_working_day);

                if (!$isNonWorkingDay && !$req && $entry && $entry->checkin !== null && $entry->checkout !== null && $entry->checkout >= $entry->checkin && $metrics !== null) {
                    $late = max(0, ($metrics['late_arrival'] ?? 0) - $gracePeriod);
                    $early = max(0, $metrics['early_departure'] ?? 0);
                    if ($late > 0) {
                        $empData['late_early_count']++;
                        $empData['late_early_minutes'] += $late;
                    }
                    if ($early > 0) {
                        $empData['late_early_count']++;
                        $empData['late_early_minutes'] += $early;
                    }
                }

                if ($req && !$isNonWorkingDay) {
                    if ($req->type == 'paid_leave') {
                        $isHalfDay = ($req->start_session == 'afternoon' && substr($req->start_date,0,10) == $dateStr) || 
                                     ($req->end_session == 'morning' && substr($req->end_date,0,10) == $dateStr);
                        $maxH = ($dayOfWeek == 6 ? 4 : 8);
                        $hours = ($hours ?? 0) + ($isHalfDay ? 4 : $maxH);
                        if ($hours > $maxH) $hours = $maxH;
                        $displayVal = $hours == $maxH ? 'P' : ($hours > 0 ? $hours . ' (P)' : 'P');
                    } elseif ($req->type == 'unpaid_leave') {
                        $displayVal = '0';
                    } elseif ($req->type == 'business_trip') {
                        $hours = ($dayOfWeek == 6 ? 4 : 8);
                        $displayVal = $hours;
                    }
                }
                
                // For holidays we usually put NL, but let's assume we don't have a holidays table right now.
                
                if (is_numeric($displayVal)) {
                    $displayVal = $displayVal == floor($displayVal) ? number_format($displayVal, 0) : rtrim(rtrim(number_format($displayVal, 2, '.', ''), '0'), '.');
                }
                
                if ($isNonWorkingDay) {
                    $hours = 0;
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

        return compact('reportData', 'dates', 'departments', 'from', 'to');
    }
}
