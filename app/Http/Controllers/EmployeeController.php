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
            'status' => 'nullable|in:active,inactive,resigned',
        ]);
        $query = DB::table('employees as e')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->select('e.id', 'e.employee_code', 'e.name', 'e.status', 'e.position', 'd.name as department_name');
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
        if ($request->filled('status')) {
            $query->where('e.status', $request->input('status'));
        }

        return view('backend.employees.index', [
            'employees' => $query->orderBy('e.employee_code')->paginate(50)->withQueryString(),
            'departments' => DB::table('departments')->orderBy('name')->get(['id', 'name']),
        ]);
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
}