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
    /**
     * Kiểm tra xem user có phải là HR (hoặc SuperAdmin / Admin) hay không
     */
    private function isHRUser($user): bool
    {
        if (!$user) return false;
        return $user->permission == 1 
            || $user->isAdmin() 
            || optional($user->employee)->level === 'HR';
    }

    /**
     * Xác định người duyệt lần 1 (Quản lý) của một nhân viên:
     * - Sale: HR là người duyệt trực tiếp -> không qua Quản lý (null)
     * - Back: Người duyệt lần 2 (cột manager_l2_id) là người duyệt lần 1; fallback manager_id
     *   Nếu không có -> null (đẩy thẳng lên HR)
     */
    private function getManagerReviewerId($employee, $evalType = null): ?int
    {
        if (!$employee) return null;
        $type = $evalType ?: ($employee->evaluation_type ?: 'office');
        if ($type === 'sale') {
            return null; // Sale: HR duyệt trực tiếp
        }
        return $employee->manager_l2_id ? (int)$employee->manager_l2_id : ($employee->manager_id ? (int)$employee->manager_id : null);
    }

    /**
     * Scope query để lọc phiếu Sale
     */
    private function scopeSaleEvaluation($query)
    {
        $query->where(function ($q) {
            $q->where('evaluations.evaluation_type', 'sale')
              ->orWhere(function ($q2) {
                  $q2->where(function ($sub) {
                      $sub->whereNull('evaluations.evaluation_type')
                          ->orWhere('evaluations.evaluation_type', '');
                  })->whereHas('employee', function ($qe) {
                      $qe->where('evaluation_type', 'sale');
                  });
              });
        });
    }

    /**
     * Scope query để lọc phiếu Back
     */
    private function scopeBackEvaluation($query)
    {
        $query->where(function ($q) {
            $q->where(function ($sub) {
                $sub->where('evaluations.evaluation_type', '!=', 'sale')
                    ->whereNotNull('evaluations.evaluation_type')
                    ->where('evaluations.evaluation_type', '!=', '');
            })->orWhere(function ($q2) {
                $q2->where(function ($sub) {
                    $sub->whereNull('evaluations.evaluation_type')
                        ->orWhere('evaluations.evaluation_type', '');
                })->whereHas('employee', function ($qe) {
                    $qe->where('evaluation_type', '!=', 'sale')
                       ->orWhereNull('evaluation_type');
                });
            });
        });
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isHR = $this->isHRUser($user);
        $userEmpId = $user->employee_id;

        // Quản lý của Back: là người ở cột manager_l2_id (hoặc manager_id) của các nhân viên Back
        $managedBackEmployeeIds = [];
        if ($userEmpId) {
            $managedBackEmployeeIds = Employee::where(function ($q) use ($userEmpId) {
                $q->where('manager_l2_id', $userEmpId)
                  ->orWhere(function ($q2) use ($userEmpId) {
                      $q2->whereNull('manager_l2_id')->where('manager_id', $userEmpId);
                  });
            })->where(function ($q) {
                $q->where('evaluation_type', '!=', 'sale')
                  ->orWhereNull('evaluation_type');
            })->pluck('id')->toArray();
        }

        $isManager = !empty($managedBackEmployeeIds);

        // Quyền truy cập: chỉ HR hoặc Quản lý được duyệt
        if (!$isHR && !$isManager) {
            return redirect()->route('backend.admin.dashboard')->with('error', 'Bạn không có quyền duyệt phiếu.');
        }

        $baseQuery = Evaluation::with(['employee.department', 'details']);

        if (!$isHR) {
            // Quản lý thông thường chỉ xem các nhân viên Back thuộc quyền quản lý của mình
            $baseQuery->whereIn('employee_id', $managedBackEmployeeIds);
            $this->scopeBackEvaluation($baseQuery);
        }

        // Chỉ hiện các phiếu đã gửi duyệt trở đi
        $baseQuery->whereIn('status', ['submitted', 'manager_reviewed', 'hr_approved']);

        // Bộ lọc theo tháng, năm (Mặc định chọn tháng và năm hiện tại nếu chưa chọn)
        $selectedMonth = $request->has('month') ? $request->input('month') : (int)date('n');
        $selectedYear = $request->has('year') ? $request->input('year') : (int)date('Y');

        if ($selectedMonth !== 'all' && $selectedMonth !== '' && $selectedMonth !== null) {
            $baseQuery->where('month', (int)$selectedMonth);
        }
        if ($selectedYear !== 'all' && $selectedYear !== '' && $selectedYear !== null) {
            $baseQuery->where('year', (int)$selectedYear);
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

        // Logic lọc cho tab "Chờ duyệt":
        // - Sale: HR duyệt trực tiếp khi submitted
        // - Back: Người ở cột Người duyệt lần 2 duyệt lần 1 khi submitted; nếu không có thì đẩy lên HR khi submitted
        // - Cấp 2: Quản lý duyệt xong (manager_reviewed) thì đẩy lên HR chốt
        $applyWaiting = function ($q) use ($isHR, $userEmpId) {
            $q->where(function ($sub) use ($isHR, $userEmpId) {
                if ($isHR) {
                    // 1. HR duyệt trực tiếp cho Sale khi submitted
                    $sub->orWhere(function ($qSale) {
                        $qSale->where('status', 'submitted');
                        $this->scopeSaleEvaluation($qSale);
                    });
                    // 2. HR duyệt trực tiếp cho Back không có người duyệt khi submitted
                    $sub->orWhere(function ($qBackNoMgr) {
                        $qBackNoMgr->where('status', 'submitted')
                                   ->whereHas('employee', function ($qe) {
                                       $qe->whereNull('manager_l2_id')
                                          ->whereNull('manager_id');
                                   });
                        $this->scopeBackEvaluation($qBackNoMgr);
                    });
                    // 3. HR duyệt/chốt cho tất cả các phiếu đã qua quản lý duyệt (manager_reviewed)
                    $sub->orWhere('status', 'manager_reviewed');
                }

                if ($userEmpId) {
                    // Quản lý duyệt lần 1 cho nhân viên Back của mình khi submitted
                    $sub->orWhere(function ($qMgr) use ($userEmpId) {
                        $qMgr->where('status', 'submitted')
                             ->whereHas('employee', function ($qe) use ($userEmpId) {
                                 $qe->where(function ($qm) use ($userEmpId) {
                                     $qm->where('manager_l2_id', $userEmpId)
                                        ->orWhere(function ($qm1) use ($userEmpId) {
                                            $qm1->whereNull('manager_l2_id')
                                                ->where('manager_id', $userEmpId);
                                        });
                                 });
                             });
                        $this->scopeBackEvaluation($qMgr);
                    });
                }
            });
        };

        // Tính số lượng cho từng tab (Chờ duyệt, Chưa duyệt, Đã duyệt, Tất cả, Chưa làm đánh giá)
        $waitingQuery = (clone $baseQuery);
        $applyWaiting($waitingQuery);
        $waitingCount = $waitingQuery->count();

        $pendingCount = (clone $baseQuery)->whereIn('status', ['submitted', 'manager_reviewed'])->count();

        $approvedQuery = (clone $baseQuery);
        if (!$isHR) {
            // Quản lý cấp 1 đã duyệt xong phiếu của mình
            $approvedQuery->whereIn('status', ['manager_reviewed', 'hr_approved']);
        } else {
            // HR đã chốt
            $approvedQuery->where('status', 'hr_approved');
        }
        $approvedCount = $approvedQuery->count();

        $allCount = (clone $baseQuery)->count();

        // Xác định kỳ tháng/năm để kiểm tra danh sách nhân viên chưa làm đánh giá
        $targetMonth = ($selectedMonth !== 'all' && $selectedMonth !== '' && $selectedMonth !== null)
            ? (int)$selectedMonth
            : (int)date('n');
        $targetYear = ($selectedYear !== 'all' && $selectedYear !== '' && $selectedYear !== null)
            ? (int)$selectedYear
            : (int)date('Y');

        $missingEmpQuery = Employee::with([
            'department',
            'manager',
            'managerL2',
            'evaluations' => function ($q) use ($targetMonth, $targetYear) {
                $q->where('month', $targetMonth)->where('year', $targetYear);
            }
        ])->where(function ($q) {
            $q->whereIn('status', ['active', 'probation'])
              ->orWhereNull('status');
        });

        if (!$isHR) {
            // Quản lý chỉ xem các nhân viên thuộc quyền quản lý của mình
            $missingEmpQuery->whereIn('id', $managedBackEmployeeIds);
        }

        if ($request->filled('department_id')) {
            $missingEmpQuery->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $missingEmpQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        // Nhân viên chưa gửi phiếu đánh giá trong tháng/năm đang chọn
        $missingEmpQuery->whereDoesntHave('evaluations', function ($q) use ($targetMonth, $targetYear) {
            $q->where('month', $targetMonth)
              ->where('year', $targetYear)
              ->whereIn('status', ['submitted', 'manager_reviewed', 'hr_approved']);
        });

        $notEvaluatedCount = (clone $missingEmpQuery)->count();

        $counts = [
            'waiting' => $waitingCount,
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'all' => $allCount,
            'not_evaluated' => $notEvaluatedCount,
        ];

        // Xác định tab hiện tại (nếu có phiếu chờ duyệt thì mặc định tab Chờ duyệt, nếu không thì Tất cả)
        $defaultStatus = $waitingCount > 0 ? 'waiting' : 'all';
        $currentStatus = $request->input('status', $defaultStatus);
        if (!in_array($currentStatus, ['waiting', 'pending', 'approved', 'all', 'not_evaluated'])) {
            $currentStatus = $defaultStatus;
        }

        $notEvaluatedEmployees = null;
        if ($currentStatus === 'not_evaluated') {
            $notEvaluatedEmployees = $missingEmpQuery->orderBy('department_id')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
            $evaluations = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        } else {
            $query = (clone $baseQuery);

            if ($currentStatus === 'waiting') {
                $applyWaiting($query);
            } elseif ($currentStatus === 'pending') {
                $query->whereIn('status', ['submitted', 'manager_reviewed']);
            } elseif ($currentStatus === 'approved') {
                if (!$isHR) {
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
        }

        $grades = EvaluationGrade::orderBy('min_score', 'asc')->get();

        if ($isHR) {
            $departments = \App\Models\Department::orderBy('name')->get();
        } else {
            $managerDeptIds = [];
            if ($user->employee && $user->employee->department_id) {
                $managerDeptIds[] = $user->employee->department_id;
            }
            if (!empty($managedBackEmployeeIds)) {
                $managedDepts = Employee::whereIn('id', $managedBackEmployeeIds)
                    ->whereNotNull('department_id')
                    ->pluck('department_id')
                    ->toArray();
                $managerDeptIds = array_unique(array_merge($managerDeptIds, $managedDepts));
            }

            $departments = \App\Models\Department::whereIn('id', $managerDeptIds)->orderBy('name')->get();
        }

        return view('backend.evaluation_approvals.index', compact(
            'evaluations', 'grades', 'counts', 'currentStatus', 'departments', 'selectedMonth', 'selectedYear', 'notEvaluatedEmployees', 'targetMonth', 'targetYear'
        ));
    }

    public function edit($id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::with(['employee.department', 'details.criterion.parent'])->findOrFail($id);
        $employee = $evaluation->employee;

        $isHR = $this->isHRUser($user);
        $managerReviewerId = $this->getManagerReviewerId($employee, $evaluation->evaluation_type);
        $isManager = ($user->employee_id && $managerReviewerId && $user->employee_id == $managerReviewerId) || ($user->permission == 1);

        // Phân quyền truy cập
        if (!$isHR && !$isManager) {
            abort(403, 'Bạn không có quyền truy cập phiếu này.');
        }

        $isSale = ($evaluation->evaluation_type ?: optional($employee)->evaluation_type) === 'sale';

        // Xác định vai trò hiện tại đang thao tác:
        // - Sale: HR duyệt trực tiếp khi submitted
        // - Back: Người ở cột Người duyệt lần 2 duyệt lần 1 khi submitted; nếu không có thì đẩy lên HR trực tiếp
        // - Sau khi quản lý duyệt xong (manager_reviewed) -> đẩy lên HR chốt
        $actingAs = 'viewer';
        if ($evaluation->status == 'submitted') {
            if ($isSale || empty($managerReviewerId)) {
                // Sale hoặc Back không có quản lý -> HR duyệt trực tiếp!
                if ($isHR) {
                    $actingAs = 'hr';
                }
            } else {
                // Back có quản lý -> Người duyệt lần 2 duyệt lần 1!
                if ($isManager) {
                    $actingAs = 'manager';
                } elseif ($isHR && $user->permission == 1) {
                    // SuperAdmin có thể thao tác thay quản lý
                    $actingAs = 'manager';
                }
            }
        } elseif ($evaluation->status == 'manager_reviewed') {
            // Quản lý đã duyệt xong -> đẩy lên HR chốt!
            if ($isHR) {
                $actingAs = 'hr';
            }
        }

        // Tự động khởi tạo lựa chọn cho HR nếu là Sale hoặc Back không có quản lý (lấy từ NV tự chấm)
        if ($actingAs === 'hr') {
            $hasAnyHrSelected = $evaluation->details->where('is_hr_selected', true)->count() > 0;
            if (!$hasAnyHrSelected && ($isSale || empty($managerReviewerId))) {
                foreach ($evaluation->details as $d) {
                    if ($d->is_self_selected) {
                        $d->is_hr_selected = true;
                        $d->hr_score = $d->self_score;
                        $d->save();
                    }
                }
                $evaluation->load('details.criterion.parent');
            }
        }

        $evalType = $evaluation->evaluation_type ?: ($employee->evaluation_type ?? 'office');
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

        return view('backend.evaluation_approvals.edit', compact('evaluation', 'criteriaTree', 'grades', 'isHR', 'isManager', 'actingAs'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $evaluation = Evaluation::with('employee')->findOrFail($id);
        $employee = $evaluation->employee;

        $isHR = $this->isHRUser($user);
        $managerReviewerId = $this->getManagerReviewerId($employee, $evaluation->evaluation_type);
        $isManager = ($user->employee_id && $managerReviewerId && $user->employee_id == $managerReviewerId) || ($user->permission == 1);

        if (!$isHR && !$isManager) {
            abort(403);
        }

        $isSale = ($evaluation->evaluation_type ?: optional($employee)->evaluation_type) === 'sale';

        $actingAs = 'viewer';
        if ($evaluation->status == 'submitted') {
            if ($isSale || empty($managerReviewerId)) {
                if ($isHR) {
                    $actingAs = 'hr';
                }
            } else {
                if ($isManager) {
                    $actingAs = 'manager';
                } elseif ($isHR && $user->permission == 1) {
                    $actingAs = 'manager';
                }
            }
        } elseif ($evaluation->status == 'manager_reviewed') {
            if ($isHR) {
                $actingAs = 'hr';
            }
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

            $evaluation->final_score = $totalScore;
            $evaluation->hr_note = $request->input('note');
            $matchedGrade = EvaluationGrade::getGradeForScore($totalScore);
            $evaluation->grade_id = $matchedGrade ? $matchedGrade->id : null;
            
            if ($request->has('approve')) {
                $evaluation->status = 'hr_approved';
            }
        } else {
            // Manager (Người duyệt lần 2 của Back)
            $managerChoices = $request->input('manager_choice', []);
            $singleChoices = $request->input('single_choice', []);
            $selectedDetailIds = array_map('intval', array_values(array_filter(array_merge((array)$managerChoices, (array)$singleChoices))));

            foreach ($details as $detail) {
                $isSelected = in_array((int)$detail->id, $selectedDetailIds, true);
                $detail->is_manager_selected = $isSelected;
                $detail->manager_score = $isSelected ? (float)($detail->criterion->max_score ?? 0) : 0;
                
                if ($request->has('approve')) {
                    // Khi Quản lý duyệt, copy luôn lựa chọn này sang cột của HR để làm điểm khởi tạo
                    $detail->is_hr_selected = $isSelected;
                    $detail->hr_score = $detail->manager_score;
                }
                $detail->save();

                if ($isSelected) {
                    $totalScore += $detail->manager_score;
                }
            }

            $evaluation->manager_total_score = $totalScore;
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
