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
                            @foreach ($criteriaTree as $parent)
                                @if($parent->children->isNotEmpty())
                                    <tr class="table-info">
                                        <td><strong>{{ $parent->order }}. {{ $parent->name }}</strong></td>
                                        <td class="text-center"><strong>{{ $parent->children->sum('max_score') ?: '-' }}</strong></td>
                                        <td></td><td></td>@if($isHR || $evaluation->status == 'hr_approved')<td></td>@endif
                                    </tr>
                                    @foreach($parent->children as $child)
                                        @php
                                            $detail = $evaluation->details->where('criteria_id', $child->id)->first();
                                            $selfScore = $detail ? $detail->self_score : 0;
                                            $managerScore = $detail ? $detail->manager_score : 0;
                                            $hrScore = $detail ? $detail->hr_score : 0;
                                            if ($actingAs == 'manager' && $managerScore == 0) $managerScore = $selfScore;
                                            if ($actingAs == 'hr' && in_array($evaluation->status, ['manager_reviewed', 'hr_approved']) && $hrScore == 0) $hrScore = $managerScore;
                                        @endphp
                                        <tr>
                                            <td class="ps-4"> - {{ $child->name }}</td>
                                            <td class="text-center">{{ $child->max_score }}</td>
                                            <td class="text-center">{!! $selfScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}</td>
                                            
                                            <td class="text-center">
                                                @if ($actingAs == 'manager')
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-checkbox" style="transform: scale(1.5);" type="checkbox" name="scores[{{ $detail ? $detail->id : '' }}]" value="{{ $child->max_score }}" {{ $managerScore > 0 ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    {!! $managerScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                                @endif
                                            </td>

                                            @if($isHR || $evaluation->status == 'hr_approved')
                                            <td class="text-center">
                                                @if ($actingAs == 'hr' && in_array($evaluation->status, ['submitted', 'manager_reviewed', 'hr_approved']))
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-checkbox" style="transform: scale(1.5);" type="checkbox" name="scores[{{ $detail ? $detail->id : '' }}]" value="{{ $child->max_score }}" {{ $hrScore > 0 ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    {!! $hrScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    @php
                                        $detail = $evaluation->details->where('criteria_id', $parent->id)->first();
                                        $selfScore = $detail ? $detail->self_score : 0;
                                        $managerScore = $detail ? $detail->manager_score : 0;
                                        $hrScore = $detail ? $detail->hr_score : 0;
                                        if ($actingAs == 'manager' && $managerScore == 0) $managerScore = $selfScore;
                                        if ($actingAs == 'hr' && in_array($evaluation->status, ['manager_reviewed', 'hr_approved']) && $hrScore == 0) $hrScore = $managerScore;
                                    @endphp
                                    <tr class="table-warning">
                                        <td><strong>{{ $parent->order }}. {{ $parent->name }}</strong></td>
                                        <td class="text-center"><strong>{{ $parent->max_score }}</strong></td>
                                        <td class="text-center">{!! $selfScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}</td>
                                        
                                        <td class="text-center">
                                            @if ($actingAs == 'manager')
                                                <div class="form-check d-flex justify-content-center mb-0">
                                                    <input class="form-check-input score-checkbox" style="transform: scale(1.5);" type="checkbox" name="scores[{{ $detail ? $detail->id : '' }}]" value="{{ $parent->max_score }}" {{ $managerScore > 0 ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                {!! $managerScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                            @endif
                                        </td>

                                        @if($isHR || $evaluation->status == 'hr_approved')
                                        <td class="text-center">
                                            @if ($actingAs == 'hr' && in_array($evaluation->status, ['submitted', 'manager_reviewed', 'hr_approved']))
                                                <div class="form-check d-flex justify-content-center mb-0">
                                                    <input class="form-check-input score-checkbox" style="transform: scale(1.5);" type="checkbox" name="scores[{{ $detail ? $detail->id : '' }}]" value="{{ $parent->max_score }}" {{ $hrScore > 0 ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                {!! $hrScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>' !!}
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex align-items-center justify-content-end mb-4 bg-light p-3 rounded border">
                    <h5 class="mb-0 me-3">Tổng điểm duyệt: <span id="total-score-display" class="text-primary fs-4">{{ $actingAs == 'hr' ? $evaluation->final_score : $evaluation->details->sum('manager_score') }}</span></h5>
                    <h5 class="mb-0">Xếp loại: <span id="grade-display" class="badge bg-success fs-5">-</span></h5>
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
        const checkboxes = document.querySelectorAll('.score-checkbox');
        const scoreDisplay = document.getElementById('total-score-display');
        const gradeDisplay = document.getElementById('grade-display');

        const grades = @json($grades);

        function updateScoreAndGrade() {
            let total = parseInt(scoreDisplay.textContent) || 0;

            if (checkboxes.length > 0) {
                total = 0;
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        total += parseInt(cb.value) || 0;
                    }
                });
                scoreDisplay.textContent = total;
            }

            let currentGradeName = '-';
            let currentGradeColor = 'bg-secondary';
            
            for (let i = 0; i < grades.length; i++) {
                if (total >= grades[i].min_score && total <= grades[i].max_score) {
                    currentGradeName = grades[i].name;
                    currentGradeColor = 'bg-success';
                    break;
                }
            }

            gradeDisplay.textContent = currentGradeName;
            gradeDisplay.className = 'badge fs-5 ' + currentGradeColor;
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateScoreAndGrade);
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
