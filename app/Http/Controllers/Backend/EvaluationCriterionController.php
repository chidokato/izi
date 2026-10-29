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

        $allCriteria = EvaluationCriterion::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('order', 'asc');
            }])
            ->orderBy('order', 'asc')
            ->get();

        if ($currentType !== 'all') {
            $criteria = $allCriteria->filter(function ($parent) use ($currentType) {
                // Tiêu chí cha phải áp dụng cho currentType
                if (!$parent->appliesTo($currentType)) {
                    return false;
                }
                // Chỉ giữ lại các tiêu chí con thuộc về currentType
                $matchingChildren = $parent->children->filter(fn($child) => $child->appliesTo($currentType));
                $parent->setRelation('children', $matchingChildren);
                return true;
            });
        } else {
            $criteria = $allCriteria;
        }

        // Danh sách tiêu chí cha kèm đối tượng cho dropdown modal
        $allParents = $allCriteria->map(function ($p) {
            $targets = $p->target_types ?? ['all'];
            return [
                'id' => $p->id,
                'name' => $p->name,
                'target_types' => $targets,
                'target_type_names' => implode(', ', $p->target_type_names),
            ];
        });

        return view('backend.evaluation_criteria.index', compact('criteria', 'targetTypes', 'currentType', 'allParents'));
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
        $targets = (array)$request->input('target_types', []);

        if (empty($data['parent_id'])) {
            $data['parent_id'] = null;
            $data['max_score'] = null; // Tiêu chí cha không nhập điểm trực tiếp
            if (empty($targets) || in_array('all', $targets)) {
                $data['target_types'] = ['all'];
            } else {
                $data['target_types'] = array_values($targets);
            }
        } else {
            // Tiêu chí con: chấp nhận điểm 0, chỉ chuyển thành null khi bỏ trống
            if ($data['max_score'] === '' || $data['max_score'] === null) {
                $data['max_score'] = null;
            } else {
                $data['max_score'] = (int)$data['max_score'];
            }

            // Tiêu chí con: CHỈ được chọn các đối tượng có trong tiêu chí cha
            $parent = EvaluationCriterion::findOrFail($data['parent_id']);
            $parentTargets = $parent->target_types ?? ['all'];

            if (empty($parentTargets) || in_array('all', $parentTargets)) {
                if (empty($targets) || in_array('all', $targets)) {
                    $data['target_types'] = ['all'];
                } else {
                    $data['target_types'] = array_values($targets);
                }
            } else {
                if (in_array('all', $targets)) {
                    $data['target_types'] = $parentTargets;
                } else {
                    $allowed = array_values(array_intersect($targets, $parentTargets));
                    $data['target_types'] = !empty($allowed) ? $allowed : $parentTargets;
                }
            }
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
        $targets = (array)$request->input('target_types', []);

        if (empty($data['parent_id'])) {
            $data['parent_id'] = null;
            $data['max_score'] = null; // Tiêu chí cha không nhập điểm trực tiếp
            if (empty($targets) || in_array('all', $targets)) {
                $data['target_types'] = ['all'];
            } else {
                $data['target_types'] = array_values($targets);
            }
        } else {
            // Tiêu chí con: chấp nhận điểm 0, chỉ chuyển thành null khi bỏ trống
            if ($data['max_score'] === '' || $data['max_score'] === null) {
                $data['max_score'] = null;
            } else {
                $data['max_score'] = (int)$data['max_score'];
            }

            // Tiêu chí con: CHỈ được chọn các đối tượng có trong tiêu chí cha
            $parent = EvaluationCriterion::findOrFail($data['parent_id']);
            $parentTargets = $parent->target_types ?? ['all'];

            if (empty($parentTargets) || in_array('all', $parentTargets)) {
                if (empty($targets) || in_array('all', $targets)) {
                    $data['target_types'] = ['all'];
                } else {
                    $data['target_types'] = array_values($targets);
                }
            } else {
                if (in_array('all', $targets)) {
                    $data['target_types'] = $parentTargets;
                } else {
                    $allowed = array_values(array_intersect($targets, $parentTargets));
                    $data['target_types'] = !empty($allowed) ? $allowed : $parentTargets;
                }
            }
        }

        $criterion->update($data);

        // Nếu là tiêu chí cha: kiểm tra và lọc lại đối tượng của toàn bộ con nếu con có đối tượng nằm ngoài cha
        if (!$criterion->parent_id) {
            $newParentTargets = $criterion->target_types ?? ['all'];
            if (!in_array('all', $newParentTargets)) {
                foreach ($criterion->children as $child) {
                    $childTargets = $child->target_types ?? ['all'];
                    if (in_array('all', $childTargets)) {
                        $child->update(['target_types' => $newParentTargets]);
                    } else {
                        $validChildTargets = array_values(array_intersect($childTargets, $newParentTargets));
                        $child->update(['target_types' => !empty($validChildTargets) ? $validChildTargets : $newParentTargets]);
                    }
                }
            }
        }

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
                $newChild->target_types = $child->target_types;
                $newChild->save();
            }
        }

        return redirect()->route('backend.evaluation-criteria.edit', $newCriterion->id)->with('success', 'Đã nhân bản tiêu chí. Bạn đang xem bản sao.');
    }
}
