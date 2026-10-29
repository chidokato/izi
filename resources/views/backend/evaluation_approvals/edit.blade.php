@extends('backend.layouts.app')

@section('title', 'Duyệt đánh giá nhân viên')
@section('page_title', 'Duyệt phiếu: ' . ($evaluation->employee->name ?? 'N/A') . ' - Tháng ' . $evaluation->month . '/' . $evaluation->year)
@section('breadcrumb', 'Duyệt đánh giá')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Chi tiết đánh giá năng lực</h4>
            <div>
                @if ($evaluation->status == 'submitted')
                    <span class="badge bg-warning fs-13">Đã gửi - Chờ Quản lý duyệt</span>
                @elseif ($evaluation->status == 'manager_reviewed')
                    <span class="badge bg-info fs-13">Đã duyệt - Chờ HR chốt</span>
                @elseif ($evaluation->status == 'hr_approved')
                    <span class="badge bg-success fs-13">Đã hoàn thành</span>
                @endif
            </div>
        </div>
@php
    $actingAs = 'viewer';
    if ($isHR) {
        $actingAs = 'hr';
    }
    if ($isManager && $evaluation->status == 'submitted') {
        $actingAs = 'manager';
    }
@endphp
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form id="approval-form" action="{{ route('backend.evaluation-approvals.update', $evaluation->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="table-responsive mb-4">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tiêu chí đánh giá</th>
                                <th class="text-center" style="width: 150px;">Điểm tối đa</th>
                                <th class="text-center" style="width: 150px;">NV tự chấm</th>
                                <th class="text-center" style="width: 150px;">Quản lý chấm</th>
                                @if($isHR || $evaluation->status == 'hr_approved')
                                <th class="text-center" style="width: 150px;">HR chốt</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $hasAnyManagerSelected = $evaluation->details->where('is_manager_selected', true)->count() > 0;
                                $hasAnyHrSelected = $evaluation->details->where('is_hr_selected', true)->count() > 0;
                            @endphp
                            @foreach ($criteriaTree as $parent)
                                @if($parent->children->isNotEmpty())
                                    @php
                                        $parentMaxScore = (float)($parent->children->max('max_score') ?? 0);
                                        
                                        // NV tự chấm
                                        $selfChild = $parent->children->first(function($c) use ($evaluation) {
                                            $d = $evaluation->details->where('criteria_id', $c->id)->first();
                                            return $d && $d->is_self_selected;
                                        });
                                        $selfScoreDisplay = $selfChild ? (float)($selfChild->max_score ?? 0) : '-';

                                        // Quản lý chấm
                                        $mgrChild = $parent->children->first(function($c) use ($evaluation, $hasAnyManagerSelected) {
                                            $d = $evaluation->details->where('criteria_id', $c->id)->first();
                                            if (!$d) return false;
                                            return $hasAnyManagerSelected ? $d->is_manager_selected : $d->is_self_selected;
                                        });
                                        $mgrScoreDisplay = $mgrChild ? (float)($mgrChild->max_score ?? 0) : '-';

                                        // HR chốt
                                        $hrChild = $parent->children->first(function($c) use ($evaluation, $hasAnyHrSelected, $hasAnyManagerSelected) {
                                            $d = $evaluation->details->where('criteria_id', $c->id)->first();
                                            if (!$d) return false;
                                            if ($hasAnyHrSelected) return $d->is_hr_selected;
                                            return $hasAnyManagerSelected ? $d->is_manager_selected : $d->is_self_selected;
                                        });
                                        $hrScoreDisplay = $hrChild ? (float)($hrChild->max_score ?? 0) : '-';
                                    @endphp
                                    <tr class="table-info parent-row" data-parent-id="{{ $parent->id }}">
                                        <td><strong>{{ $parent->order }}. {{ $parent->name }}</strong></td>
                                        <td class="text-center"><strong>{{ $parentMaxScore }}</strong></td>
                                        <td class="text-center"><strong class="text-primary">{{ $selfScoreDisplay }}</strong></td>
                                        <td class="text-center"><strong class="parent-mgr-score text-success" id="parent-mgr-score-{{ $parent->id }}">{{ $mgrScoreDisplay }}</strong></td>
                                        @if($isHR || $evaluation->status == 'hr_approved')
                                        <td class="text-center"><strong class="parent-hr-score text-danger" id="parent-hr-score-{{ $parent->id }}">{{ $hrScoreDisplay }}</strong></td>
                                        @endif
                                    </tr>
                                    @foreach($parent->children as $child)
                                        @php
                                            $detail = $evaluation->details->where('criteria_id', $child->id)->first();
                                            $isSelfSelected = $detail ? $detail->is_self_selected : false;
                                            
                                            $isMgrSelected = false;
                                            if ($detail) {
                                                $isMgrSelected = $hasAnyManagerSelected ? $detail->is_manager_selected : $isSelfSelected;
                                            }

                                            $isHrSelected = false;
                                            if ($detail) {
                                                if ($hasAnyHrSelected) {
                                                    $isHrSelected = $detail->is_hr_selected;
                                                } else {
                                                    $isHrSelected = $hasAnyManagerSelected ? $detail->is_manager_selected : $isSelfSelected;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="ps-4"> - {{ $child->name }}</td>
                                            <td class="text-center">{{ $child->max_score !== null ? (float)$child->max_score : 0 }}</td>
                                            <td class="text-center">
                                                {!! $isSelfSelected ? '<i class="ri-checkbox-circle-fill text-primary fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                            </td>
                                            
                                            <td class="text-center">
                                                @if ($actingAs == 'manager')
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-radio mgr-radio" 
                                                               style="transform: scale(1.4); cursor: pointer;" 
                                                               type="radio" 
                                                               name="manager_choice[{{ $parent->id }}]" 
                                                               value="{{ $detail ? $detail->id : '' }}" 
                                                               data-parent-id="{{ $parent->id }}"
                                                               data-score="{{ (float)($child->max_score ?? 0) }}"
                                                               {{ $isMgrSelected ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    {!! $isMgrSelected ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                                @endif
                                            </td>

                                            @if($isHR || $evaluation->status == 'hr_approved')
                                            <td class="text-center">
                                                @if ($actingAs == 'hr' && in_array($evaluation->status, ['submitted', 'manager_reviewed', 'hr_approved']))
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-radio hr-radio" 
                                                               style="transform: scale(1.4); cursor: pointer;" 
                                                               type="radio" 
                                                               name="hr_choice[{{ $parent->id }}]" 
                                                               value="{{ $detail ? $detail->id : '' }}" 
                                                               data-parent-id="{{ $parent->id }}"
                                                               data-score="{{ (float)($child->max_score ?? 0) }}"
                                                               {{ $isHrSelected ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    {!! $isHrSelected ? '<i class="ri-checkbox-circle-fill text-danger fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    @php
                                        $detail = $evaluation->details->where('criteria_id', $parent->id)->first();
                                        $isSelfSelected = $detail ? $detail->is_self_selected : false;
                                        $isMgrSelected = $detail ? ($hasAnyManagerSelected ? $detail->is_manager_selected : $isSelfSelected) : false;
                                        $isHrSelected = $detail ? ($hasAnyHrSelected ? $detail->is_hr_selected : $isMgrSelected) : false;
                                    @endphp
                                    <tr class="table-warning parent-row" data-parent-id="{{ $parent->id }}">
                                        <td><strong>{{ $parent->order }}. {{ $parent->name }}</strong></td>
                                        <td class="text-center"><strong>{{ (float)$parent->max_score }}</strong></td>
                                        <td class="text-center">{!! $isSelfSelected ? '<i class="ri-checkbox-circle-fill text-primary fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}</td>
                                        
                                        <td class="text-center">
                                            @if ($actingAs == 'manager')
                                                <div class="form-check d-flex justify-content-center mb-0">
                                                    <input class="form-check-input score-single mgr-radio" 
                                                           style="transform: scale(1.4); cursor: pointer;" 
                                                           type="checkbox" 
                                                           name="single_choice[{{ $parent->id }}]" 
                                                           value="{{ $detail ? $detail->id : '' }}" 
                                                           data-parent-id="{{ $parent->id }}"
                                                           data-score="{{ (float)($parent->max_score ?? 0) }}"
                                                           {{ $isMgrSelected ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                {!! $isMgrSelected ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                            @endif
                                        </td>

                                        @if($isHR || $evaluation->status == 'hr_approved')
                                        <td class="text-center">
                                            @if ($actingAs == 'hr' && in_array($evaluation->status, ['submitted', 'manager_reviewed', 'hr_approved']))
                                                <div class="form-check d-flex justify-content-center mb-0">
                                                    <input class="form-check-input score-single hr-radio" 
                                                           style="transform: scale(1.4); cursor: pointer;" 
                                                           type="checkbox" 
                                                           name="single_choice[{{ $parent->id }}]" 
                                                           value="{{ $detail ? $detail->id : '' }}" 
                                                           data-parent-id="{{ $parent->id }}"
                                                           data-score="{{ (float)($parent->max_score ?? 0) }}"
                                                           {{ $isHrSelected ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                {!! $isHrSelected ? '<i class="ri-checkbox-circle-fill text-danger fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $displayScore = $actingAs == 'hr' ? $evaluation->final_score : $evaluation->details->sum('manager_score');
                    $matchedInitialGrade = \App\Models\EvaluationGrade::getGradeForScore($displayScore);
                    $initialGradeName = $matchedInitialGrade ? $matchedInitialGrade->name : '-';
                @endphp
                <div class="d-flex align-items-center justify-content-end mb-4 bg-light p-3 rounded border">
                    <h5 class="mb-0 me-3">Tổng điểm duyệt: <span id="total-score-display" class="text-primary fs-4">{{ (float)$displayScore }}</span></h5>
                    <h5 class="mb-0">Xếp loại: <span id="grade-display" class="badge bg-success fs-5">{{ $initialGradeName }}</span></h5>
                </div>

                @if ($evaluation->self_note)
                <div class="mb-4">
                    <label class="form-label fw-bold text-primary">Nhận xét của Nhân viên (Tự đánh giá)</label>
                    <div class="p-3 bg-light border rounded">{{ $evaluation->self_note }}</div>
                </div>
                @endif

                @if ($isManager)
                    <div class="mb-4">
                        <label class="form-label fw-bold">Nhận xét của Quản lý</label>
                        <textarea name="note" class="form-control" rows="3" {{ $actingAs == 'manager' ? '' : 'disabled' }}>{{ $evaluation->manager_note }}</textarea>
                    </div>
                @elseif ($evaluation->manager_note)
                    <div class="mb-4">
                        <label class="form-label fw-bold text-info">Nhận xét của Quản lý</label>
                        <div class="p-3 bg-light border rounded">{{ $evaluation->manager_note }}</div>
                    </div>
                @endif

                @if ($isHR)
                    <div class="mb-4">
                        <label class="form-label fw-bold">Nhận xét của HR</label>
                        <textarea name="note" class="form-control" rows="3" {{ $actingAs == 'hr' ? '' : 'disabled' }}>{{ $evaluation->hr_note }}</textarea>
                    </div>
                @elseif ($evaluation->hr_note)
                    <div class="mb-4">
                        <label class="form-label fw-bold text-danger">Nhận xét của HR</label>
                        <div class="p-3 bg-light border rounded">{{ $evaluation->hr_note }}</div>
                    </div>
                @endif

                @if ($actingAs == 'manager')
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary">Lưu nháp điểm</button>
                    <button type="button" class="btn btn-primary btn-approve">Duyệt & Gửi lên HR</button>
                </div>
                @endif

                @if ($actingAs == 'hr')
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary">Lưu nháp điểm</button>
                    <button type="button" class="btn btn-success btn-approve">{{ $evaluation->status == 'hr_approved' ? 'Cập nhật lại điểm' : 'Chốt điểm & Xếp loại' }}</button>
                </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actingAs = '{{ $actingAs }}';
        const activeRadios = document.querySelectorAll(actingAs === 'hr' ? '.hr-radio' : '.mgr-radio');
        const scoreDisplay = document.getElementById('total-score-display');
        const gradeDisplay = document.getElementById('grade-display');

        const grades = @json($grades);

        function updateScoreAndGrade() {
            let total = 0;

            const parentRows = document.querySelectorAll('.parent-row');
            parentRows.forEach(pRow => {
                const parentId = pRow.dataset.parentId;
                const scoreSpan = document.getElementById(actingAs === 'hr' ? `parent-hr-score-${parentId}` : `parent-mgr-score-${parentId}`);
                const checkedRadio = document.querySelector(`.score-radio[data-parent-id="${parentId}"]:checked`);
                
                if (checkedRadio) {
                    const score = parseFloat(checkedRadio.dataset.score) || 0;
                    if (scoreSpan) {
                        scoreSpan.textContent = score;
                    }
                    total += score;
                } else if (scoreSpan && (actingAs === 'manager' || actingAs === 'hr')) {
                    scoreSpan.textContent = '-';
                }
            });

            const singles = document.querySelectorAll('.score-single:checked');
            singles.forEach(chk => {
                total += parseFloat(chk.dataset.score) || 0;
            });

            // Làm tròn điểm về 2 chữ số thập phân để tránh lỗi dấu phẩy động (float precision)
            total = Math.round(total * 100) / 100;

            if (scoreDisplay) {
                scoreDisplay.textContent = total;
            }

            let currentGradeName = '-';
            let currentGradeColor = 'bg-secondary';
            
            if (grades && grades.length > 0) {
                // Sắp xếp các mức xếp loại từ cao xuống thấp theo min_score
                const sortedGrades = [...grades].map(g => ({
                    id: g.id,
                    name: g.name,
                    min: parseFloat(g.min_score) || 0,
                    max: parseFloat(g.max_score) || 0
                })).sort((a, b) => b.min - a.min);

                // 1. Khớp chính xác trong khoảng [min, max] (cho phép dung sai 0.001)
                for (let g of sortedGrades) {
                    if (total >= (g.min - 0.001) && total <= (g.max + 0.001)) {
                        currentGradeName = g.name;
                        currentGradeColor = 'bg-success';
                        break;
                    }
                }

                // 2. Nếu nằm trong khoảng hở giữa 2 bậc (ví dụ giữa 4.45 và 4.50), lấy bậc tương ứng
                if (currentGradeName === '-') {
                    for (let g of sortedGrades) {
                        if (total >= (g.min - 0.001)) {
                            currentGradeName = g.name;
                            currentGradeColor = 'bg-success';
                            break;
                        }
                    }
                }
            }

            if (gradeDisplay) {
                gradeDisplay.textContent = currentGradeName;
                gradeDisplay.className = 'badge fs-5 ' + currentGradeColor;
            }
        }

        activeRadios.forEach(radio => {
            radio.dataset.wasChecked = radio.checked ? 'true' : 'false';

            radio.addEventListener('click', function() {
                if (this.dataset.wasChecked === 'true') {
                    this.checked = false;
                    this.dataset.wasChecked = 'false';
                } else {
                    document.querySelectorAll(`.score-radio[name="${this.name}"]`).forEach(r => {
                        r.dataset.wasChecked = 'false';
                    });
                    this.dataset.wasChecked = 'true';
                }
                updateScoreAndGrade();
            });
        });

        document.querySelectorAll('.score-single').forEach(chk => {
            chk.addEventListener('change', updateScoreAndGrade);
        });

        updateScoreAndGrade();

        // Xử lý nút Duyệt bằng SweetAlert2
        const btnApprove = document.querySelector('.btn-approve');
        if (btnApprove) {
            btnApprove.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Xác nhận duyệt?',
                    text: "Sau khi duyệt, điểm sẽ được chuyển sang bước tiếp theo và bạn không thể sửa lại.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý, duyệt ngay!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('approval-form');
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'approve';
                        input.value = '1';
                        form.appendChild(input);
                        form.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
