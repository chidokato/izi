@extends('backend.layouts.app')

@section('title', 'Chi tiết phiếu đánh giá')
@section('page_title', 'Phiếu đánh giá tháng ' . $evaluation->month . '/' . $evaluation->year)
@section('breadcrumb', 'Chi tiết đánh giá')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Tự đánh giá năng lực</h4>
            <div>
                @if ($evaluation->status == 'draft')
                    <span class="badge bg-secondary fs-13">Bản nháp</span>
                @elseif ($evaluation->status == 'submitted')
                    <span class="badge bg-warning fs-13">Đã gửi - Chờ duyệt</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if(session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form id="eval-form" action="{{ route('backend.my-evaluations.update', $evaluation->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="table-responsive mb-4">
                    <!-- ... table content ... -->
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tiêu chí đánh giá</th>
                                <th class="text-center" style="width: 150px;">Điểm tối đa</th>
                                <th class="text-center" style="width: 200px;">Điểm tự chấm</th>
                                @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                <th class="text-center" style="width: 150px;">Điểm quản lý</th>
                                <th class="text-center" style="width: 150px;">Điểm HR chốt</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($criteriaTree as $parent)
                                @if($parent->children->isNotEmpty())
                                    <!-- Parent -->
                                    <tr class="table-info">
                                        <td><strong>{{ $parent->order }}. {{ $parent->name }}</strong></td>
                                        <td class="text-center"><strong>{{ $parent->children->sum('max_score') ?: '-' }}</strong></td>
                                        <td class="text-center"></td>
                                        @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                        <td></td><td></td>
                                        @endif
                                    </tr>
                                    <!-- Children -->
                                    @foreach($parent->children as $child)
                                        @php
                                            $detail = $evaluation->details->where('criteria_id', $child->id)->first();
                                            $selfScore = $detail ? $detail->self_score : 0;
                                            $managerScore = $detail ? $detail->manager_score : 0;
                                            $hrScore = $detail ? $detail->hr_score : 0;
                                        @endphp
                                        <tr>
                                            <td class="ps-4"> - {{ $child->name }}</td>
                                            <td class="text-center">{{ $child->max_score }}</td>
                                            <td class="text-center">
                                                @if (in_array($evaluation->status, ['draft']))
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-checkbox" style="transform: scale(1.5);" type="checkbox" name="scores[{{ $detail ? $detail->id : '' }}]" value="{{ $child->max_score }}" {{ $selfScore > 0 ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    @if($selfScore > 0)
                                                        <i class="ri-checkbox-circle-fill text-success fs-20"></i>
                                                    @else
                                                        <i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>
                                                    @endif
                                                @endif
                                            </td>
                                            @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                            <td class="text-center">{!! $managerScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '-' !!}</td>
                                            <td class="text-center">{!! $hrScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '-' !!}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    <!-- Top level leaves -->
                                    @php
                                        $detail = $evaluation->details->where('criteria_id', $parent->id)->first();
                                        $selfScore = $detail ? $detail->self_score : 0;
                                        $managerScore = $detail ? $detail->manager_score : 0;
                                        $hrScore = $detail ? $detail->hr_score : 0;
                                    @endphp
                                    <tr class="table-warning">
                                        <td><strong>{{ $parent->order }}. {{ $parent->name }}</strong></td>
                                        <td class="text-center"><strong>{{ $parent->max_score }}</strong></td>
                                        <td class="text-center">
                                            @if (in_array($evaluation->status, ['draft']))
                                                <div class="form-check d-flex justify-content-center mb-0">
                                                    <input class="form-check-input score-checkbox" style="transform: scale(1.5);" type="checkbox" name="scores[{{ $detail ? $detail->id : '' }}]" value="{{ $parent->max_score }}" {{ $selfScore > 0 ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                @if($selfScore > 0)
                                                    <i class="ri-checkbox-circle-fill text-success fs-20"></i>
                                                @else
                                                    <i class="ri-checkbox-blank-circle-line text-muted fs-20"></i>
                                                @endif
                                            @endif
                                        </td>
                                        @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                        <td class="text-center">{!! $managerScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '-' !!}</td>
                                        <td class="text-center">{!! $hrScore > 0 ? '<i class="ri-checkbox-circle-fill text-success fs-20"></i>' : '-' !!}</td>
                                        @endif
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Hiển thị tổng điểm và xếp loại -->
                <div class="d-flex align-items-center justify-content-end mb-4 bg-light p-3 rounded border">
                    <h5 class="mb-0 me-3">Tổng điểm: <span id="total-score-display" class="text-primary fs-4">{{ $evaluation->self_total_score }}</span></h5>
                    <h5 class="mb-0">Xếp loại: <span id="grade-display" class="badge bg-success fs-5">-</span></h5>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Nhận xét của bạn (Ghi chú tự đánh giá)</label>
                    <textarea name="self_note" class="form-control" rows="3" {{ in_array($evaluation->status, ['draft']) ? '' : 'disabled' }}>{{ $evaluation->self_note }}</textarea>
                </div>

                @if ($evaluation->manager_note)
                <div class="mb-4">
                    <label class="form-label fw-bold text-primary">Nhận xét của Quản lý</label>
                    <div class="p-3 bg-light border rounded">{{ $evaluation->manager_note }}</div>
                </div>
                @endif

                @if ($evaluation->hr_note)
                <div class="mb-4">
                    <label class="form-label fw-bold text-danger">Nhận xét của HR (Chốt)</label>
                    <div class="p-3 bg-light border rounded">{{ $evaluation->hr_note }}</div>
                </div>
                @endif

                @if (in_array($evaluation->status, ['draft']))
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary">Lưu nháp</button>
                    <button type="button" class="btn btn-primary btn-submit-eval">Gửi cho Quản lý duyệt</button>
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
            
            // Tìm xếp loại phù hợp
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

        // Chạy lần đầu lúc vừa load trang
        updateScoreAndGrade();

        // Xử lý nút Gửi duyệt bằng SweetAlert2
        const btnSubmitEval = document.querySelector('.btn-submit-eval');
        if (btnSubmitEval) {
            btnSubmitEval.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Bạn chắc chắn chứ?',
                    text: "Sau khi Gửi Duyệt bạn sẽ không thể tự sửa điểm được nữa.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý, gửi duyệt!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('eval-form');
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'submit_evaluation';
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
