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
            'department_id' => 'nullable|string',
            'position' => 'nullable|in:employee,team_leader,manager,director',
            'status' => 'nullable|in:active,inactive,resigned,probation',
        ]);
        $query = DB::table('employees as e')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->select('e.id', 'e.employee_code', 'e.name', 'e.status', 'e.position', 'e.evaluation_type', 'e.manager_id', 'e.manager_l2_id', 'e.department_id', 'e.annual_leave_balance', 'd.name as department_name', 'e.deleted_at');
            
        if ($request->boolean('view_deleted')) {
            $query->whereNotNull('e.deleted_at');
        } else {
            $query->whereNull('e.deleted_at');
        }
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('e.employee_code', 'like', '%'.$term.'%')
                    ->orWhere('e.name', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('department_id')) {
            if ($request->input('department_id') === 'none') {
                $query->whereNull('e.department_id');
            } else {
                $query->where('e.department_id', $request->input('department_id'));
            }
        }
        if ($request->filled('position')) {
            if ($request->input('position') === 'employee') {
                $query->where(function ($q) {
                    $q->where('e.position', 'employee')
                      ->orWhereNull('e.position')
                      ->orWhere('e.position', '');
                });
            } else {
                $query->where('e.position', $request->input('position'));
            }
        }
        if ($request->filled('status')) {
            $query->where('e.status', $request->input('status'));
        }

        return view('backend.employees.index', [
            'employees' => $query->orderBy('e.employee_code')->paginate(50)->withQueryString(),
            'departments' => DB::table('departments')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'managers' => DB::table('employees')
                ->whereIn('status', ['active', 'probation'])
                ->whereNull('deleted_at')
                ->whereIn('position', ['team_leader', 'manager', 'director'])
                ->orderBy('name')
                ->get(['id', 'name', 'employee_code', 'department_id']),
            'hrs' => DB::table('employees as e')
                ->join('departments as d', 'd.id', '=', 'e.department_id')
                ->where('d.status', 'active')
                ->whereIn('e.status', ['active', 'probation'])
                ->whereNull('e.deleted_at')
                ->whereIn('e.position', ['team_leader', 'manager', 'director'])
                ->orderBy('e.name')
                ->get(['e.id', 'e.name', 'e.employee_code', 'e.department_id']),
        ]);
    }

    public function create()
    {
        $departments = DB::table('departments')->where('status', 'active')->orderBy('name')->get();
        $managers = DB::table('employees')
            ->whereIn('status', ['active', 'probation'])
            ->whereNull('deleted_at')
            ->whereIn('position', ['team_leader', 'manager', 'director'])
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'department_id']);
        $hrs = DB::table('employees')
            ->whereIn('status', ['active', 'probation'])
            ->whereNull('deleted_at')
            ->whereIn('position', ['team_leader', 'manager', 'director'])
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'department_id']);

        return view('backend.employees.create', compact('departments', 'managers', 'hrs'));
    }

    public function store(Request $request)
    {
        $existing = \App\Models\Employee::withTrashed()->where('employee_code', $request->employee_code)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $errorMsg = "Mã nhân viên '{$request->employee_code}' đã tồn tại trong danh sách 'Đã xóa / Ẩn' (Nhân viên: {$existing->name}). Vui lòng vào mục 'Đã xóa / Ẩn' để khôi phục thay vì tạo mới.";
            } else {
                $errorMsg = "Mã nhân viên '{$request->employee_code}' đã tồn tại trong hệ thống (Nhân viên: {$existing->name}).";
            }
            return back()->withInput()->withErrors(['employee_code' => $errorMsg]);
        }

        $validated = $request->validate([
            'employee_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|integer|exists:departments,id',
            'position' => 'nullable|string',
            'evaluation_type' => 'nullable|string|in:manager,office,sale,collaborator',
            'attendance_exempt' => 'nullable|boolean',
            'level' => 'nullable|string|max:100',
            'manager_id' => 'nullable|integer|exists:employees,id',
            'manager_l2_id' => 'nullable|integer|exists:employees,id',
            'join_date' => 'nullable|date',
            'leave_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned,probation'
        ], [
            'employee_code.required' => 'Vui lòng nhập mã nhân viên.',
            'name.required' => 'Vui lòng nhập họ tên nhân viên.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'status.required' => 'Vui lòng chọn trạng thái làm việc.',
        ]);

        $validated['attendance_exempt'] = $request->boolean('attendance_exempt');

        \App\Models\Employee::create($validated);

        return redirect()->route('backend.employees.index')->with('success', 'Thêm mới nhân viên thành công!');
    }

    public function importLeave(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120'
        ], [
            'file.required' => 'Vui lòng chọn file Excel hoặc CSV để tải lên.',
            'file.max' => 'Dung lượng file tối đa là 5MB.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
            return back()->with('import_leave_result', [
                'success' => false,
                'message' => 'Định dạng file không hợp lệ. Vui lòng tải lên file .xlsx, .xls hoặc .csv.'
            ]);
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, false, false);
            
            if (empty($rows)) {
                return back()->with('import_leave_result', [
                    'success' => false,
                    'message' => 'File tải lên không có dữ liệu.'
                ]);
            }

            // Detect headers from the first 5 rows
            $headerRowIndex = null;
            $codeCol = null;
            $balanceCol = null;
            $nameCol = null;

            $codeAliases = ['code', 'macc', 'machamcong', 'id', 'idnv'];
            $balanceAliases = ['phepnam', 'sophep', 'phepton', 'tonphep', 'phepconlai', 'phep', 'annualleave', 'leavebalance', 'balance', 'songayphep', 'sophepton', 'phepnamton', 'ngayphep', 'tonphepnam', 'sophepdunoiden'];

            foreach (array_slice($rows, 0, 5, true) as $rIdx => $row) {
                $foundCode = null;
                $foundBalance = null;
                $foundName = null;

                foreach ($row as $cIdx => $cellVal) {
                    if ($cellVal === null || trim((string)$cellVal) === '') continue;
                    $clean = preg_replace('/[^a-z0-9]/', '', strtolower(\Illuminate\Support\Str::ascii(trim((string)$cellVal))));
                    if ($clean === '') continue;

                    // Match employee code column
                    if ($foundCode === null) {
                        if (in_array($clean, $codeAliases, true) 
                            || str_contains($clean, 'manv') 
                            || str_contains($clean, 'manhanvien') 
                            || (str_contains($clean, 'ma') && (str_contains($clean, 'nv') || str_contains($clean, 'nhanvien') || str_contains($clean, 'cc')))) {
                            $foundCode = $cIdx;
                        }
                    }

                    // Match leave balance column
                    if ($foundBalance === null) {
                        if (in_array($clean, $balanceAliases, true) 
                            || str_contains($clean, 'phep') 
                            || str_contains($clean, 'leave')) {
                            $foundBalance = $cIdx;
                        }
                    }

                    // Match name column
                    if ($foundName === null) {
                        if (str_contains($clean, 'hoten') 
                            || str_contains($clean, 'hovaten') 
                            || str_contains($clean, 'tennhanvien') 
                            || str_contains($clean, 'tennv') 
                            || in_array($clean, ['name', 'fullname', 'ten'], true)) {
                            $foundName = $cIdx;
                        }
                    }
                }

                if ($foundCode !== null && $foundBalance !== null) {
                    $headerRowIndex = $rIdx;
                    $codeCol = $foundCode;
                    $balanceCol = $foundBalance;
                    $nameCol = $foundName;
                    break;
                }
            }

            // Fallback column detection if no clear header was found
            if ($codeCol === null || $balanceCol === null) {
                $firstRowVal1 = str_replace([' ', ','], ['', '.'], (string)($rows[0][1] ?? ''));
                $startRow = is_numeric($firstRowVal1) ? 0 : 1;

                $allEmployeeCodes = \Illuminate\Support\Facades\DB::table('employees')->pluck('employee_code')->map(fn($c) => trim((string)$c))->flip()->toArray();

                $sampleCodeCol1 = trim((string)($rows[$startRow][1] ?? ''));

                if (isset($allEmployeeCodes[$sampleCodeCol1])) {
                    $codeCol = 1;
                    $balanceCol = 2;
                } else {
                    $codeCol = 0;
                    $balanceCol = 1;
                }
                $dataRows = array_slice($rows, $startRow, null, true);
            } else {
                $dataRows = array_slice($rows, $headerRowIndex + 1, null, true);
            }

            // Cache all employees: employee_code => Employee object
            $allEmployees = \Illuminate\Support\Facades\DB::table('employees')
                ->select('id', 'employee_code', 'name', 'annual_leave_balance', 'deleted_at')
                ->get()
                ->keyBy(function($item) {
                    return trim((string)$item->employee_code);
                });

            $successList = [];
            $errorList = [];
            $totalProcessed = 0;

            foreach ($dataRows as $rIdx => $row) {
                $excelRowNum = $rIdx + 1; // 1-based index in Excel file

                // Skip completely empty rows
                $allEmpty = true;
                foreach ($row as $cell) {
                    if ($cell !== null && trim((string)$cell) !== '') {
                        $allEmpty = false;
                        break;
                    }
                }
                if ($allEmpty) {
                    continue;
                }

                $totalProcessed++;

                $rawCode = trim((string)($row[$codeCol] ?? ''));
                $rawBalance = trim((string)($row[$balanceCol] ?? ''));
                $empName = ($nameCol !== null) ? trim((string)($row[$nameCol] ?? '')) : '';

                // Check empty code
                if ($rawCode === '') {
                    $errorList[] = [
                        'row' => $excelRowNum,
                        'code' => '---',
                        'name' => $empName ?: '---',
                        'balance' => $rawBalance !== '' ? $rawBalance : '---',
                        'reason' => 'Mã nhân viên để trống'
                    ];
                    continue;
                }

                // Check empty balance
                if ($rawBalance === '') {
                    $emp = $allEmployees->get($rawCode);
                    $errorList[] = [
                        'row' => $excelRowNum,
                        'code' => $rawCode,
                        'name' => $empName ?: ($emp->name ?? '---'),
                        'balance' => '---',
                        'reason' => 'Số phép năm để trống'
                    ];
                    continue;
                }

                // Clean balance (replace comma with dot)
                $cleanBalance = str_replace([' ', ','], ['', '.'], $rawBalance);

                if (!is_numeric($cleanBalance)) {
                    $emp = $allEmployees->get($rawCode);
                    $errorList[] = [
                        'row' => $excelRowNum,
                        'code' => $rawCode,
                        'name' => $empName ?: ($emp->name ?? '---'),
                        'balance' => $rawBalance,
                        'reason' => "Số phép năm '{$rawBalance}' không hợp lệ (phải là số)"
                    ];
                    continue;
                }

                $leaveBalanceVal = (float)$cleanBalance;
                if ($leaveBalanceVal < 0) {
                    $emp = $allEmployees->get($rawCode);
                    $errorList[] = [
                        'row' => $excelRowNum,
                        'code' => $rawCode,
                        'name' => $empName ?: ($emp->name ?? '---'),
                        'balance' => $rawBalance,
                        'reason' => 'Số phép năm không được nhỏ hơn 0'
                    ];
                    continue;
                }

                // Match employee
                $emp = $allEmployees->get($rawCode);
                if (!$emp) {
                    // Try without leading zeros
                    $trimmedCode = ltrim($rawCode, '0');
                    if ($trimmedCode !== '' && $allEmployees->has($trimmedCode)) {
                        $emp = $allEmployees->get($trimmedCode);
                    }
                }

                if (!$emp) {
                    $errorList[] = [
                        'row' => $excelRowNum,
                        'code' => $rawCode,
                        'name' => $empName ?: '---',
                        'balance' => $leaveBalanceVal,
                        'reason' => 'Mã nhân viên không tồn tại trong hệ thống'
                    ];
                    continue;
                }

                // Update employee annual leave balance
                \Illuminate\Support\Facades\DB::table('employees')
                    ->where('id', $emp->id)
                    ->update([
                        'annual_leave_balance' => $leaveBalanceVal,
                        'updated_at' => now(),
                    ]);

                $successList[] = [
                    'row' => $excelRowNum,
                    'code' => $emp->employee_code,
                    'name' => $emp->name,
                    'balance' => $leaveBalanceVal,
                ];
            }

            $successCount = count($successList);
            $errorCount = count($errorList);

            return back()->with('import_leave_result', [
                'success' => true,
                'total' => $totalProcessed,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errorList,
            ]);
        } catch (\Throwable $e) {
            return back()->with('import_leave_result', [
                'success' => false,
                'message' => 'Lỗi khi đọc file Excel: ' . $e->getMessage()
            ]);
        }
    }

    public function edit($id)
    {
        $employee = \App\Models\Employee::withTrashed()->findOrFail($id);
        $departments = \Illuminate\Support\Facades\DB::table('departments')
            ->where(function($q) use ($employee) {
                $q->where('status', 'active');
                if ($employee->department_id) {
                    $q->orWhere('id', $employee->department_id);
                }
            })
            ->orderBy('name')
            ->get();
        $managers = \Illuminate\Support\Facades\DB::table('employees as e')
                ->whereIn('e.status', ['active', 'probation'])
                ->whereNull('e.deleted_at')
                ->whereIn('e.position', ['team_leader', 'manager', 'director'])
                ->orderBy('e.name')
                ->get(['e.id', 'e.name', 'e.employee_code', 'e.department_id']);
        $hrs = $managers;
        return view('backend.employees.edit', compact('employee', 'departments', 'managers', 'hrs'));
    }

    public function update(Request $request, $id)
    {
        $employee = \App\Models\Employee::withTrashed()->findOrFail($id);

        $existing = \App\Models\Employee::withTrashed()
            ->where('employee_code', $request->employee_code)
            ->where('id', '!=', $id)
            ->first();
        if ($existing) {
            if ($existing->trashed()) {
                $errorMsg = "Mã nhân viên '{$request->employee_code}' đã tồn tại trong danh sách 'Đã xóa / Ẩn' (Nhân viên: {$existing->name}).";
            } else {
                $errorMsg = "Mã nhân viên '{$request->employee_code}' đã tồn tại trong hệ thống (Nhân viên: {$existing->name}).";
            }
            return back()->withInput()->withErrors(['employee_code' => $errorMsg]);
        }

        $validated = $request->validate([
            'employee_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'department_id' => 'nullable|integer|exists:departments,id',
            'position' => 'nullable|string',
            'evaluation_type' => 'nullable|string|in:manager,office,sale,collaborator',
            'attendance_exempt' => 'nullable|boolean',
            'level' => 'nullable|string|max:100',
            'manager_id' => 'nullable|integer|exists:employees,id',
            'manager_l2_id' => 'nullable|integer|exists:employees,id',
            'join_date' => 'nullable|date',
            'leave_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,resigned,probation'
        ], [
            'employee_code.required' => 'Vui lòng nhập mã nhân viên.',
            'name.required' => 'Vui lòng nhập họ tên nhân viên.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'status.required' => 'Vui lòng chọn trạng thái làm việc.',
        ]);

        if ($request->manager_id == $id || $request->manager_l2_id == $id) {
            return back()->withInput()->withErrors(['manager_id' => 'Không thể chọn chính mình làm người duyệt.']);
        }

        $validated['attendance_exempt'] = $request->boolean('attendance_exempt');

        $employee->update($validated);

        return redirect()->route('backend.employees.index')->with('success', 'Cập nhật thông tin nhân viên thành công!');
    }

    public function destroy($id)
    {
        $employee = \App\Models\Employee::findOrFail($id);
        $employee->delete();
        
        return back()->with('success', 'Xóa nhân viên thành công!');
    }

    public function restore($id)
    {
        $employee = \App\Models\Employee::withTrashed()->findOrFail($id);
        $employee->restore();
        
        return back()->with('success', 'Khôi phục nhân viên thành công!');
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,resigned,probation',
            'annual_leave_balance' => 'nullable|numeric|min:0',
            'join_date' => 'nullable|date'
        ]);

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->status = $request->status;
        if ($request->has('annual_leave_balance')) {
            $employee->annual_leave_balance = $request->annual_leave_balance;
        }
        if ($request->has('join_date')) {
            $employee->join_date = $request->join_date;
        }
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'annual_leave_balance' => $employee->annual_leave_balance
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

    public function changeEvaluationType(Request $request, $id)
    {
        $request->validate([
            'evaluation_type' => 'required|string|in:manager,office,sale,collaborator'
        ]);

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->evaluation_type = $request->evaluation_type;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật loại tiêu chí đánh giá thành công!'
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

    public function changeAnnualLeave(Request $request, $id)
    {
        $request->validate([
            'annual_leave_balance' => 'required|numeric'
        ]);

        $employee = \App\Models\Employee::findOrFail($id);
        $employee->annual_leave_balance = $request->annual_leave_balance;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật phép năm thành công!'
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
            'evaluation_type' => 'nullable|string|in:manager,office,sale,collaborator',
            'manager_id' => 'nullable|integer|exists:employees,id',
            'manager_l2_id' => 'nullable|integer|exists:employees,id'
        ]);

        $departmentId = $request->department_id;
        $evaluationType = $request->evaluation_type;
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
        if ($request->has('department_id')) $dataToUpdate['department_id'] = $departmentId;
        if ($request->has('evaluation_type')) $dataToUpdate['evaluation_type'] = $evaluationType;
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
