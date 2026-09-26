<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EvaluationCriterion;

class EvaluationCriterionController extends Controller
{
    public function index()
    {
        // Get only root criteria (parent_id is null) with their children
        $criteria = EvaluationCriterion::whereNull('parent_id')->with('children')->orderBy('order', 'asc')->get();
        return view('backend.evaluation_criteria.index', compact('criteria'));
    }

    public function create()
    {
        $parents = EvaluationCriterion::whereNull('parent_id')->orderBy('order', 'asc')->get();
        return view('backend.evaluation_criteria.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:evaluation_criteria,id',
            'name' => 'required|string|max:255',
            'max_score' => 'nullable|integer|min:0',
            'order' => 'nullable|integer',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        
        // If it's a parent (has children later), max_score might be empty/null
        if (empty($data['max_score'])) {
            $data['max_score'] = null;
        }

        EvaluationCriterion::create($data);

        return redirect()->route('backend.evaluation-criteria.index')->with('success', 'Thêm tiêu chí thành công.');
    }

    public function edit($id)
    {
        $criterion = EvaluationCriterion::findOrFail($id);
        $parents = EvaluationCriterion::whereNull('parent_id')->where('id', '!=', $id)->orderBy('order', 'asc')->get();
        return view('backend.evaluation_criteria.edit', compact('criterion', 'parents'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:evaluation_criteria,id',
            'name' => 'required|string|max:255',
            'max_score' => 'nullable|integer|min:0',
            'order' => 'nullable|integer',
        ]);

        $criterion = EvaluationCriterion::findOrFail($id);
        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        
        if (empty($data['max_score'])) {
            $data['max_score'] = null;
        }

        $criterion->update($data);

        return redirect()->route('backend.evaluation-criteria.index')->with('success', 'Cập nhật tiêu chí thành công.');
    }

    public function destroy($id)
    {
        $criterion = EvaluationCriterion::findOrFail($id);
        $criterion->delete();

        return redirect()->route('backend.evaluation-criteria.index')->with('success', 'Xóa tiêu chí thành công.');
    }

    public function duplicate($id)
    {
        $criterion = EvaluationCriterion::with('children')->findOrFail($id);
        
        // Deep clone
        $newCriterion = $criterion->replicate();
        $newCriterion->name = $newCriterion->name . ' (Bản sao)';
        $newCriterion->save();

        // Duplicate children if any
        if ($criterion->children->isNotEmpty()) {
            foreach ($criterion->children as $child) {
                $newChild = $child->replicate();
                $newChild->parent_id = $newCriterion->id;
                $newChild->save();
            }
        }

        return redirect()->route('backend.evaluation-criteria.edit', $newCriterion->id)->with('success', 'Đã nhân bản tiêu chí. Bạn đang xem bản sao.');
    }
}
