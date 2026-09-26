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
        $hrReportIds = Employee::where('hr_id', $user->employee_id)->pluck('id')->toArray();
        
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
        $hrReportIds = Employee::where('hr_id', $user->employee_id)->pluck('id')->toArray();
        $isSuperAdmin = $user->permission == 1;

        $isManager = in_array($evaluation->employee_id, $directReportIds);
        $isHR = in_array($evaluation->employee_id, $hrReportIds) || $isSuperAdmin;

        // Phân quyền truy cập
        if (!$isHR && !$isManager) {
            abort(403, 'Bạn không có quyền truy cập phiếu này.');
        }

        $criteriaTree = EvaluationCriterion::whereNull('parent_id')->with('children')->where('is_active', 1)->orderBy('order', 'asc')->get();
        $grades = EvaluationGrade::orderBy('min_score', 'asc')->get();

        return view('backend.evaluation_approvals.edit', compact('evaluation', 'criteriaTree', 'grades', 'isHR', 'isManager'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::findOrFail($id);
        
        $directReportIds = Employee::where('manager_id', $user->employee_id)->pluck('id')->toArray();
        $hrReportIds = Employee::where('hr_id', $user->employee_id)->pluck('id')->toArray();
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

        $scores = $request->input('scores', []);
        $totalScore = 0;
        $details = EvaluationDetail::where('evaluation_id', $evaluation->id)->get();

        foreach ($details as $detail) {
            $score = isset($scores[$detail->id]) ? (int)$scores[$detail->id] : 0;
            
            if ($actingAs == 'hr') {
                $detail->hr_score = $score;
            } else {
                $detail->manager_score = $score;
                if ($request->has('approve')) {
                    // Khi Quản lý bấm chốt duyệt, copy luôn điểm này sang cột của HR để làm điểm khởi tạo
                    $detail->hr_score = $score;
                }
            }
            $detail->save();
            $totalScore += $score;
        }

        if ($actingAs == 'hr') {
            $evaluation->final_score = $totalScore;
            $evaluation->hr_note = $request->input('note');
            
            if ($request->has('approve')) {
                $evaluation->status = 'hr_approved';
            }
        } else {
            $evaluation->manager_note = $request->input('note');
            
            if ($request->has('approve')) {
                $evaluation->status = 'manager_reviewed';
            }
        }

        $evaluation->save();

        return redirect()->route('backend.evaluation-approvals.index')->with('success', 'Đã lưu đánh giá thành công.');
    }
}
