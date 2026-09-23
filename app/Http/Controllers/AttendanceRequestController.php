<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRequest::with('employee')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search_employee')) {
            $keyword = $request->search_employee;
            $query->whereHas('employee', function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('employee_code', 'like', "%{$keyword}%");
            });
        }

        $requests = $query->paginate(20);

        return view('backend.attendance_requests.index', compact('requests'));
    }

    public function create()
    {
        $employees = Employee::orderBy('name')->get();
        return view('backend.attendance_requests.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:paid_leave,unpaid_leave,business_trip,attendance_adjustment,work_from_home,other,overtime',
            'reason' => 'required|string|max:1000',
        ];

        if ($type === 'business_trip') {
            $request->merge([
                'start_date' => $request->input('start_date_business'),
                'end_date' => $request->input('end_date_business'),
            ]);
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
        } elseif ($type === 'overtime') {
            $request->merge([
                'start_date' => $request->input('date_overtime') . ' ' . $request->input('start_time_overtime'),
                'end_date' => $request->input('date_overtime') . ' ' . $request->input('end_time_overtime'),
            ]);
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
        } elseif ($type === 'attendance_adjustment') {
            $request->merge([
                'start_date' => $request->input('start_date_adj'),
                'end_date' => $request->input('start_date_adj'),
                'start_session' => $request->input('start_session_adj'),
                'end_session' => $request->input('start_session_adj'),
            ]);
            $rules['start_date'] = 'required|date';
            $rules['start_session'] = 'required|in:morning,afternoon';
        } else {
            // Leave request logic
            $startDate = $request->input('start_date_leave');
            $startSession = $request->input('start_session_leave');
            $leaveDays = (float) $request->input('leave_days');
            
            $rules['start_date_leave'] = 'required|date';
            $rules['start_session_leave'] = 'required|in:morning,afternoon';
            $rules['leave_days'] = 'required|numeric|min:0.5';

            if ($startDate && $startSession && $leaveDays > 0) {
                $current = \Carbon\Carbon::parse($startDate)->startOfDay();
                $daysAccumulated = 0;
                $lastLeaveDate = $current->copy();
                $isFirstDay = true;
                $lastDayValConsumed = 0;

                while ($daysAccumulated < $leaveDays) {
                    $dayOfWeek = $current->dayOfWeek;
                    $dailyValue = 0;
                    
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                        $dailyValue = 1;
                    } elseif ($dayOfWeek == 6) {
                        $dailyValue = 0.5;
                    }

                    if ($isFirstDay && $startSession === 'afternoon' && $dailyValue === 1) {
                        $dailyValue = 0.5;
                    } elseif ($isFirstDay && $startSession === 'afternoon' && $dailyValue === 0.5) {
                        $dailyValue = 0;
                    }

                    if ($dailyValue > 0) {
                        if ($daysAccumulated + $dailyValue > $leaveDays) {
                            $consumed = $leaveDays - $daysAccumulated;
                            $daysAccumulated += $consumed;
                            $lastDayValConsumed = $consumed;
                        } else {
                            $daysAccumulated += $dailyValue;
                            $lastDayValConsumed = $dailyValue;
                        }
                        $lastLeaveDate = $current->copy();
                    }

                    if ($daysAccumulated < $leaveDays) {
                        $current->addDay();
                        $isFirstDay = false;
                    }
                }

                $endsMidDay = false;
                if ($lastLeaveDate->dayOfWeek >= 1 && $lastLeaveDate->dayOfWeek <= 5) {
                    $startedAfternoonOnLastDay = ($lastLeaveDate->isSameDay(\Carbon\Carbon::parse($startDate))) && ($startSession === 'afternoon');
                    if ($lastDayValConsumed == 0.5 && !$startedAfternoonOnLastDay) {
                        $endsMidDay = true;
                    }
                }

                $request->merge([
                    'start_date' => $startDate,
                    'start_session' => $startSession,
                    'end_date' => $lastLeaveDate->toDateString(),
                    'end_session' => $endsMidDay ? 'morning' : 'afternoon',
                ]);
            }
        }

        $request->validate($rules);

        if ($type === 'attendance_adjustment') {
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $count = AttendanceRequest::where('employee_id', $request->employee_id)
                ->where('type', 'attendance_adjustment')
                ->whereMonth('start_date', $startDate->month)
                ->whereYear('start_date', $startDate->year)
                ->where('status', '!=', 'cancelled')
                ->count();
            
            if ($count >= 3) {
                return back()->withInput()->withErrors(['type' => 'Nhân viên này đã sử dụng hết 3 phiếu bổ sung công trong tháng ' . $startDate->month . '.']);
            }
        }

        AttendanceRequest::create($request->all());

        return redirect()->route('backend.attendance-requests.index')->with('success', 'Đã tạo phiếu thành công.');
    }

    public function edit(AttendanceRequest $attendanceRequest)
    {
        $employees = Employee::orderBy('name')->get();
        return view('backend.attendance_requests.edit', compact('attendanceRequest', 'employees'));
    }

    public function update(Request $request, AttendanceRequest $attendanceRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled',
        ]);

        $attendanceRequest->update([
            'status' => $request->status,
            'approved_at' => $request->status === 'approved' ? now() : null,
            'rejected_at' => $request->status === 'rejected' ? now() : null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái phiếu.',
                'status' => $attendanceRequest->status
            ]);
        }

        return redirect()->route('backend.attendance-requests.index')->with('success', 'Đã cập nhật trạng thái phiếu.');
    }

    public function destroy(AttendanceRequest $attendanceRequest)
    {
        $attendanceRequest->delete();
        return redirect()->route('backend.attendance-requests.index')->with('success', 'Đã xóa phiếu.');
    }

    public function checkLimit(Request $request)
    {
        $employeeId = $request->employee_id;
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : now();
        
        if (!$employeeId) {
            return response()->json(['remaining' => 3, 'total' => 3]);
        }

        $count = AttendanceRequest::where('employee_id', $employeeId)
            ->where('type', 'attendance_adjustment')
            ->whereMonth('start_date', $date->month)
            ->whereYear('start_date', $date->year)
            ->where('status', '!=', 'cancelled')
            ->count();

        $remaining = max(0, 3 - $count);

        return response()->json([
            'used' => $count,
            'remaining' => $remaining,
            'total' => 3,
            'month' => $date->month
        ]);
    }
}
