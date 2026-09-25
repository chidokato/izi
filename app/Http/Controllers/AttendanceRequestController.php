<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\RequestApproval;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceRequest::with(['employee', 'requestApprovals'])->latest();

        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $query->where(function($q) use ($user) {
                $q->where('employee_id', $user->employee_id)
                  ->orWhereHas('requestApprovals', function($q2) use ($user) {
                      $q2->where('approver_id', $user->id);
                  })
                  ->orWhereHas('employee', function($q3) use ($user) {
                      $q3->where('manager_id', $user->employee_id);
                  });
            });
        }

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

        $canBulkApprove = $user->isAdmin();
        if (!$canBulkApprove && $user->employee_id) {
            $canBulkApprove = Employee::where('manager_id', $user->employee_id)->exists() || 
                              \App\Models\RequestApproval::where('approver_id', $user->id)->exists();
        }

        return view('backend.attendance_requests.index', compact('requests', 'canBulkApprove'));
    }

    public function create()
    {
        $employees = Employee::with('manager')->orderBy('name')->get();
        $currentUserEmployee = null;
        if (auth()->check() && auth()->user()->employee_id) {
            $currentUserEmployee = Employee::with('manager')->find(auth()->user()->employee_id);
        }
        return view('backend.attendance_requests.create', compact('employees', 'currentUserEmployee'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:paid_leave,unpaid_leave,business_trip,attendance_adjustment,work_from_home,other,overtime',
            'reason' => 'required|string|max:1000',
        ];

        $messages = [
            'employee_id.required' => 'Vui lòng chọn nhân viên.',
            'employee_id.exists' => 'Nhân viên không tồn tại trong hệ thống.',
            'type.required' => 'Vui lòng chọn loại phiếu.',
            'reason.required' => 'Vui lòng nhập lý do/diễn giải.',
            'start_date_leave.required' => 'Vui lòng chọn ngày xin nghỉ.',
            'start_session_leave.required' => 'Vui lòng chọn buổi bắt đầu nghỉ.',
            'leave_days.required' => 'Vui lòng nhập số ngày nghỉ.',
            'leave_days.min' => 'Số ngày nghỉ tối thiểu là 0.5 ngày.',
            'start_date.required' => 'Vui lòng chọn ngày tháng hợp lệ.',
            'end_date.required' => 'Vui lòng chọn ngày kết thúc.',
            'end_date.after_or_equal' => 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu.',
            'start_session.required' => 'Vui lòng chọn xác nhận công ra/vào.'
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

        $request->validate($rules, $messages);

        // Kiểm tra trùng thời gian (Overlap)
        $newStart = \Carbon\Carbon::parse($request->start_date);
        $newEnd = \Carbon\Carbon::parse($request->end_date);
        
        if ($newStart->format('H:i:s') === '00:00:00') {
            $newStart->setTime($request->start_session === 'afternoon' ? 13 : 0, 0, 0);
        }
        if ($newEnd->format('H:i:s') === '00:00:00') {
            $newEnd->setTime($request->end_session === 'morning' ? 12 : 23, 59, 59);
        }

        $existingRequests = AttendanceRequest::where('employee_id', $request->employee_id)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        foreach ($existingRequests as $existing) {
            $existingStart = \Carbon\Carbon::parse($existing->start_date);
            $existingEnd = \Carbon\Carbon::parse($existing->end_date);
            
            if ($existingStart->format('H:i:s') === '00:00:00') {
                $existingStart->setTime($existing->start_session === 'afternoon' ? 13 : 0, 0, 0);
            }
            if ($existingEnd->format('H:i:s') === '00:00:00') {
                $existingEnd->setTime($existing->end_session === 'morning' ? 12 : 23, 59, 59);
            }

            if ($newStart <= $existingEnd && $newEnd >= $existingStart) {
                return back()->withInput()->withErrors(['time' => 'Thời gian này đã bị trùng với một phiếu khác đã tạo. Vui lòng chọn khoảng thời gian khác.']);
            }
        }

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

        $attendanceRequest = AttendanceRequest::create($request->all());

        $employee = Employee::find($request->employee_id);
        
        $adminUser = User::whereIn('permission', [1,2])->first();

        if ($employee && $employee->manager_id) {
            $managerUser = User::where('employee_id', $employee->manager_id)->first();
            
            $attendanceRequest->update(['current_approval_step' => 1]);
            RequestApproval::create([
                'request_id' => $attendanceRequest->id,
                'approver_id' => $managerUser ? $managerUser->id : ($adminUser->id ?? 1),
                'step' => 1,
                'status' => 'pending',
            ]);
        } else {
            $attendanceRequest->update(['current_approval_step' => 2]);
            RequestApproval::create([
                'request_id' => $attendanceRequest->id,
                'approver_id' => $adminUser->id ?? 1,
                'step' => 2,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('backend.attendance-requests.index')->with('success', 'Đã tạo phiếu thành công.');
    }

    public function edit($id)
    {
        $attendanceRequest = AttendanceRequest::with(['requestApprovals.approver', 'employee'])->findOrFail($id);
        $employees = Employee::orderBy('name')->get();
        return view('backend.attendance_requests.edit', compact('attendanceRequest', 'employees'));
    }

    public function update(Request $request, AttendanceRequest $attendanceRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled',
        ]);

        $user = auth()->user();
        $status = $request->status;

        $originalStatus = $attendanceRequest->status;

        if ($status === 'approved') {
            if ($attendanceRequest->current_approval_step == 1) {
                // Manager approved
                RequestApproval::where('request_id', $attendanceRequest->id)
                    ->where('step', 1)
                    ->update(['status' => 'approved', 'acted_at' => now(), 'approver_id' => $user->id]);
                
                // Move to step 2 (HR)
                $adminUser = User::whereIn('permission', [1,2])->first();
                $attendanceRequest->update(['current_approval_step' => 2]);
                RequestApproval::create([
                    'request_id' => $attendanceRequest->id,
                    'approver_id' => $adminUser->id ?? 1,
                    'step' => 2,
                    'status' => 'pending',
                ]);

                $message = 'Đã duyệt bước 1, chuyển cho Nhân sự.';
                
            } else if ($attendanceRequest->current_approval_step == 2) {
                // HR approved
                RequestApproval::where('request_id', $attendanceRequest->id)
                    ->where('step', 2)
                    ->update(['status' => 'approved', 'acted_at' => now(), 'approver_id' => $user->id]);
                
                $attendanceRequest->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);

                if ($originalStatus !== 'approved' && $attendanceRequest->type === 'paid_leave') {
                    $days = $this->calculateLeaveDays($attendanceRequest);
                    $attendanceRequest->employee()->decrement('annual_leave_balance', $days);
                }

                $message = 'Đã duyệt hoàn tất.';
            } else {
                $message = 'Trạng thái đã được cập nhật.';
            }
        } else if ($status === 'rejected') {
            RequestApproval::where('request_id', $attendanceRequest->id)
                ->where('step', $attendanceRequest->current_approval_step)
                ->update(['status' => 'rejected', 'acted_at' => now(), 'approver_id' => $user->id]);
            
            $attendanceRequest->update([
                'status' => 'rejected',
                'rejected_at' => now(),
            ]);

            if ($originalStatus === 'approved' && $attendanceRequest->type === 'paid_leave') {
                $days = $this->calculateLeaveDays($attendanceRequest);
                $attendanceRequest->employee()->increment('annual_leave_balance', $days);
            }

            $message = 'Đã từ chối phiếu.';
        } else if ($status === 'cancelled') {
            $attendanceRequest->update(['status' => 'cancelled']);
            
            if ($originalStatus === 'approved' && $attendanceRequest->type === 'paid_leave') {
                $days = $this->calculateLeaveDays($attendanceRequest);
                $attendanceRequest->employee()->increment('annual_leave_balance', $days);
            }

            $message = 'Đã hủy phiếu.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message ?? 'Đã cập nhật trạng thái phiếu.',
                'status' => $attendanceRequest->status,
                'step' => $attendanceRequest->current_approval_step
            ]);
        }

        return redirect()->route('backend.attendance-requests.index')->with('success', $message ?? 'Đã cập nhật trạng thái phiếu.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:attendance_requests,id',
            'status' => 'required|in:approved,rejected',
        ]);

        $user = auth()->user();
        $status = $request->status;
        $count = 0;

        foreach ($request->ids as $id) {
            $attendanceRequest = AttendanceRequest::find($id);
            if (!$attendanceRequest || $attendanceRequest->status !== 'pending') continue;

            $currentApproval = $attendanceRequest->requestApprovals->where('step', $attendanceRequest->current_approval_step)->first();
            
            $canApprove = false;
            if ($currentApproval) {
                if ($currentApproval->approver_id == $user->id || $user->isAdmin() || ($attendanceRequest->current_approval_step == 1 && optional($attendanceRequest->employee)->manager_id == $user->employee_id)) {
                    $canApprove = true;
                }
            }

            if (!$canApprove) continue;

            $originalStatus = $attendanceRequest->status;

            if ($status === 'approved') {
                if ($attendanceRequest->current_approval_step == 1) {
                    RequestApproval::where('request_id', $attendanceRequest->id)
                        ->where('step', 1)
                        ->update(['status' => 'approved', 'acted_at' => now(), 'approver_id' => $user->id]);
                    
                    $adminUser = User::whereIn('permission', [1,2])->first();
                    $attendanceRequest->update(['current_approval_step' => 2]);
                    RequestApproval::create([
                        'request_id' => $attendanceRequest->id,
                        'approver_id' => $adminUser->id ?? 1,
                        'step' => 2,
                        'status' => 'pending',
                    ]);
                } else if ($attendanceRequest->current_approval_step == 2) {
                    RequestApproval::where('request_id', $attendanceRequest->id)
                        ->where('step', 2)
                        ->update(['status' => 'approved', 'acted_at' => now(), 'approver_id' => $user->id]);
                    
                    $attendanceRequest->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);

                    if ($originalStatus !== 'approved' && $attendanceRequest->type === 'paid_leave') {
                        $days = $this->calculateLeaveDays($attendanceRequest);
                        $attendanceRequest->employee()->decrement('annual_leave_balance', $days);
                    }
                }
            } else if ($status === 'rejected') {
                RequestApproval::where('request_id', $attendanceRequest->id)
                    ->where('step', $attendanceRequest->current_approval_step)
                    ->update(['status' => 'rejected', 'acted_at' => now(), 'approver_id' => $user->id]);
                
                $attendanceRequest->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                ]);

                if ($originalStatus === 'approved' && $attendanceRequest->type === 'paid_leave') {
                    $days = $this->calculateLeaveDays($attendanceRequest);
                    $attendanceRequest->employee()->increment('annual_leave_balance', $days);
                }
            }
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xử lý ' . $count . ' phiếu thành công.',
        ]);
    }

    public function destroy(AttendanceRequest $attendanceRequest)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($attendanceRequest->status === 'approved' && $attendanceRequest->type === 'paid_leave') {
            $days = $this->calculateLeaveDays($attendanceRequest);
            $attendanceRequest->employee()->increment('annual_leave_balance', $days);
        }

        $attendanceRequest->requestApprovals()->delete();
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

        $employee = \App\Models\Employee::find($employeeId);
        $leaveBalance = $employee ? $employee->annual_leave_balance : 0;

        return response()->json([
            'used' => $count,
            'remaining' => $remaining,
            'total' => 3,
            'month' => $date->month,
            'leave_balance' => $leaveBalance
        ]);
    }

    private function calculateLeaveDays(AttendanceRequest $req)
    {
        $start = \Carbon\Carbon::parse($req->start_date)->startOfDay();
        $end = \Carbon\Carbon::parse($req->end_date)->startOfDay();
        $days = 0;
        
        $current = $start->copy();
        while ($current->lte($end)) {
            $dayOfWeek = $current->dayOfWeek;
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $val = 1;
            } elseif ($dayOfWeek == 6) {
                $val = 0.5;
            } else {
                $val = 0;
            }
            
            if ($current->isSameDay($start) && $req->start_session == 'afternoon') {
                $val -= 0.5;
            }
            if ($current->isSameDay($end) && $req->end_session == 'morning') {
                $val -= 0.5;
            }
            if ($val < 0) $val = 0;
            
            $days += $val;
            $current->addDay();
        }
        return $days;
    }
}
