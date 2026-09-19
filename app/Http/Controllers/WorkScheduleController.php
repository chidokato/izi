<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkScheduleController extends Controller
{
    public const DAYS = [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 0 => 'Chủ nhật'];

    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,inactive']);
        $query = DB::table('work_schedules');
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $schedules = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $rules = DB::table('work_schedule_rules')->whereIn('work_schedule_id', $schedules->pluck('id'))->get()->groupBy('work_schedule_id');

        return view('backend.schedules.index', ['schedules' => $schedules, 'rules' => $rules, 'days' => self::DAYS]);
    }

    public function create()
    {
        return view('backend.schedules.form', ['schedule' => null, 'rules' => [], 'days' => self::DAYS]);
    }

    public function edit(int $id)
    {
        $schedule = DB::table('work_schedules')->where('id', $id)->first();
        abort_unless($schedule, 404);
        $rules = DB::table('work_schedule_rules')->where('work_schedule_id', $id)->get()->keyBy('day_of_week')->map(fn ($r) => (array) $r)->all();
        foreach ($rules as &$row) {
            foreach (['start_time', 'end_time', 'break_start', 'break_end', 'ot_start', 'ot_end'] as $field) {
                $row[$field] = $row[$field] === null ? '' : substr($row[$field], 0, 5);
            }
        }

        return view('backend.schedules.form', compact('schedule', 'rules') + ['days' => self::DAYS]);
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, int $id)
    {
        return $this->save($request, $id);
    }

    private function save(Request $request, ?int $id = null)
    {
        if ($id) {
            abort_unless(DB::table('work_schedules')->where('id', $id)->exists(), 404);
        }
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('work_schedules', 'code')->ignore($id)],
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:2000', 'status' => 'required|in:active,inactive',
            'rules' => 'required|array|size:7', 'rules.*' => 'required|array',
            'rules.*.is_working_day' => 'required|boolean', 'rules.*.ot_next_day' => 'required|boolean',
            'rules.*.start_time' => 'nullable|date_format:H:i', 'rules.*.end_time' => 'nullable|date_format:H:i',
            'rules.*.break_start' => 'nullable|date_format:H:i', 'rules.*.break_end' => 'nullable|date_format:H:i',
            'rules.*.ot_start' => 'nullable|date_format:H:i', 'rules.*.ot_end' => 'nullable|date_format:H:i',
        ]);
        $normalized = [];
        foreach (self::DAYS as $day => $label) {
            if (! isset($data['rules'][$day])) {
                throw ValidationException::withMessages(['rules' => 'Cần cấu hình đủ 7 ngày trong tuần.']);
            }
            $r = $data['rules'][$day];
            $error = fn ($message) => ValidationException::withMessages(['rules.'.$day => $label.': '.$message]);
            foreach (['start_time', 'end_time', 'break_start', 'break_end', 'ot_start', 'ot_end'] as $field) {
                $r[$field] = $r[$field] ?? null;
            }
            $minutes = fn ($time) => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
            $required = 0;
            if ($r['is_working_day']) {
                if (! $r['start_time'] || ! $r['end_time'] || $r['end_time'] <= $r['start_time']) {
                    throw $error('Giờ hành chính phải có giờ kết thúc sau giờ bắt đầu trong cùng ngày.');
                }
                $required = $minutes($r['end_time']) - $minutes($r['start_time']);
                if ((bool) $r['break_start'] !== (bool) $r['break_end']) {
                    throw $error('Cần nhập đủ giờ bắt đầu và kết thúc nghỉ.');
                }
                if ($r['break_start']) {
                    if ($r['break_start'] < $r['start_time'] || $r['break_end'] > $r['end_time'] || $r['break_end'] <= $r['break_start']) {
                        throw $error('Giờ nghỉ phải nằm trong giờ hành chính và kết thúc sau bắt đầu.');
                    }
                    $required -= $minutes($r['break_end']) - $minutes($r['break_start']);
                    if ($required <= 0) {
                        throw $error('Thời gian làm việc sau khi trừ giờ nghỉ phải lớn hơn 0.');
                    }
                }
            } elseif ($r['start_time'] || $r['end_time'] || $r['break_start'] || $r['break_end']) {
                throw $error('Đang tắt giờ hành chính: hãy xóa giờ hành chính/giờ nghỉ hoặc bật lại.');
            }
            if ((bool) $r['ot_start'] !== (bool) $r['ot_end']) {
                throw $error('Cần nhập đủ giờ bắt đầu và kết thúc OT.');
            }
            if ($r['ot_next_day'] && ! $r['ot_start']) {
                throw $error('Chỉ chọn qua ngày khi đã nhập khung giờ OT.');
            }
            if ($r['ot_start']) {
                $start = $minutes($r['ot_start']);
                $end = $minutes($r['ot_end']) + ($r['ot_next_day'] ? 1440 : 0);
                if ($end <= $start || $end - $start >= 1440) {
                    throw $error('Khung OT phải lớn hơn 0 và dưới 24 giờ. Kiểm tra lựa chọn qua ngày.');
                }
                if ($r['is_working_day'] && $start < $minutes($r['end_time']) && $end > $minutes($r['start_time'])) {
                    throw $error('Khung OT không được trùng giờ hành chính.');
                }
            }
            $normalized[$day] = array_intersect_key($r, array_flip(['is_working_day', 'start_time', 'end_time', 'break_start', 'break_end', 'ot_start', 'ot_end', 'ot_next_day'])) + ['required_minutes' => $required, 'work_value' => $r['is_working_day'] ? 1 : 0];
        }
        // An overnight OT window also must not overlap the following day's configured work.
        foreach ($normalized as $day => $r) {
            if (! $r['ot_next_day']) {
                continue;
            }
            $next = $normalized[($day + 1) % 7];
            $end = $minutes($r['ot_end']);
            if (($next['is_working_day'] && $end > $minutes($next['start_time'])) || ($next['ot_start'] && $end > $minutes($next['ot_start']))) {
                throw ValidationException::withMessages(['rules.'.$day => self::DAYS[$day].': OT qua ngày trùng khung giờ của ngày tiếp theo.']);
            }
        }
        DB::transaction(function () use ($id, $data, $normalized, $request) {
            $old = $id ? DB::table('work_schedules')->where('id', $id)->lockForUpdate()->first() : null;
            if ($id && DB::table('employee_schedules')->where('work_schedule_id', $id)->exists()) {
                throw ValidationException::withMessages(['name' => 'Lịch này đã được gán cho nhân viên. Hãy tạo lịch mới để giữ nguyên cấu hình đã áp dụng.']);
            }
            $values = ['code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'] ?? null, 'status' => $data['status'], 'updated_at' => now()];
            $oldRules = $id ? DB::table('work_schedule_rules')->where('work_schedule_id', $id)->get()->toArray() : [];
            if ($id) {
                DB::table('work_schedules')->where('id', $id)->update($values);
            } else {
                $id = DB::table('work_schedules')->insertGetId($values + ['created_at' => now()]);
            }
            foreach ($normalized as $day => $rule) {
                $exists = DB::table('work_schedule_rules')->where('work_schedule_id', $id)->where('day_of_week', $day)->exists();
                DB::table('work_schedule_rules')->updateOrInsert(['work_schedule_id' => $id, 'day_of_week' => $day], $rule + ['updated_at' => now()] + ($exists ? [] : ['created_at' => now()]));
            }
            DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => $old ? 'work_schedule_update' : 'work_schedule_create', 'model_type' => 'work_schedules', 'model_id' => $id, 'old_values' => $old ? json_encode(['schedule' => $old, 'rules' => $oldRules]) : null, 'new_values' => json_encode(['schedule' => $values, 'rules' => $normalized]), 'created_at' => now(), 'updated_at' => now()]);
        });

        return redirect()->route('backend.schedules.index')->with('success', 'Đã lưu lịch làm việc.');
    }
}
