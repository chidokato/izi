<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Evaluation;
use App\Models\Employee;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationGrade;
use App\Models\EvaluationDetail;

class EvaluationApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->permission == 1 || $user->isAdmin();
        $baseQuery = Evaluation::with(['employee.department', 'details']);

        if (!$isSuperAdmin && !$user->employee_id) {
            return redirect()->route('backend.admin.dashboard')->with('error', 'Tài khoản chưa liên kết nhân viên.');
        }

        $directReportIds = $user->employee_id ? Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray() : [];
        $hrReportIds = $user->employee_id ? Employee::where('manager_l2_id', $user->employee_id)->pluck('id')->toArray() : [];

        if (empty($directReportIds) && empty($hrReportIds) && !$isSuperAdmin) {
            return redirect()->route('backend.admin.dashboard')->with('error', 'Bạn không có quyền duyệt phiếu.');
        }

        if (!$isSuperAdmin) {
            $baseQuery->where(function ($q) use ($directReportIds, $hrReportIds) {
                if (!empty($directReportIds)) {
                    $q->orWhereIn('employee_id', $directReportIds);
                }
                if (!empty($hrReportIds)) {
                    $q->orWhereIn('employee_id', $hrReportIds);
                }
            });
        }

        // Chỉ hiện các phiếu đã gửi duyệt trở đi
        $baseQuery->whereIn('status', ['submitted', 'manager_reviewed', 'hr_approved']);

        // Bộ lọc theo tháng, năm nếu có
        if ($request->filled('month')) {
            $baseQuery->where('month', (int)$request->month);
        }
        if ($request->filled('year')) {
            $baseQuery->where('year', (int)$request->year);
        }

        // Bộ lọc theo phòng ban nếu có
        if ($request->filled('department_id')) {
            $baseQuery->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Tìm kiếm theo tên nhân viên
        if ($request->filled('search')) {
            $search = trim($request->search);
            $baseQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Logic lọc cho tab "Chờ duyệt" (Các phiếu đang chờ đúng người này duyệt)
        $applyWaiting = function ($q) use ($isSuperAdmin, $directReportIds, $hrReportIds) {
            if ($isSuperAdmin) {
                $q->whereIn('status', ['submitted', 'manager_reviewed']);
            } else {
                $q->where(function ($sub) use ($directReportIds, $hrReportIds) {
                    if (!empty($directReportIds)) {
                        $sub->orWhere(function ($q1) use ($directReportIds) {
                            $q1->whereIn('employee_id', $directReportIds)
                               ->where('status', 'submitted');
                        });
                    }
                    if (!empty($hrReportIds)) {
                        $sub->orWhere(function ($q2) use ($hrReportIds) {
                            $q2->whereIn('employee_id', $hrReportIds)
                               ->where('status', 'manager_reviewed');
                        });
                    }
                });
            }
        };

        // Tính số lượng cho từng tab (Chờ duyệt, Chưa duyệt, Đã duyệt, Tất cả)
        $waitingQuery = (clone $baseQuery);
        $applyWaiting($waitingQuery);
        $waitingCount = $waitingQuery->count();

        $pendingCount = (clone $baseQuery)->whereIn('status', ['submitted', 'manager_reviewed'])->count();

        $approvedQuery = (clone $baseQuery);
        if (!$isSuperAdmin && empty($hrReportIds)) {
            $approvedQuery->whereIn('status', ['manager_reviewed', 'hr_approved']);
        } else {
            $approvedQuery->where('status', 'hr_approved');
        }
        $approvedCount = $approvedQuery->count();

        $allCount = (clone $baseQuery)->count();

        $counts = [
            'waiting' => $waitingCount,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'all' => $allCount,
        ];

        // Xác định tab hiện tại (nếu có phiếu chờ duyệt thì mặc định tab Chờ duyệt, nếu không thì Tất cả)
        $defaultStatus = $waitingCount > 0 ? 'waiting' : 'all';
        $currentStatus = $request->input('status', $defaultStatus);
        if (!in_array($currentStatus, ['waiting', 'pending', 'approved', 'all'])) {
            $currentStatus = $defaultStatus;
        }

        $query = (clone $baseQuery);

        if ($currentStatus === 'waiting') {
            $applyWaiting($query);
        } elseif ($currentStatus === 'pending') {
            $query->whereIn('status', ['submitted', 'manager_reviewed']);
        } elseif ($currentStatus === 'approved') {
            if (!$isSuperAdmin && empty($hrReportIds)) {
                $query->whereIn('status', ['manager_reviewed', 'hr_approved']);
            } else {
                $query->where('status', 'hr_approved');
            }
        }

        $evaluations = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $grades = EvaluationGrade::orderBy('min_score', 'asc')->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('backend.evaluation_approvals.index', compact('evaluations', 'grades', 'counts', 'currentStatus', 'departments'));
    }

    public function edit($id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::with(['employee.department', 'details.criterion.parent'])->findOrFail($id);

        $isSuperAdmin = $user->permission == 1 || $user->isAdmin();
        $directReportIds = $user->employee_id ? Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray() : [];
        $hrReportIds = $user->employee_id ? Employee::where('manager_l2_id', $user->employee_id)->pluck('id')->toArray() : [];

        $isManager = in_array($evaluation->employee_id, $directReportIds) || $isSuperAdmin;
        $isHR = in_array($evaluation->employee_id, $hrReportIds) || $isSuperAdmin;

        // Phân quyền truy cập
        if (!$isHR && !$isManager && !$isSuperAdmin) {
            abort(403, 'Bạn không có quyền truy cập phiếu này.');
        }

        $evalType = $evaluation->evaluation_type ?: ($evaluation->employee->evaluation_type ?? 'office');
        $detailCriteriaIds = $evaluation->details->pluck('criteria_id')->toArray();

        $criteriaTree = EvaluationCriterion::whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['children' => function ($q) {
                $q->where('is_active', 1)->orderBy('order', 'asc');
            }])
            ->orderBy('order', 'asc')
            ->get()
            ->filter(function ($parent) use ($evalType, $detailCriteriaIds) {
                $matchingChildren = $parent->children->filter(function ($child) use ($evalType, $detailCriteriaIds) {
                    return in_array($child->id, $detailCriteriaIds) || $child->appliesTo($evalType);
                });

                if ($parent->children->isNotEmpty()) {
                    $parent->setRelation('children', $matchingChildren);
                    return $matchingChildren->isNotEmpty();
                }

                return in_array($parent->id, $detailCriteriaIds) || $parent->appliesTo($evalType);
            });
        $grades = EvaluationGrade::orderBy('min_score', 'asc')->get();

        // Đảm bảo tất cả tiêu chí lá đều có bản ghi trong evaluation_details
        $existingCriteriaIds = $evaluation->details->pluck('criteria_id')->toArray();
        $missingDetails = [];
        foreach ($criteriaTree as $parent) {
            if ($parent->children->isNotEmpty()) {
                foreach ($parent->children as $child) {
                    if (!in_array($child->id, $existingCriteriaIds)) {
                        $missingDetails[] = [
                            'evaluation_id' => $evaluation->id,
                            'criteria_id' => $child->id,
                            'self_score' => 0,
                            'manager_score' => 0,
                            'hr_score' => 0,
                            'is_self_selected' => false,
                            'is_manager_selected' => false,
                            'is_hr_selected' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            } else {
                if (!in_array($parent->id, $existingCriteriaIds)) {
                    $missingDetails[] = [
                        'evaluation_id' => $evaluation->id,
                        'criteria_id' => $parent->id,
                        'self_score' => 0,
                        'manager_score' => 0,
                        'hr_score' => 0,
                        'is_self_selected' => false,
                        'is_manager_selected' => false,
                        'is_hr_selected' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        if (!empty($missingDetails)) {
            EvaluationDetail::insert($missingDetails);
            $evaluation->load('details.criterion.parent');
        }

        return view('backend.evaluation_approvals.edit', compact('evaluation', 'criteriaTree', 'grades', 'isHR', 'isManager'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::findOrFail($id);
        
        $isSuperAdmin = $user->permission == 1 || $user->isAdmin();
        $directReportIds = $user->employee_id ? Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray() : [];
        $hrReportIds = $user->employee_id ? Employee::where('manager_l2_id', $user->employee_id)->pluck('id')->toArray() : [];

        $isManager = in_array($evaluation->employee_id, $directReportIds) || $isSuperAdmin;
        $isHR = in_array($evaluation->employee_id, $hrReportIds) || $isSuperAdmin;

        if (!$isHR && !$isManager && !$isSuperAdmin) {
            abort(403);
        }

        // Xác định vai trò hiện tại đang thao tác dựa vào status
        $actingAs = 'viewer';
        if ($isHR) {
            $actingAs = 'hr';
        }
        if ($isManager && $evaluation->status == 'submitted') {
            $actingAs = 'manager';
        }

        if ($actingAs == 'viewer') {
            abort(403, 'Bạn không thể thao tác trên phiếu này lúc này.');
        }

        $details = EvaluationDetail::with('criterion')->where('evaluation_id', $evaluation->id)->get();
        $totalScore = 0;

        if ($actingAs == 'hr') {
            $hrChoices = $request->input('hr_choice', []);
            $singleChoices = $request->input('single_choice', []);
            $selectedDetailIds = array_map('intval', array_values(array_filter(array_merge((array)$hrChoices, (array)$singleChoices))));

            foreach ($details as $detail) {
                $isSelected = in_array((int)$detail->id, $selectedDetailIds, true);
                $detail->is_hr_selected = $isSelected;
                $detail->hr_score = $isSelected ? (float)($detail->criterion->max_score ?? 0) : 0;
                $detail->save();

                if ($isSelected) {
                    $totalScore += $detail->hr_score;
                }
            }
        } else {
            // Manager
            $managerChoices = $request->input('manager_choice', []);
            $singleChoices = $request->input('single_choice', []);
            $selectedDetailIds = array_map('intval', array_values(array_filter(array_merge((array)$managerChoices, (array)$singleChoices))));

            foreach ($details as $detail) {
                $isSelected = in_array((int)$detail->id, $selectedDetailIds, true);
                $detail->is_manager_selected = $isSelected;
                $detail->manager_score = $isSelected ? (float)($detail->criterion->max_score ?? 0) : 0;
                
                if ($request->has('approve')) {
                    // Khi Quản lý bấm chốt duyệt, copy luôn lựa chọn này sang cột của HR để làm điểm khởi tạo
                    $detail->is_hr_selected = $isSelected;
                    $detail->hr_score = $detail->manager_score;
                }
                $detail->save();

                if ($isSelected) {
                    $totalScore += $detail->manager_score;
                }
            }
        }

        if ($actingAs == 'hr') {
            $evaluation->final_score = $totalScore;
            $evaluation->hr_note = $request->input('note');
            $matchedGrade = EvaluationGrade::getGradeForScore($totalScore);
            $evaluation->grade_id = $matchedGrade ? $matchedGrade->id : null;
            
            if ($request->has('approve')) {
                $evaluation->status = 'hr_approved';
            }
        } else {
            $evaluation->manager_note = $request->input('note');
            $matchedGrade = EvaluationGrade::getGradeForScore($totalScore);
            $evaluation->grade_id = $matchedGrade ? $matchedGrade->id : null;
            
            if ($request->has('approve')) {
                $evaluation->status = 'manager_reviewed';
            }
        }

        $evaluation->save();

        return redirect()->route('backend.evaluation-approvals.index')->with('success', 'Đã lưu đánh giá thành công.');
    }
}
