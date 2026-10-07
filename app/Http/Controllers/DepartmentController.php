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

    public function create()
    {
        $allDepartments = DB::table('departments')->orderBy('sort_order')->orderBy('name')->get();
        $departmentsTree = $this->buildTreeSelect($allDepartments);

        return view('backend.departments.create', [
            'departmentsTree' => $departmentsTree,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:50|unique:departments,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        DB::table('departments')->insert([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'parent_id' => $request->input('parent_id') ?: null,
            'sort_order' => $request->input('sort_order', 0),
            'status' => $request->input('status', 'active'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('backend.departments.index')->with('success', 'Thêm phòng ban thành công.');
    }

    public function edit($id)
    {
        $department = DB::table('departments')->where('id', $id)->first();
        if (!$department) {
            return redirect()->route('backend.departments.index')->with('error', 'Không tìm thấy phòng ban.');
        }

        $allDepartments = DB::table('departments')->orderBy('sort_order')->orderBy('name')->get();
        $descendants = $this->getDescendantIds($allDepartments, $id);
        $invalidParentIds = array_merge([$id], $descendants);

        $departmentsTree = $this->buildTreeSelect($allDepartments);

        return view('backend.departments.edit', [
            'department' => $department,
            'departmentsTree' => $departmentsTree,
            'invalidParentIds' => $invalidParentIds,
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = DB::table('departments')->where('id', $id)->first();
        if (!$department) {
            return redirect()->route('backend.departments.index')->with('error', 'Không tìm thấy phòng ban.');
        }

        $request->validate([
            'code' => 'nullable|string|max:50|unique:departments,code,' . $id,
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->filled('parent_id')) {
            $allDepartments = DB::table('departments')->get();
            $descendants = $this->getDescendantIds($allDepartments, $id);
            if ($request->input('parent_id') == $id || in_array($request->input('parent_id'), $descendants)) {
                return back()->with('error', 'Không thể chọn phòng ban này làm cấp trên để tránh vòng lặp.')->withInput();
            }
        }

        DB::table('departments')->where('id', $id)->update([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'parent_id' => $request->input('parent_id') ?: null,
            'sort_order' => $request->input('sort_order', 0),
            'status' => $request->input('status', 'active'),
            'updated_at' => now(),
        ]);

        return redirect()->route('backend.departments.index')->with('success', 'Cập nhật phòng ban thành công.');
    }

    public function destroy($id)
    {
        $department = DB::table('departments')->where('id', $id)->first();
        if (!$department) {
            return redirect()->route('backend.departments.index')->with('error', 'Không tìm thấy phòng ban.');
        }

        $employeesCount = DB::table('employees')->where('department_id', $id)->count();
        if ($employeesCount > 0) {
            return redirect()->route('backend.departments.index')->with('error', 'Không thể xóa phòng ban đang có nhân viên.');
        }

        $subDepartmentsCount = DB::table('departments')->where('parent_id', $id)->count();
        if ($subDepartmentsCount > 0) {
            return redirect()->route('backend.departments.index')->with('error', 'Không thể xóa phòng ban đang có phòng ban con.');
        }

        DB::table('departments')->where('id', $id)->delete();

        return redirect()->route('backend.departments.index')->with('success', 'Xóa phòng ban thành công.');
    }

    private function getDescendantIds($departments, $parentId)
    {
        $descendants = [];
        foreach ($departments as $department) {
            if ($department->parent_id == $parentId) {
                $descendants[] = $department->id;
                $descendants = array_merge($descendants, $this->getDescendantIds($departments, $department->id));
            }
        }
        return $descendants;
    }

    private function buildTreeSelect($departments, $parentId = null, $prefix = '')
    {
        $tree = [];
        foreach ($departments as $department) {
            if ($department->parent_id == $parentId) {
                $department->name_with_prefix = $prefix . $department->name;
                $tree[] = $department;
                $tree = array_merge($tree, $this->buildTreeSelect($departments, $department->id, $prefix . '-- '));
            }
        }
        return $tree;
    }
}