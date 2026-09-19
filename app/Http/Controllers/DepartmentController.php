<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);
        $counts = DB::table('employees')->select('department_id')
            ->selectRaw('COUNT(*) as employee_count')->groupBy('department_id');
        $query = DB::table('departments as d')
            ->leftJoin('departments as parent', 'parent.id', '=', 'd.parent_id')
            ->leftJoinSub($counts, 'staff', 'staff.department_id', '=', 'd.id')
            ->select('d.id', 'd.code', 'd.name', 'd.status', 'parent.name as parent_name')
            ->selectRaw('COALESCE(staff.employee_count, 0) as employee_count');
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('d.code', 'like', '%'.$term.'%')
                    ->orWhere('d.name', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('d.status', $request->input('status'));
        }

        return view('backend.departments.index', [
            'departments' => $query->orderBy('d.sort_order')->orderBy('d.name')->orderBy('d.id')
                ->paginate(50)->withQueryString(),
        ]);
    }
}