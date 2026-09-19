<?php

namespace App\Http\Controllers;

use App\Services\AttendanceFileReader;
use App\Services\AttendanceImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceImportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['hours_mode' => 'nullable|in:regular,total', 'schedule_id' => 'nullable|integer|exists:work_schedules,id', 'q' => 'nullable|string|max:100', 'department' => 'nullable|string|max:255', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $query = DB::table('attendance_entries as a')->join('employees as e', 'e.id', '=', 'a.employee_id')->select('a.*', 'e.employee_code');
        if ($q = $request->input('q')) {
            $query->where(function ($query) use ($q) {
                $query->where('e.employee_code', 'like', '%'.$q.'%')->orWhere('a.employee_name', 'like', '%'.$q.'%');
            });
        }
        if ($request->filled('department')) {
            $query->where('a.department_name', $request->input('department'));
        }
        if ($request->filled('from')) {
            $query->where('a.work_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('a.work_date', '<=', $request->input('to'));
        }

        $entries = $query->orderByDesc('a.work_date')->orderBy('e.employee_code')->paginate(50)->withQueryString();
        $schedules = DB::table('work_schedules')->orderBy('name')->get(['id', 'name', 'status']);
        $activeSchedules = $schedules->where('status', 'active');
        $selectedSchedule = $request->input('schedule_id');
        if (!$selectedSchedule) {
            $defaultSchedule = $activeSchedules->firstWhere('name', 'Giờ hành chính');
            $selectedSchedule = $defaultSchedule ? $defaultSchedule->id : ($activeSchedules->count() === 1 ? $activeSchedules->first()->id : null);
        }
        $assignments = DB::table('employee_schedules')->whereIn('employee_id', $entries->pluck('employee_id'))
            ->orderByDesc('effective_from')->get()->groupBy('employee_id');
        $rules = DB::table('work_schedule_rules')->whereIn('work_schedule_id', $schedules->pluck('id'))->get()
            ->keyBy(fn ($rule) => $rule->work_schedule_id.':'.$rule->day_of_week);
        $calculator = app(\App\Services\AttendanceHourCalculator::class);
        
        // Fetch approved requests for these employees within this date range
        $minDate = $entries->min('work_date');
        $maxDate = $entries->max('work_date');
        $requests = $entries->isEmpty() ? collect() : DB::table('attendance_requests')
            ->whereIn('employee_id', $entries->pluck('employee_id')->unique())
            ->where('status', 'approved')
            ->where(function($q) use ($minDate, $maxDate) {
                $q->whereBetween('start_date', [$minDate, $maxDate])
                  ->orWhereBetween('end_date', [$minDate, $maxDate])
                  ->orWhere(function($sq) use ($minDate, $maxDate) {
                      $sq->where('start_date', '<', $minDate)->where('end_date', '>', $maxDate);
                  });
            })->get();

        foreach ($entries as $entry) {
            $matches = ($assignments->get($entry->employee_id) ?? collect())->filter(fn ($assignment) => $assignment->effective_from <= $entry->work_date && ($assignment->effective_to === null || $assignment->effective_to >= $entry->work_date));
            $assignment = $matches->first();
            $scheduleId = $assignment ? $assignment->work_schedule_id : $selectedSchedule;
            $rule = $matches->count() > 1 ? null : $rules->get($scheduleId.':'.\Carbon\Carbon::parse($entry->work_date)->dayOfWeek);
            
            // Check for approved request on this day
            $req = $requests->first(function($r) use ($entry) {
                return $r->employee_id == $entry->employee_id && $entry->work_date >= substr($r->start_date, 0, 10) && $entry->work_date <= substr($r->end_date, 0, 10);
            });
            
            if ($req && $rule) {
                $entry->request = $req;
                if ($req->type == 'attendance_adjustment') {
                    $entry->calculation_note = 'Bổ sung công';
                    
                    $adjSession = $req->start_session;
                    
                    if ($adjSession === 'morning' || $adjSession === 'full') {
                        $entry->checkin = substr($rule->start_time, 0, 5);
                    }
                    if ($adjSession === 'afternoon' || $adjSession === 'full') {
                        $entry->checkout = substr($rule->end_time, 0, 5);
                    }
                } elseif ($req->type == 'paid_leave' || $req->type == 'unpaid_leave') {
                    $entry->calculation_note = 'Nghỉ phép';
                } elseif ($req->type == 'business_trip') {
                    $entry->calculation_note = 'Công tác';
                    $entry->business_hours = 0;
                    if ($rule && $rule->is_working_day) {
                        $seconds = fn ($time) => (int) substr($time, 0, 2) * 3600 + (int) substr($time, 3, 2) * 60 + (int) substr($time, 6, 2);
                        
                        $rStart = $seconds($rule->start_time);
                        $rEnd = $seconds($rule->end_time);
                        $bStartSec = $rule->break_start ? $seconds($rule->break_start) : null;
                        $bEndSec = $rule->break_end ? $seconds($rule->break_end) : null;
                        
                        $dayStartStr = $entry->work_date . ' 00:00:00';
                        $dayEndStr = $entry->work_date . ' 23:59:59';
                        
                        $reqStartStr = $req->start_date instanceof \Carbon\Carbon ? $req->start_date->format('Y-m-d H:i:s') : (string)$req->start_date;
                        $reqEndStr = $req->end_date instanceof \Carbon\Carbon ? $req->end_date->format('Y-m-d H:i:s') : (string)$req->end_date;
                        
                        $actStart = max($reqStartStr, $dayStartStr);
                        $actEnd = min($reqEndStr, $dayEndStr);
                        
                        if ($actEnd > $actStart) {
                            $t1 = $seconds(substr($actStart, 11, 8));
                            $t2 = $seconds(substr($actEnd, 11, 8));
                            
                            $overlap = fn ($a, $b, $c, $d) => max(0, min($b, $d) - max($a, $c));
                            $workOverlap = $overlap($t1, $t2, $rStart, $rEnd);
                            $breakOverlap = ($bStartSec && $bEndSec) ? $overlap($t1, $t2, $bStartSec, $bEndSec) : 0;
                            
                            $entry->business_hours = ($workOverlap - $breakOverlap) / 3600;
                        }
                    } else {
                        $dayStartStr = $entry->work_date . ' 00:00:00';
                        $dayEndStr = $entry->work_date . ' 23:59:59';
                        $reqStartStr = $req->start_date instanceof \Carbon\Carbon ? $req->start_date->format('Y-m-d H:i:s') : (string)$req->start_date;
                        $reqEndStr = $req->end_date instanceof \Carbon\Carbon ? $req->end_date->format('Y-m-d H:i:s') : (string)$req->end_date;
                        $actStart = max($reqStartStr, $dayStartStr);
                        $actEnd = min($reqEndStr, $dayEndStr);
                        if ($actEnd > $actStart) {
                            $entry->business_hours = (\Carbon\Carbon::parse($actEnd)->diffInMinutes(\Carbon\Carbon::parse($actStart))) / 60;
                            if ($entry->business_hours > 8) $entry->business_hours = 8;
                        }
                    }
                }
            }
            
            $entry->metrics = $calculator->calculate($entry, $rule);
            
            if (!isset($entry->calculation_note)) {
                $entry->calculation_note = $matches->count() > 1 ? 'Lịch gán bị chồng ngày' : ($rule ? (($schedules->firstWhere('id', $scheduleId)->name ?? '').($assignment ? ' (lịch đã gán)' : ' (lịch đối chiếu)')) : 'Chưa có khung giờ đối chiếu');
            }
        }

        return view('backend.attendance.index', ['entries' => $entries, 'schedules' => $schedules, 'selectedSchedule' => $selectedSchedule, 'departments' => DB::table('attendance_entries')->distinct()->orderBy('department_name')->pluck('department_name'), 'imports' => DB::table('attendance_imports')->where('uploaded_by', $request->user()->id)->orderByDesc('id')->limit(10)->get()]);
    }

    public function preview(Request $request, AttendanceFileReader $reader, AttendanceImporter $importer)
    {
        $request->validate(['file' => 'required|file|max:5120|mimes:xlsx,xls', 'date_format' => 'required|in:m/d/Y,d/m/Y']);
        $file = $request->file('file');
        if (! in_array(strtolower($file->getClientOriginalExtension()), ['xls', 'xlsx'], true)) {
            throw ValidationException::withMessages(['file' => 'Chỉ nhận file XLS hoặc XLSX.']);
        }
        try {
            $rows = $reader->read($file->getRealPath(), $request->input('date_format'));
        } catch(\Throwable $e) {
            if ($e instanceof \InvalidArgumentException) {
                $message = $e->getMessage();
            } else {
                report($e);
                $message = 'Không đọc được file Excel. Hãy kiểm tra file không có mật khẩu và xuất lại XLSX.';
            } throw ValidationException::withMessages(['file' => $message]);
        }
        $rows = $importer->inspect($rows);
        DB::table('attendance_imports')->where('status', 'pending')->where('expires_at', '<', now())->update(['preview' => null, 'status' => 'failed', 'error_message' => 'Bản xem trước hết hạn.']);
        $id = DB::table('attendance_imports')->insertGetId(['filename' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'file_hash' => hash_file('sha256', $file->getRealPath()), 'uploaded_by' => $request->user()->id, 'total_rows' => count($rows), 'status' => 'pending', 'preview' => json_encode($rows, JSON_UNESCAPED_UNICODE), 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('backend.attendance.show', $id);
    }

    public function show(Request $request, int $id)
    {
        $batch = DB::table('attendance_imports')->where('id', $id)->where('uploaded_by', $request->user()->id)->first();
        abort_unless($batch, 404);
        if ($batch->status !== 'pending' || ! $batch->preview || now()->gt($batch->expires_at)) {
            return redirect()->route('backend.attendance.index')->withErrors(['file' => 'Bản xem trước đã hết hạn hoặc đã xử lý.']);
        }

        return view('backend.attendance.preview', ['batch' => $batch, 'rows' => json_decode($batch->preview, true)]);
    }

    public function confirm(Request $request, int $id, AttendanceImporter $importer)
    {
        $request->validate(['confirm' => 'accepted', 'create_employees' => 'nullable|boolean', 'replace_existing' => 'nullable|boolean']);
        $result = $importer->commit($id, $request->user()->id, $request->boolean('create_employees'), $request->boolean('replace_existing'));

        return redirect()->route('backend.attendance.index')->with('success', "Đã lưu {$result['success']} dòng; {$result['duplicate']} dòng giống dữ liệu cũ; {$result['errors']} dòng lỗi; {$result['skipped']} dòng bỏ qua.");
    }
}
