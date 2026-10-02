<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer|exists:departments,id',
            'position' => 'nullable|in:employee,team_leader,manager,director',
            'status' => 'nullable|in:active,inactive,resigned',
        ]);
        $query = DB::table('employees as e')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->select('e.id', 'e.employee_code', 'e.name', 'e.status', 'e.position', 'e.manager_id', 'e.manager_l2_id', 'e.department_id', 'd.name as department_name');
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('e.employee_code', 'like', '%'.$term.'%')
                    ->orWhere('e.name', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('department_id')) {
            $query->where('e.department_id', $request->input('department_id'));
        }
        if ($request->filled('position')) {
            $query->where('e.position', $request->input('position'));
        }
        if ($request->filled('status')) {
            $query->where('e.status', $request->input('status'));
        }

        return view('backend.employees.index', [
            'employees' => $query->orderBy('e.employee_code')->paginate(50)->withQueryString(),
            'departments' => DB::table('departments')->orderBy('name')->get(['id', 'name']),
            'managers' => DB::table('employees')
                ->where('status', 'active')
                ->whereIn('position', ['team_leader', 'manager', 'director'])
                ->orderBy('name')
                ->get(['id', 'name', 'employee_code', 'department_id']),
            'hrs' => DB::table('employees as e')
                ->join('departments as d', 'd.id', '=', 'e.department_id')
                ->where('e.status', 'active')
                ->whereIn('e.position', ['team_leader', 'manager', 'director'])
                ->orderBy('e.name')
                ->get(['e.id', 'e.name', 'e.employee_code', 'e.department_id']),
        ]);
    }

    public function edit($id)
    {
        $employee = \App\Models\Employee::findOrFail($id);
        $departments = \Illuminate\Support\Facades\DB::table('departments')->orderBy('name')->get();
        $managers = \Illuminate\Support\Facades\DB::table('employees as e')
                ->where('e.status', 'active')
                ->whereIn('e.position', ['team_leader', 'manager', 'director'])
                ->orderBy('e.name')
                ->get(['e.id', 'e.name', 'e.employee_code', 'e.department_id']);
        $hrs = $managers;
        return view('backend.employees.edit', compact('employee', 'departments', 'managers', 'hrs'));
    }

    public function update(Request $request, $id)
    {
        $employee = \App\Models\Employee::findOrFail($id);

        $validated = $request->validate([
            'employee_code' => 'required|string|max:50|unique:employees,employee_code,' . $id,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|integer|exists:departments,id',
            'position' => 'nullable|string',
            'level' => 'nullable|string|max:100',
            'manager_id' => 'nullable|integer|exists:employees,id',
            'manager_l2_id' => 'nullable|integer|exists:employees,id',
            'join_date' => 'nullable|date',
            'leave_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned'
        ]);

        if ($request->manager_id == $id || $request->manager_l2_id == $id) {
            return back()->withInput()->withErrors(['manager_id' => 'Không thể chọn chính mình.']);
        }

        $employee->update($validated);

        return redirect()->route('backend.employees.index')->with('success', 'Cập nhật thông tin nhân viên thành công!');
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,resigned'
        ]);

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->status = $request->status;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ]);
    }

    public function changePosition(Request $request, $id)
    {
        $request->validate([
            'position' => 'required|in:employee,team_leader,manager,director'
        ]);

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->position = $request->position;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật chức vụ thành công!'
        ]);
    }

    public function changeDepartment(Request $request, $id)
    {
        $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id'
        ]);

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->department_id = $request->department_id;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phòng ban thành công!'
        ]);
    }

    public function changeManager(Request $request, $id)
    {
        $request->validate([
            'manager_id' => 'nullable|integer|exists:employees,id'
        ]);

        if ($request->manager_id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tự chọn mình làm người duyệt lần 1!'
            ], 400);
        }

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->manager_id = $request->manager_id;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật người duyệt lần 1 thành công!'
        ]);
    }

    public function changeHr(Request $request, $id)
    {
        $request->validate([
            'manager_l2_id' => 'nullable|integer|exists:employees,id'
        ]);

        if ($request->manager_l2_id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tự chọn mình làm người duyệt lần 2!'
            ], 400);
        }

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->manager_l2_id = $request->manager_l2_id;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật người duyệt lần 2 thành công!'
        ]);
    }

    public function bulkManager(Request $request)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'manager_id' => 'nullable|integer|exists:employees,id',
            'manager_l2_id' => 'nullable|integer|exists:employees,id'
        ]);

        $departmentId = $request->department_id;
        $managerId = $request->manager_id;
        $hrId = $request->manager_l2_id;
        $employeeIds = $request->employee_ids;

        if ($managerId && in_array($managerId, $employeeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể chọn người duyệt lần 1 là một trong những người đang được chọn để cập nhật!'
            ], 400);
        }
        
        if ($hrId && in_array($hrId, $employeeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể chọn người duyệt lần 2 là một trong những người đang được chọn để cập nhật!'
            ], 400);
        }

        $dataToUpdate = [];
        // Only update the fields if they are explicitly sent in request? 
        // Wait, the modal might send both. So we update both if the request has them.
        // Actually, let's just update both to what's provided (can be null).
        if ($request->has('department_id')) $dataToUpdate['department_id'] = $departmentId;
        if ($request->has('manager_id')) $dataToUpdate['manager_id'] = $managerId;
        if ($request->has('manager_l2_id')) $dataToUpdate['manager_l2_id'] = $hrId;

        if (!empty($dataToUpdate)) {
            \App\Models\Employee::whereIn('id', $employeeIds)->update($dataToUpdate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin hàng loạt thành công cho ' . count($employeeIds) . ' nhân viên!'
        ]);
    }
}
