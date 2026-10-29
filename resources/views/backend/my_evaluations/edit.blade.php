@extends('backend.layouts.app')

@section('title', 'Chi tiết phiếu đánh giá')
@section('page_title', 'Phiếu đánh giá năng lực - Tháng ' . $evaluation->month . '/' . $evaluation->year)
@section('breadcrumb', 'Chi tiết đánh giá')

@push('styles')
<style>
    .child-row {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .child-row:hover {
        background-color: #f8faff !important;
    }
    .child-row.is-selected {
        background-color: #eff6ff !important;
        border-left: 3px solid #2563eb !important;
    }
    .child-row.is-selected td {
        color: #1e3a8a;
    }
    .child-row.is-selected .child-name-text {
        font-weight: 600;
        color: #1d4ed8;
    }
    .child-row.is-selected .score-badge {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
    }
    .child-row.is-selected .status-icon {
        color: #2563eb !important;
    }
    .parent-row {
        background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }
    .eval-card-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    }
    .score-radio {
        width: 1.2em;
        height: 1.2em;
        cursor: pointer;
    }
    .sticky-eval-sidebar {
        position: sticky;
        top: 75px;
        z-index: 100;
    }
    .sticky-mobile-footer {
        position: sticky;
        bottom: 0;
        z-index: 1020;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.08);
        border-top: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
    @php
        $matchedInitialGrade = \App\Models\EvaluationGrade::getGradeForScore($evaluation->self_total_score);
        $initialGradeName = $matchedInitialGrade ? $matchedInitialGrade->name : '-';
        $totalMaxPossible = 0;
        foreach ($criteriaTree as $p) {
            $totalMaxPossible += $p->children->isNotEmpty() ? (float)($p->children->max('max_score') ?? 0) : (float)($p->max_score ?? 0);
        }
    @endphp

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3 fs-13" role="alert">
            <i class="ri-information-line me-1"></i> {{ session('info') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3 fs-13" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="eval-form" action="{{ route('backend.my-evaluations.update', $evaluation->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <!-- CỘT TRÁI: BẢNG TIÊU CHÍ & Ý KIẾN GHI CHÚ -->
            <div class="col-xxl-9 col-xl-8 col-lg-8">
                <!-- Bảng tiêu chí đánh giá -->
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px; overflow: hidden;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <i class="ri-list-check-2 text-primary fs-16 me-2"></i>
                            <h6 class="card-title mb-0 fs-14 fw-bold text-dark">Bảng tiêu chí đánh giá năng lực</h6>
                        </div>
                        <span class="text-muted fs-11"><i class="ri-information-line me-1"></i>Mỗi nhóm chọn 1 mức phù hợp</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th class="ps-3 py-2 text-uppercase fs-11 text-muted fw-bold">Tiêu chí & Cấp độ đánh giá</th>
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 100px;">Điểm chuẩn</th>
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 130px;">Điểm tự chấm</th>
                                    @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 100px;">QL chấm</th>
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 100px;">HR chốt</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($criteriaTree as $parent)
                                    @if($parent->children->isNotEmpty())
                                        @php
                                            $selectedChild = $parent->children->first(function($c) use ($evaluation) {
                                                $d = $evaluation->details->where('criteria_id', $c->id)->first();
                                                return $d && $d->is_self_selected;
                                            });
                                            $parentSelfScore = $selectedChild ? (float)($selectedChild->max_score ?? 0) : '-';
                                            $parentMaxScore = (float)($parent->children->max('max_score') ?? 0);
                                        @endphp
                                        <!-- Parent Header Row -->
                                        <tr class="parent-row" data-parent-id="{{ $parent->id }}">
                                            <td class="ps-3 py-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-folder-shield-2-fill text-primary fs-15 me-2"></i>
                                                    <span class="fw-bold fs-13 text-dark">{{ $loop->iteration }}. {{ $parent->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center py-2"></td>
                                            <td class="text-center py-2">
                                                <span class="badge bg-soft-primary text-primary border border-primary px-2 py-0.5 fs-11 fw-bold parent-self-score-badge" id="parent-score-{{ $parent->id }}">
                                                    {{ $parentSelfScore !== '-' ? $parentSelfScore . ' đ' : 'Chưa chọn' }}
                                                </span>
                                            </td>
                                            @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                                @php
                                                    $mgrChild = $parent->children->first(function($c) use ($evaluation) {
                                                        $d = $evaluation->details->where('criteria_id', $c->id)->first();
                                                        return $d && $d->is_manager_selected;
                                                    });
                                                    $hrChild = $parent->children->first(function($c) use ($evaluation) {
                                                        $d = $evaluation->details->where('criteria_id', $c->id)->first();
                                                        return $d && $d->is_hr_selected;
                                                    });
                                                @endphp
                                                <td class="text-center py-2"><strong class="text-success fs-12">{{ $mgrChild ? (float)($mgrChild->max_score ?? 0) . ' đ' : '-' }}</strong></td>
                                                <td class="text-center py-2"><strong class="text-danger fs-12">{{ $hrChild ? (float)($hrChild->max_score ?? 0) . ' đ' : '-' }}</strong></td>
                                            @endif
                                        </tr>

                                        <!-- Children Rows -->
                                        @foreach($parent->children as $child)
                                            @php
                                                $detail = $evaluation->details->where('criteria_id', $child->id)->first();
                                                $isSelfSelected = $detail ? $detail->is_self_selected : false;
                                                $isMgrSelected = $detail ? $detail->is_manager_selected : false;
                                                $isHrSelected = $detail ? $detail->is_hr_selected : false;
                                            @endphp
                                            <tr class="child-row child-row-{{ $parent->id }} {{ $isSelfSelected ? 'is-selected' : '' }}" 
                                                data-parent-id="{{ $parent->id }}" 
                                                data-detail-id="{{ $detail ? $detail->id : '' }}">
                                                <td class="ps-4 py-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="status-icon me-2 text-muted">
                                                            @if($isSelfSelected)
                                                                <i class="ri-checkbox-circle-fill text-primary fs-15"></i>
                                                            @else
                                                                <i class="ri-checkbox-blank-circle-line fs-15"></i>
                                                            @endif
                                                        </span>
                                                        <span class="child-name-text fs-12" style="line-height: 1.45;">{{ $child->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-center py-2">
                                                    <span class="badge bg-light text-muted border px-2 py-0.5 fs-11 score-badge">
                                                        {{ $child->max_score !== null ? (float)$child->max_score : 0 }} đ
                                                    </span>
                                                </td>
                                                <td class="text-center py-2">
                                                    @if (in_array($evaluation->status, ['draft']))
                                                        <div class="form-check d-flex justify-content-center mb-0">
                                                            <input class="form-check-input score-radio" 
                                                                   type="radio" 
                                                                   name="parent_choice[{{ $parent->id }}]" 
                                                                   value="{{ $detail ? $detail->id : '' }}" 
                                                                   data-parent-id="{{ $parent->id }}"
                                                                   data-score="{{ (float)($child->max_score ?? 0) }}"
                                                                   {{ $isSelfSelected ? 'checked' : '' }}>
                                                        </div>
                                                    @else
                                                        @if($isSelfSelected)
                                                            <span class="badge bg-soft-success text-success px-2 py-0.5 fs-11"><i class="ri-check-line me-1"></i>Đã chọn</span>
                                                        @else
                                                            <span class="text-muted fs-11">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                                <td class="text-center py-2">{!! $isMgrSelected ? '<i class="ri-checkbox-circle-fill text-success fs-16"></i>' : '<span class="text-muted fs-11">-</span>' !!}</td>
                                                <td class="text-center py-2">{!! $isHrSelected ? '<i class="ri-checkbox-circle-fill text-danger fs-16"></i>' : '<span class="text-muted fs-11">-</span>' !!}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @else
                                        <!-- Top level leaves (Tiêu chí độc lập không con) -->
                                        @php
                                            $detail = $evaluation->details->where('criteria_id', $parent->id)->first();
                                            $isSelfSelected = $detail ? $detail->is_self_selected : false;
                                            $isMgrSelected = $detail ? $detail->is_manager_selected : false;
                                            $isHrSelected = $detail ? $detail->is_hr_selected : false;
                                        @endphp
                                        <tr class="parent-row" data-parent-id="{{ $parent->id }}">
                                            <td class="ps-3 py-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-bookmark-3-fill text-warning fs-15 me-2"></i>
                                                    <span class="fw-bold fs-13 text-dark">{{ $loop->iteration }}. {{ $parent->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center py-2">
                                                <span class="badge bg-soft-secondary text-secondary border px-2 py-0.5 fs-11">
                                                    {{ (float)$parent->max_score }} đ
                                                </span>
                                            </td>
                                            <td class="text-center py-2">
                                                @if (in_array($evaluation->status, ['draft']))
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-single" 
                                                               style="transform: scale(1.15); cursor: pointer;" 
                                                               type="checkbox" 
                                                               name="single_choice[{{ $parent->id }}]" 
                                                               value="{{ $detail ? $detail->id : '' }}" 
                                                               data-parent-id="{{ $parent->id }}"
                                                               data-score="{{ (float)($parent->max_score ?? 0) }}"
                                                               {{ $isSelfSelected ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    @if($isSelfSelected)
                                                        <span class="badge bg-soft-success text-success px-2 py-0.5 fs-11"><i class="ri-check-line me-1"></i>Đạt</span>
                                                    @else
                                                        <span class="text-muted fs-11">-</span>
                                                    @endif
                                                @endif
                                            </td>
                                            @if(in_array($evaluation->status, ['manager_reviewed', 'hr_approved']))
                                            <td class="text-center py-2">{!! $isMgrSelected ? '<i class="ri-checkbox-circle-fill text-success fs-16"></i>' : '<span class="text-muted fs-11">-</span>' !!}</td>
                                            <td class="text-center py-2">{!! $isHrSelected ? '<i class="ri-checkbox-circle-fill text-danger fs-16"></i>' : '<span class="text-muted fs-11">-</span>' !!}</td>
                                            @endif
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Nhận xét & Đánh giá -->
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px;">
                    <div class="card-body p-3">
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark fs-13 mb-1">
                                <i class="ri-chat-1-line text-primary me-1"></i> Ý kiến & Ghi chú tự đánh giá của nhân sự
                            </label>
                            <textarea name="self_note" class="form-control fs-12" rows="2" 
                                      style="border-radius: 6px;"
                                      placeholder="Nhập ghi chú tóm tắt kết quả nổi bật hoặc giải trình trong tháng..."
                                      {{ in_array($evaluation->status, ['draft']) ? '' : 'disabled' }}>{{ $evaluation->self_note }}</textarea>
                        </div>

                        @if ($evaluation->manager_note)
                        <div class="mt-2 p-2 rounded-2" style="background: #f0fdf4; border-left: 3px solid #10b981;">
                            <div class="d-flex align-items-center mb-0.5">
                                <i class="ri-shield-user-fill text-success fs-14 me-1"></i>
                                <span class="text-success fw-bold fs-12">Nhận xét của Quản lý trực tiếp</span>
                            </div>
                            <p class="mb-0 text-dark fs-12 ps-3">{{ $evaluation->manager_note }}</p>
                        </div>
                        @endif

                        @if ($evaluation->hr_note)
                        <div class="mt-2 p-2 rounded-2" style="background: #fef2f2; border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center mb-0.5">
                                <i class="ri-award-fill text-danger fs-14 me-1"></i>
                                <span class="text-danger fw-bold fs-12">Nhận xét của Nhân sự / BGĐ (Chốt)</span>
                            </div>
                            <p class="mb-0 text-dark fs-12 ps-3">{{ $evaluation->hr_note }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- CỘT PHẢI: THANH THÔNG TIN NHÂN SỰ, ĐIỂM SỐ, XẾP LOẠI & THAO TÁC (STICKY & NHỎ GỌN) -->
            <div class="col-xxl-3 col-xl-4 col-lg-4">
                <div class="sticky-eval-sidebar">
                    <div class="card border-0 shadow-sm mb-3 overflow-hidden" style="border-radius: 10px;">
                        <!-- Header thu gọn -->
                        <div class="card-body p-3 eval-card-header text-white">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm me-2 flex-shrink-0">
                                    <div class="rounded-circle bg-white text-primary fs-16 fw-bold shadow-sm" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.9);">
                                        {{ mb_substr($evaluation->employee->name ?? auth()->user()->name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="overflow-hidden">
                                    <h6 class="text-white mb-0 fw-bold text-truncate fs-13">{{ $evaluation->employee->name ?? auth()->user()->name }}</h6>
                                    <span class="badge bg-white text-primary fw-semibold px-1.5 py-0 fs-10">
                                        {{ $evaluation->employee->evaluation_type_name }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 text-white-50 fs-11 d-flex flex-column gap-1" style="border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 10px;">
                                <div><i class="ri-building-line me-1 text-white"></i>{{ $evaluation->employee->department->name ?? 'Phòng ban' }}</div>
                                <div><i class="ri-briefcase-line me-1 text-white"></i>{{ $evaluation->employee->position ?? 'Nhân sự' }}</div>
                                <div><i class="ri-calendar-event-line me-1 text-white"></i>Tháng <strong class="text-white">{{ $evaluation->month }}/{{ $evaluation->year }}</strong></div>
                            </div>
                        </div>

                        <!-- Thống kê điểm, xếp loại & Trạng thái -->
                        <div class="card-body p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="text-muted fs-11 fw-semibold text-uppercase">Trạng thái</span>
                                <div>
                                    @if ($evaluation->status == 'draft')
                                        <span class="badge bg-warning text-dark px-1.5 py-0.5 fs-11"><i class="ri-edit-line me-1"></i>Bản nháp</span>
                                    @elseif ($evaluation->status == 'submitted')
                                        <span class="badge bg-info text-white px-1.5 py-0.5 fs-11"><i class="ri-time-line me-1"></i>Chờ QL duyệt</span>
                                    @elseif ($evaluation->status == 'manager_reviewed')
                                        <span class="badge bg-primary px-1.5 py-0.5 fs-11"><i class="ri-check-double-line me-1"></i>QL đã duyệt</span>
                                    @elseif ($evaluation->status == 'hr_approved')
                                        <span class="badge bg-success px-1.5 py-0.5 fs-11"><i class="ri-checkbox-circle-line me-1"></i>HR chốt</span>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="h-100 p-2 rounded-2 text-center d-flex flex-column justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <span class="d-block text-muted fs-10 text-uppercase fw-semibold">Tổng điểm</span>
                                        <div class="my-auto py-1">
                                            <span class="fs-18 fw-bold text-primary d-block lh-1" id="sidebar-score-display">{{ (float)$evaluation->self_total_score }}</span>
                                        </div>
                                        <span class="text-muted fs-10 d-block">/ {{ $totalMaxPossible }}đ</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="h-100 p-2 rounded-2 text-center d-flex flex-column justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <span class="d-block text-muted fs-10 text-uppercase fw-semibold">Xếp loại</span>
                                        <div class="my-auto py-1">
                                            <span class="badge fs-12 px-2 py-0.5 grade-badge-pill" id="sidebar-grade-display" style="background-color: #10b981;">
                                                {{ $initialGradeName }}
                                            </span>
                                        </div>
                                        <span class="text-muted fs-10 d-block">Dự kiến</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tiến độ hoàn thành -->
                            <div class="p-2 rounded-2 mb-3" style="background: #f0f7ff; border: 1px solid #dbeafe;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fs-11 fw-semibold text-dark"><i class="ri-task-line text-primary me-1"></i>Tiến độ</span>
                                    <span class="fs-11 fw-bold text-primary" id="completion-ratio">0/0</span>
                                </div>
                                <div class="progress" style="height: 5px; border-radius: 3px; background: #cbd5e1;">
                                    <div class="progress-bar bg-primary" id="completion-progress-bar" style="width: 0%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>

                            <!-- Các nút hành động chính -->
                            @if (in_array($evaluation->status, ['draft']))
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-secondary btn-sm flex-fill fw-semibold py-1.5 fs-12 text-nowrap">
                                    <i class="ri-save-line me-1"></i> Lưu nháp
                                </button>
                                <button type="button" class="btn btn-primary btn-sm flex-fill fw-semibold py-1.5 btn-submit-eval shadow-sm fs-12 text-nowrap">
                                    <i class="ri-send-plane-fill me-1"></i> Gửi duyệt
                                </button>
                            </div>
                            @else
                            <div class="d-grid">
                                <a href="{{ route('backend.my-evaluations.index') }}" class="btn btn-light btn-sm py-1.5 fs-12">
                                    <i class="ri-arrow-left-line me-1"></i> Quay lại danh sách
                                </a>
                            </div>
                            @endif

                            <div class="text-center text-muted fs-10 mt-2">
                                <i class="ri-cursor-line me-1 text-primary"></i> Nhấp trực tiếp vào dòng để chọn điểm.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thanh Mobile Sticky Footer -->
        <div class="sticky-mobile-footer d-lg-none py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fs-11 text-uppercase fw-semibold">Điểm:</span>
                    <strong class="text-primary fs-15" id="mobile-score-display">{{ (float)$evaluation->self_total_score }}đ</strong>
                    <span class="badge fs-11 px-2 py-0 ms-1 grade-badge-pill" id="mobile-grade-display" style="background-color: #10b981;">{{ $initialGradeName }}</span>
                </div>
                @if (in_array($evaluation->status, ['draft']))
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">Lưu</button>
                    <button type="button" class="btn btn-sm btn-primary py-1 px-2 fs-12 btn-submit-eval">Gửi duyệt</button>
                </div>
                @endif
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('.score-radio');
        const singles = document.querySelectorAll('.score-single');
        const sidebarScoreDisplay = document.getElementById('sidebar-score-display');
        const mobileScoreDisplay = document.getElementById('mobile-score-display');
        const sidebarGradeDisplay = document.getElementById('sidebar-grade-display');
        const mobileGradeDisplay = document.getElementById('mobile-grade-display');
        const completionRatio = document.getElementById('completion-ratio');
        const completionBar = document.getElementById('completion-progress-bar');
        const isEditable = {{ in_array($evaluation->status, ['draft']) ? 'true' : 'false' }};

        const grades = @json($grades);

        function getGradeThemeColor(gradeName) {
            switch(gradeName.toUpperCase()) {
                case 'A': return '#10b981'; // Xanh lá
                case 'B': return '#3b82f6'; // Xanh dương
                case 'C': return '#f59e0b'; // Vàng cam
                case 'D': return '#f97316'; // Cam đậm
                case 'E': return '#ef4444'; // Đỏ
                default:  return '#64748b'; // Xám
            }
        }

        function updateScoreAndGrade() {
            let total = 0;
            let completedParents = 0;
            const parentRows = document.querySelectorAll('.parent-row');
            const totalParents = parentRows.length;

            parentRows.forEach(pRow => {
                const parentId = pRow.dataset.parentId;
                const scoreBadge = document.getElementById(`parent-score-${parentId}`);
                const checkedRadio = document.querySelector(`.score-radio[data-parent-id="${parentId}"]:checked`);
                
                // Cập nhật highlight cho các dòng con
                const childRows = document.querySelectorAll(`.child-row-${parentId}`);
                childRows.forEach(cr => {
                    const r = cr.querySelector('.score-radio');
                    const statusIcon = cr.querySelector('.status-icon');
                    if (r && r.checked) {
                        cr.classList.add('is-selected');
                        if (statusIcon) statusIcon.innerHTML = '<i class="ri-checkbox-circle-fill text-primary fs-15"></i>';
                    } else {
                        cr.classList.remove('is-selected');
                        if (statusIcon) statusIcon.innerHTML = '<i class="ri-checkbox-blank-circle-line fs-15"></i>';
                    }
                });

                if (checkedRadio) {
                    completedParents++;
                    const score = parseFloat(checkedRadio.dataset.score) || 0;
                    if (scoreBadge) {
                        scoreBadge.innerHTML = `<strong>${score} đ</strong>`;
                        scoreBadge.className = 'badge bg-primary text-white px-2 py-0.5 fs-11 fw-bold';
                    }
                    total += score;
                } else if (scoreBadge) {
                    scoreBadge.textContent = 'Chưa chọn';
                    scoreBadge.className = 'badge bg-soft-secondary text-muted border px-2 py-0.5 fs-11';
                }
            });

            // Tiêu chí độc lập không con
            singles.forEach(chk => {
                if (chk.checked) {
                    completedParents++;
                    total += parseFloat(chk.dataset.score) || 0;
                }
            });

            // Làm tròn 2 số thập phân chuẩn
            total = Math.round(total * 100) / 100;

            if (sidebarScoreDisplay) sidebarScoreDisplay.textContent = total;
            if (mobileScoreDisplay) mobileScoreDisplay.textContent = `${total} đ`;

            // Cập nhật thanh tiến độ
            if (completionRatio && completionBar && totalParents > 0) {
                completionRatio.textContent = `${completedParents}/${totalParents} (${Math.round((completedParents/totalParents)*100)}%)`;
                const pct = Math.round((completedParents / totalParents) * 100);
                completionBar.style.width = `${pct}%`;
                if (pct === 100) {
                    completionBar.className = 'progress-bar bg-success';
                } else {
                    completionBar.className = 'progress-bar bg-primary';
                }
            }

            let currentGradeName = '-';
            if (grades && grades.length > 0) {
                const sortedGrades = [...grades].map(g => ({
                    id: g.id,
                    name: g.name,
                    min: parseFloat(g.min_score) || 0,
                    max: parseFloat(g.max_score) || 0
                })).sort((a, b) => b.min - a.min);

                // Khớp chính xác [min, max]
                for (let g of sortedGrades) {
                    if (total >= (g.min - 0.001) && total <= (g.max + 0.001)) {
                        currentGradeName = g.name;
                        break;
                    }
                }

                // Nếu lọt khoảng hở
                if (currentGradeName === '-') {
                    for (let g of sortedGrades) {
                        if (total >= (g.min - 0.001)) {
                            currentGradeName = g.name;
                            break;
                        }
                    }
                }
            }

            const themeColor = getGradeThemeColor(currentGradeName);
            const displayText = currentGradeName !== '-' ? `Hạng ${currentGradeName}` : '-';

            if (sidebarGradeDisplay) {
                sidebarGradeDisplay.textContent = displayText;
                sidebarGradeDisplay.style.backgroundColor = themeColor;
            }
            if (mobileGradeDisplay) {
                mobileGradeDisplay.textContent = displayText;
                mobileGradeDisplay.style.backgroundColor = themeColor;
            }
        }

        // Cho phép click vào bất kỳ đâu trên dòng con để chọn
        if (isEditable) {
            document.querySelectorAll('.child-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target && e.target.classList.contains('score-radio')) {
                        return;
                    }
                    const radio = this.querySelector('.score-radio');
                    if (!radio) return;

                    if (radio.dataset.wasChecked === 'true') {
                        radio.checked = false;
                        radio.dataset.wasChecked = 'false';
                    } else {
                        document.querySelectorAll(`.score-radio[name="${radio.name}"]`).forEach(r => {
                            r.dataset.wasChecked = 'false';
                        });
                        radio.checked = true;
                        radio.dataset.wasChecked = 'true';
                    }
                    updateScoreAndGrade();
                });
            });

            radios.forEach(radio => {
                radio.dataset.wasChecked = radio.checked ? 'true' : 'false';

                radio.addEventListener('click', function(e) {
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

            singles.forEach(chk => {
                chk.addEventListener('change', updateScoreAndGrade);
            });
        }

        // Chạy khởi tạo ngay lúc vừa tải trang
        updateScoreAndGrade();

        // Xử lý nút Gửi duyệt bằng SweetAlert2
        document.querySelectorAll('.btn-submit-eval').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Xác nhận gửi duyệt?',
                    text: 'Sau khi gửi, phiếu đánh giá sẽ được chuyển cho Quản lý xét duyệt và bạn không thể chỉnh sửa điểm nữa.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Đồng ý, gửi duyệt!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('eval-form');
                        let input = form.querySelector('input[name="submit_evaluation"]');
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'submit_evaluation';
                            form.appendChild(input);
                        }
                        input.value = '1';
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            });
        });
    });
</script>
@endpush
