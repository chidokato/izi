<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyEvaluationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->employee_id) {
            return redirect()->route('backend.admin.dashboard')->with('error', 'Tài khoản của bạn chưa được liên kết với hồ sơ nhân viên.');
        }

        $evaluations = \App\Models\Evaluation::where('employee_id', $user->employee_id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $grades = \App\Models\EvaluationGrade::orderBy('min_score', 'asc')->get();

        return view('backend.my_evaluations.index', compact('evaluations', 'grades'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->employee_id) {
            return redirect()->back()->with('error', 'Tài khoản chưa được liên kết nhân viên.');
        }

        $currentDay = date('j');
        if ($currentDay < 16 || $currentDay > 30) {
            return redirect()->route('backend.my-evaluations.index')->with('error', 'Chức năng tự đánh giá chỉ mở từ ngày 16 đến ngày 30 hàng tháng.');
        }

        $month = date('n');
        $year = date('Y');

        // Kiểm tra xem đã có phiếu tháng này chưa
        $exists = \App\Models\Evaluation::where('employee_id', $user->employee_id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($exists) {
            return redirect()->route('backend.my-evaluations.edit', $exists->id)->with('info', 'Phiếu đánh giá tháng này đã tồn tại.');
        }

        $employee = \App\Models\Employee::find($user->employee_id);
        $evalType = ($employee && $employee->evaluation_type) ? $employee->evaluation_type : 'office';

        $evaluation = \App\Models\Evaluation::create([
            'employee_id' => $user->employee_id,
            'evaluation_type' => $evalType,
            'month' => $month,
            'year' => $year,
            'status' => 'draft',
        ]);

        // Tạo sẵn các details từ bảng criteria theo loại đánh giá
        $criteria = \App\Models\EvaluationCriterion::where('is_active', 1)->get();
        foreach ($criteria as $criterion) {
            if (!$criterion->appliesTo($evalType)) {
                continue;
            }
            $hasChildren = $criteria->where('parent_id', $criterion->id)->count() > 0;
            if (!$hasChildren) {
                \App\Models\EvaluationDetail::create([
                    'evaluation_id' => $evaluation->id,
                    'criteria_id' => $criterion->id,
                    'self_score' => 0,
                    'manager_score' => 0,
                    'hr_score' => 0,
                ]);
            }
        }

        return redirect()->route('backend.my-evaluations.edit', $evaluation->id)->with('success', 'Đã tự động tạo phiếu đánh giá cho tháng hiện tại. Vui lòng tự chấm điểm.');
    }

    public function store(Request $request)
    {
        // Method này không còn cần thiết nếu ta bỏ qua form create, nhưng vẫn giữ để dự phòng
        return redirect()->route('backend.my-evaluations.create');
    }

    public function edit($id)
    {
        $user = auth()->user();
        $evaluation = \App\Models\Evaluation::with(['employee.department', 'details.criterion.parent'])->findOrFail($id);
        
        if ($evaluation->employee_id != $user->employee_id) {
            abort(403);
        }

        // Lấy tiêu chí áp dụng cho loại nhân sự này
        $evalType = $evaluation->evaluation_type ?: ($evaluation->employee->evaluation_type ?? 'office');
        $detailCriteriaIds = $evaluation->details->pluck('criteria_id')->toArray();

        $criteriaTree = \App\Models\EvaluationCriterion::whereNull('parent_id')
            ->where('is_active', 1)
            ->with(['children' => function ($q) {
                $q->where('is_active', 1)->orderBy('order', 'asc');
            }])
            ->orderBy('order', 'asc')
            ->get()
            ->filter(function ($parent) use ($evalType, $detailCriteriaIds) {
                // Lọc con: thuộc evaluation_details hoặc appliesTo
                $matchingChildren = $parent->children->filter(function ($child) use ($evalType, $detailCriteriaIds) {
                    return in_array($child->id, $detailCriteriaIds) || $child->appliesTo($evalType);
                });

                if ($parent->children->isNotEmpty()) {
                    $parent->setRelation('children', $matchingChildren);
                    return $matchingChildren->isNotEmpty();
                }

                return in_array($parent->id, $detailCriteriaIds) || $parent->appliesTo($evalType);
            });

        // Lấy danh sách các xếp loại
        $grades = \App\Models\EvaluationGrade::orderBy('min_score', 'asc')->get();

        return view('backend.my_evaluations.edit', compact('evaluation', 'criteriaTree', 'grades'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $evaluation = \App\Models\Evaluation::findOrFail($id);

        if ($evaluation->employee_id != $user->employee_id) {
            abort(403);
        }

        if (in_array($evaluation->status, ['manager_reviewed', 'hr_approved'])) {
            return redirect()->back()->with('error', 'Phiếu đánh giá đã được duyệt, không thể chỉnh sửa.');
        }

        $parentChoices = $request->input('parent_choice', []);
        $singleChoices = $request->input('single_choice', []);
        $selectedDetailIds = array_map('intval', array_values(array_filter(array_merge((array)$parentChoices, (array)$singleChoices))));

        $details = \App\Models\EvaluationDetail::with('criterion')->where('evaluation_id', $evaluation->id)->get();
        $totalSelfScore = 0;

        foreach ($details as $detail) {
            $isSelected = in_array((int)$detail->id, $selectedDetailIds, true);
            $detail->is_self_selected = $isSelected;
            $detail->self_score = $isSelected ? (float)($detail->criterion->max_score ?? 0) : 0;
            $detail->save();

            if ($isSelected) {
                $totalSelfScore += $detail->self_score;
            }
        }

        $evaluation->self_total_score = $totalSelfScore;
        $matchedGrade = \App\Models\EvaluationGrade::getGradeForScore($totalSelfScore);
        $evaluation->grade_id = $matchedGrade ? $matchedGrade->id : null;
        $evaluation->self_note = $request->input('self_note');
        
        if ($request->has('submit_evaluation') || $request->has('submit')) {
            $evaluation->status = 'submitted';
        } else {
            $evaluation->status = 'draft';
        }

        $evaluation->save();

        return redirect()->route('backend.my-evaluations.index')->with('success', 'Đã lưu phiếu đánh giá.');
    }
}
