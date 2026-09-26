<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EvaluationGrade;

class EvaluationGradeController extends Controller
{
    public function index()
    {
        $grades = EvaluationGrade::orderBy('min_score', 'desc')->get();
        return view('backend.evaluation_grades.index', compact('grades'));
    }

    public function create()
    {
        return view('backend.evaluation_grades.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer|gte:min_score',
        ]);

        EvaluationGrade::create($request->all());

        return redirect()->route('backend.evaluation-grades.index')->with('success', 'Thêm xếp loại thành công.');
    }

    public function edit($id)
    {
        $grade = EvaluationGrade::findOrFail($id);
        return view('backend.evaluation_grades.edit', compact('grade'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer|gte:min_score',
        ]);

        $grade = EvaluationGrade::findOrFail($id);
        $grade->update($request->all());

        return redirect()->route('backend.evaluation-grades.index')->with('success', 'Cập nhật xếp loại thành công.');
    }

    public function destroy($id)
    {
        $grade = EvaluationGrade::findOrFail($id);
        $grade->delete();

        return redirect()->route('backend.evaluation-grades.index')->with('success', 'Xóa xếp loại thành công.');
    }
}
