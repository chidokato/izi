<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EvaluationCriterion;

class EvaluationCriterionController extends Controller
{
    public function index(Request $request)
    {
        $currentType = $request->input('type', 'all');
        $targetTypes = EvaluationCriterion::getTargetTypes();

        $query = EvaluationCriterion::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('order', 'asc');
            }])
            ->orderBy('order', 'asc');

        $allCriteria = $query->get();

        if ($currentType !== 'all') {
            $criteria = $allCriteria->filter(function ($parent) use ($currentType) {
                $matchingChildren = $parent->children->filter(fn($child) => $child->appliesTo($currentType));
                if ($parent->children->isNotEmpty()) {
                    $parent->setRelation('children', $matchingChildren);
                    return $matchingChildren->isNotEmpty() || $parent->appliesTo($currentType);
                }
                return $parent->appliesTo($currentType);
            });
        } else {
            $criteria = $allCriteria;
        }

        return view('backend.evaluation_criteria.index', compact('criteria', 'targetTypes', 'currentType'));
    }

    public function create()
    {
        $parents = EvaluationCriterion::whereNull('parent_id')->orderBy('order', 'asc')->get();
        $targetTypes = EvaluationCriterion::getTargetTypes();
        return view('backend.evaluation_criteria.create', compact('parents', 'targetTypes'));
    }

    public function store(Request $request)
    {
        if ($request->input('parent_id') === '') {
            $request->merge(['parent_id' => null]);
        }

        $request->validate([
            'parent_id' => 'nullable|exists:evaluation_criteria,id',
            'name' => 'required|string|max:500',
            'max_score' => 'nullable|integer|min:0',
            'order' => 'nullable|integer',
            'target_types' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        
        if (empty($data['max_score'])) {
            $data['max_score'] = null;
        }

        if (empty($data['parent_id'])) {
            $data['parent_id'] = null;
        }

        $targets = (array)$request->input('target_types', []);
        if (empty($targets) || in_array('all', $targets)) {
            $data['target_types'] = ['all'];
        } else {
            $data['target_types'] = array_values($targets);
        }

        EvaluationCriterion::create($data);

        return redirect()->route('backend.evaluation-criteria.index')->with('success', 'Thêm tiêu chí thành công.');
    }

    public function edit($id)
    {
        $criterion = EvaluationCriterion::findOrFail($id);
        $parents = EvaluationCriterion::whereNull('parent_id')->where('id', '!=', $id)->orderBy('order', 'asc')->get();
        $targetTypes = EvaluationCriterion::getTargetTypes();
        return view('backend.evaluation_criteria.edit', compact('criterion', 'parents', 'targetTypes'));
    }

    public function update(Request $request, $id)
    {
        if ($request->input('parent_id') === '') {
            $request->merge(['parent_id' => null]);
        }

        $request->validate([
            'parent_id' => 'nullable|exists:evaluation_criteria,id',
            'name' => 'required|string|max:500',
            'max_score' => 'nullable|integer|min:0',
            'order' => 'nullable|integer',
            'target_types' => 'nullable|array',
        ]);

        $criterion = EvaluationCriterion::findOrFail($id);
        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        
        if (empty($data['max_score'])) {
            $data['max_score'] = null;
        }

        if (empty($data['parent_id'])) {
            $data['parent_id'] = null;
        }

        $targets = (array)$request->input('target_types', []);
        if (empty($targets) || in_array('all', $targets)) {
            $data['target_types'] = ['all'];
        } else {
            $data['target_types'] = array_values($targets);
        }

        $criterion->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật tiêu chí thành công.',
                'criterion' => $criterion,
                'target_types' => $criterion->target_types,
                'target_type_names' => $criterion->target_type_names,
            ]);
        }

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
