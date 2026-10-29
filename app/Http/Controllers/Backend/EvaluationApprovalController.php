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
    public function index()
    {
        $user = auth()->user();
        $query = Evaluation::with('employee');

        if (!$user->employee_id) {
            return redirect()->route('backend.admin.dashboard')->with('error', 'Tài khoản chưa liên kết nhân viên.');
        }

        $directReportIds = Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray();
        $hrReportIds = Employee::where('manager_l2_id', $user->employee_id)->pluck('id')->toArray();
        
        // Nếu là Super Admin (permission=1) thì cho phép xem tất cả
        $isSuperAdmin = $user->permission == 1;

        if (empty($directReportIds) && empty($hrReportIds) && !$isSuperAdmin) {
            return redirect()->route('backend.admin.dashboard')->with('error', 'Bạn không có quyền duyệt phiếu.');
        }

        if (!$isSuperAdmin) {
            $query->where(function ($q) use ($directReportIds, $hrReportIds) {
                if (!empty($directReportIds)) {
                    $q->orWhereIn('employee_id', $directReportIds);
                }
                if (!empty($hrReportIds)) {
                    $q->orWhereIn('employee_id', $hrReportIds);
                }
            });
        }

        // Chỉ hiện các phiếu đã gửi duyệt trở đi
        $query->whereIn('status', ['submitted', 'manager_reviewed', 'hr_approved']);

        $evaluations = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        $grades = EvaluationGrade::orderBy('min_score', 'asc')->get();

        return view('backend.evaluation_approvals.index', compact('evaluations', 'grades'));
    }

    public function edit($id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::with(['employee', 'details.criterion.parent'])->findOrFail($id);

        $directReportIds = Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray();
        $hrReportIds = Employee::where('manager_l2_id', $user->employee_id)->pluck('id')->toArray();
        $isSuperAdmin = $user->permission == 1;

        $isManager = in_array($evaluation->employee_id, $directReportIds);
        $isHR = in_array($evaluation->employee_id, $hrReportIds) || $isSuperAdmin;

        // Phân quyền truy cập
        if (!$isHR && !$isManager) {
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

        return view('backend.evaluation_approvals.edit', compact('evaluation', 'criteriaTree', 'grades', 'isHR', 'isManager'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::findOrFail($id);
        
        $directReportIds = Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray();
        $hrReportIds = Employee::where('manager_l2_id', $user->employee_id)->pluck('id')->toArray();
        $isSuperAdmin = $user->permission == 1;

        $isManager = in_array($evaluation->employee_id, $directReportIds);
        $isHR = in_array($evaluation->employee_id, $hrReportIds) || $isSuperAdmin;

        if (!$isHR && !$isManager) {
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
