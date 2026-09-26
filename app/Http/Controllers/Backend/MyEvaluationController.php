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

        $evaluation = \App\Models\Evaluation::create([
            'employee_id' => $user->employee_id,
            'month' => $month,
            'year' => $year,
            'status' => 'draft',
        ]);

        // Tạo sẵn các details từ bảng criteria
        $criteria = \App\Models\EvaluationCriterion::where('is_active', 1)->get();
        foreach ($criteria as $criterion) {
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
        $evaluation = \App\Models\Evaluation::with(['details.criterion.parent'])->findOrFail($id);
        
        if ($evaluation->employee_id != $user->employee_id) {
            abort(403);
        }

        // Lấy tất cả criteria để hiển thị dạng cây
        $criteriaTree = \App\Models\EvaluationCriterion::whereNull('parent_id')->with('children')->where('is_active', 1)->orderBy('order', 'asc')->get();

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

        $scores = $request->input('scores', []);
        $totalSelfScore = 0;

        $details = \App\Models\EvaluationDetail::where('evaluation_id', $evaluation->id)->get();
        foreach ($details as $detail) {
            // Nếu có trong mảng scores (tức là được tick), lấy giá trị (chính là max_score). Nếu không thì 0.
            $score = isset($scores[$detail->id]) ? (int)$scores[$detail->id] : 0;
            $detail->self_score = $score;
            $detail->save();
            $totalSelfScore += $score;
        }

        $evaluation->self_total_score = $totalSelfScore;
        $evaluation->self_note = $request->input('self_note');
        
        if ($request->has('submit_evaluation')) {
            $evaluation->status = 'submitted';
        } else {
            $evaluation->status = 'draft';
        }

        $evaluation->save();

        return redirect()->route('backend.my-evaluations.index')->with('success', 'Đã lưu phiếu đánh giá.');
    }
}
