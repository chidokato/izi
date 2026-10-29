@extends('backend.layouts.app')

@section('title', 'Duyệt đánh giá nhân viên')
@section('page_title', 'Duyệt phiếu: ' . ($evaluation->employee->name ?? 'N/A') . ' - Tháng ' . $evaluation->month . '/' . $evaluation->year)
@section('breadcrumb', 'Duyệt đánh giá')

@push('styles')
<style>
    .child-row {
        transition: all 0.15s ease-in-out;
    }
    .child-row.is-clickable {
        cursor: pointer;
    }
    .child-row.is-clickable:hover {
        background-color: #f8faff !important;
    }
    .child-row.is-selected {
        background-color: #f0fdf4 !important;
        border-left: 3px solid #10b981 !important;
    }
    .child-row.is-selected.is-hr-selected {
        background-color: #eff6ff !important;
        border-left: 3px solid #2563eb !important;
    }
    .child-row.is-selected td {
        color: #064e3b;
    }
    .child-row.is-selected.is-hr-selected td {
        color: #1e3a8a;
    }
    .child-row.is-selected .child-name-text {
        font-weight: 600;
        color: #047857;
    }
    .child-row.is-selected.is-hr-selected .child-name-text {
        color: #1d4ed8;
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
    $actingAs = 'viewer';
    if ($isHR) {
        $actingAs = 'hr';
    }
    if ($isManager && $evaluation->status == 'submitted') {
        $actingAs = 'manager';
    }
    $isEditable = in_array($actingAs, ['manager', 'hr']);

    $hasAnyManagerSelected = $evaluation->details->where('is_manager_selected', true)->count() > 0;
    $hasAnyHrSelected = $evaluation->details->where('is_hr_selected', true)->count() > 0;

    $displayScore = $actingAs == 'hr' ? (float)$evaluation->final_score : (float)$evaluation->details->sum('manager_score');
    if ($displayScore == 0 && $actingAs == 'manager' && !$hasAnyManagerSelected) {
        $displayScore = (float)$evaluation->self_total_score;
    }
    $matchedInitialGrade = \App\Models\EvaluationGrade::getGradeForScore($displayScore);
    $initialGradeName = $matchedInitialGrade ? $matchedInitialGrade->name : '-';

    $totalMaxPossible = 0;
    foreach ($criteriaTree as $p) {
        $totalMaxPossible += $p->children->isNotEmpty() ? (float)($p->children->max('max_score') ?? 0) : (float)($p->max_score ?? 0);
    }
@endphp

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3 fs-13" role="alert">
            <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2 px-3 mb-3 fs-13" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="approval-form" action="{{ route('backend.evaluation-approvals.update', $evaluation->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <!-- CỘT TRÁI: BẢNG TIÊU CHÍ & Ý KIẾN NHẬN XÉT -->
            <div class="col-xxl-9 col-xl-8 col-lg-8">
                <!-- Bảng tiêu chí đánh giá -->
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px; overflow: hidden;">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <i class="ri-list-check-2 text-primary fs-16 me-2"></i>
                            <h6 class="card-title mb-0 fs-14 fw-bold text-dark">Bảng tiêu chí và phân bổ điểm số</h6>
                        </div>
                        <div class="text-muted fs-11">
                            @if ($actingAs == 'manager')
                                <span class="badge bg-soft-success text-success border border-success px-1.5 py-0.5 fs-10"><i class="ri-shield-user-fill me-1"></i>Quản lý chấm</span>
                            @elseif ($actingAs == 'hr')
                                <span class="badge bg-soft-primary text-primary border border-primary px-1.5 py-0.5 fs-10"><i class="ri-award-fill me-1"></i>HR chốt điểm</span>
                            @else
                                <span class="badge bg-soft-secondary text-secondary px-1.5 py-0.5 fs-10"><i class="ri-eye-line me-1"></i>Xem kết quả</span>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th class="ps-3 py-2 text-uppercase fs-11 text-muted fw-bold">Tiêu chí & Cấp độ đánh giá</th>
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 95px;">Điểm chuẩn</th>
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 115px;">NV tự chấm</th>
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 120px;">QL chấm</th>
                                    @if($isHR || $evaluation->status == 'hr_approved')
                                    <th class="text-center py-2 text-uppercase fs-11 text-muted fw-bold" style="width: 120px;">HR chốt</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
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
                                                <span class="badge bg-soft-primary text-primary border border-primary px-2 py-0.5 fs-11 fw-bold">
                                                    {{ $selfScoreDisplay !== '-' ? $selfScoreDisplay . ' đ' : '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center py-2">
                                                <span class="badge bg-soft-success text-success border border-success px-2 py-0.5 fs-11 fw-bold parent-mgr-score" id="parent-mgr-score-{{ $parent->id }}">
                                                    {{ $mgrScoreDisplay !== '-' ? $mgrScoreDisplay . ' đ' : '-' }}
                                                </span>
                                            </td>
                                            @if($isHR || $evaluation->status == 'hr_approved')
                                            <td class="text-center py-2">
                                                <span class="badge bg-soft-danger text-danger border border-danger px-2 py-0.5 fs-11 fw-bold parent-hr-score" id="parent-hr-score-{{ $parent->id }}">
                                                    {{ $hrScoreDisplay !== '-' ? $hrScoreDisplay . ' đ' : '-' }}
                                                </span>
                                            </td>
                                            @endif
                                        </tr>

                                        <!-- Children Rows -->
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

                                                $activeSelection = ($actingAs === 'hr') ? $isHrSelected : $isMgrSelected;
                                            @endphp
                                            <tr class="child-row child-row-{{ $parent->id }} {{ $isEditable ? 'is-clickable' : '' }} {{ $activeSelection ? ($actingAs === 'hr' ? 'is-selected is-hr-selected' : 'is-selected') : '' }}" 
                                                data-parent-id="{{ $parent->id }}" 
                                                data-detail-id="{{ $detail ? $detail->id : '' }}">
                                                <td class="ps-4 py-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="status-icon me-2 text-muted">
                                                            @if($actingAs === 'hr')
                                                                {!! $isHrSelected ? '<i class="ri-checkbox-circle-fill text-primary fs-15"></i>' : '<i class="ri-checkbox-blank-circle-line fs-15"></i>' !!}
                                                            @else
                                                                {!! $isMgrSelected ? '<i class="ri-checkbox-circle-fill text-success fs-15"></i>' : '<i class="ri-checkbox-blank-circle-line fs-15"></i>' !!}
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
                                                    @if($isSelfSelected)
                                                        <span class="badge bg-soft-primary text-primary px-2 py-0.5 fs-11"><i class="ri-checkbox-circle-fill me-1"></i>NV chọn</span>
                                                    @else
                                                        <span class="text-muted fs-11">-</span>
                                                    @endif
                                                </td>
                                                
                                                <!-- Cột Quản lý chấm -->
                                                <td class="text-center py-2">
                                                    @if ($actingAs == 'manager')
                                                        <div class="form-check d-flex justify-content-center mb-0">
                                                            <input class="form-check-input score-radio mgr-radio" 
                                                                   type="radio" 
                                                                   name="manager_choice[{{ $parent->id }}]" 
                                                                   value="{{ $detail ? $detail->id : '' }}" 
                                                                   data-parent-id="{{ $parent->id }}"
                                                                   data-score="{{ (float)($child->max_score ?? 0) }}"
                                                                   {{ $isMgrSelected ? 'checked' : '' }}>
                                                        </div>
                                                    @else
                                                        @if($isMgrSelected)
                                                            <span class="badge bg-soft-success text-success px-2 py-0.5 fs-11"><i class="ri-checkbox-circle-fill me-1"></i>Đã chọn</span>
                                                        @else
                                                            <span class="text-muted fs-11">-</span>
                                                        @endif
                                                    @endif
                                                </td>

                                                <!-- Cột HR chốt -->
                                                @if($isHR || $evaluation->status == 'hr_approved')
                                                <td class="text-center py-2">
                                                    @if ($actingAs == 'hr' && in_array($evaluation->status, ['submitted', 'manager_reviewed', 'hr_approved']))
                                                        <div class="form-check d-flex justify-content-center mb-0">
                                                            <input class="form-check-input score-radio hr-radio" 
                                                                   type="radio" 
                                                                   name="hr_choice[{{ $parent->id }}]" 
                                                                   value="{{ $detail ? $detail->id : '' }}" 
                                                                   data-parent-id="{{ $parent->id }}"
                                                                   data-score="{{ (float)($child->max_score ?? 0) }}"
                                                                   {{ $isHrSelected ? 'checked' : '' }}>
                                                        </div>
                                                    @else
                                                        @if($isHrSelected)
                                                            <span class="badge bg-soft-danger text-danger px-2 py-0.5 fs-11"><i class="ri-checkbox-circle-fill me-1"></i>Đã chốt</span>
                                                        @else
                                                            <span class="text-muted fs-11">-</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @else
                                        <!-- Top level leaves (Tiêu chí độc lập không con) -->
                                        @php
                                            $detail = $evaluation->details->where('criteria_id', $parent->id)->first();
                                            $isSelfSelected = $detail ? $detail->is_self_selected : false;
                                            $isMgrSelected = $detail ? ($hasAnyManagerSelected ? $detail->is_manager_selected : $isSelfSelected) : false;
                                            $isHrSelected = $detail ? ($hasAnyHrSelected ? $detail->is_hr_selected : $isMgrSelected) : false;
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
                                                {!! $isSelfSelected ? '<span class="badge bg-soft-primary text-primary px-2 py-0.5 fs-11"><i class="ri-checkbox-circle-fill me-1"></i>NV chọn</span>' : '<span class="text-muted fs-11">-</span>' !!}
                                            </td>
                                            
                                            <td class="text-center py-2">
                                                @if ($actingAs == 'manager')
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-single mgr-radio" 
                                                               style="transform: scale(1.15); cursor: pointer;" 
                                                               type="checkbox" 
                                                               name="single_choice[{{ $parent->id }}]" 
                                                               value="{{ $detail ? $detail->id : '' }}" 
                                                               data-parent-id="{{ $parent->id }}"
                                                               data-score="{{ (float)($parent->max_score ?? 0) }}"
                                                               {{ $isMgrSelected ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    {!! $isMgrSelected ? '<span class="badge bg-soft-success text-success px-2 py-0.5 fs-11"><i class="ri-checkbox-circle-fill me-1"></i>Đã chọn</span>' : '<span class="text-muted fs-11">-</span>' !!}
                                                @endif
                                            </td>

                                            @if($isHR || $evaluation->status == 'hr_approved')
                                            <td class="text-center py-2">
                                                @if ($actingAs == 'hr' && in_array($evaluation->status, ['submitted', 'manager_reviewed', 'hr_approved']))
                                                    <div class="form-check d-flex justify-content-center mb-0">
                                                        <input class="form-check-input score-single hr-radio" 
                                                               style="transform: scale(1.15); cursor: pointer;" 
                                                               type="checkbox" 
                                                               name="single_choice[{{ $parent->id }}]" 
                                                               value="{{ $detail ? $detail->id : '' }}" 
                                                               data-parent-id="{{ $parent->id }}"
                                                               data-score="{{ (float)($parent->max_score ?? 0) }}"
                                                               {{ $isHrSelected ? 'checked' : '' }}>
                                                    </div>
                                                @else
                                                    {!! $isHrSelected ? '<span class="badge bg-soft-danger text-danger px-2 py-0.5 fs-11"><i class="ri-checkbox-circle-fill me-1"></i>Đã chốt</span>' : '<span class="text-muted fs-11">-</span>' !!}
                                                @endif
                                            </td>
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
                        <h6 class="card-title fs-13 fw-bold text-dark mb-2">
                            <i class="ri-chat-smile-2-line text-primary me-1"></i>Ý kiến nhận xét & Đánh giá
                        </h6>

                        @if ($evaluation->self_note)
                        <div class="mb-2 p-2 rounded-2" style="background: #eff6ff; border-left: 3px solid #3b82f6;">
                            <div class="d-flex align-items-center mb-0.5">
                                <i class="ri-user-voice-fill text-primary fs-14 me-1"></i>
                                <span class="text-primary fw-bold fs-12">Ghi chú tự đánh giá của Nhân sự</span>
                            </div>
                            <p class="mb-0 text-dark fs-12 ps-3">{{ $evaluation->self_note }}</p>
                        </div>
                        @endif

                        @if ($isManager)
                            <div class="mb-2">
                                <label class="form-label fw-bold text-dark fs-12 mb-1">
                                    <i class="ri-shield-user-line text-success me-1"></i> Nhận xét & Đóng góp ý kiến của Quản lý trực tiếp
                                </label>
                                <textarea name="note" class="form-control fs-12" rows="2" 
                                          style="border-radius: 6px;"
                                          placeholder="Nhập nhận xét về năng lực, tinh thần trách nhiệm của nhân sự..."
                                          {{ $actingAs == 'manager' ? '' : 'disabled' }}>{{ $evaluation->manager_note }}</textarea>
                            </div>
                        @elseif ($evaluation->manager_note)
                            <div class="mb-2 p-2 rounded-2" style="background: #f0fdf4; border-left: 3px solid #10b981;">
                                <div class="d-flex align-items-center mb-0.5">
                                    <i class="ri-shield-user-fill text-success fs-14 me-1"></i>
                                    <span class="text-success fw-bold fs-12">Nhận xét của Quản lý trực tiếp</span>
                                </div>
                                <p class="mb-0 text-dark fs-12 ps-3">{{ $evaluation->manager_note }}</p>
                            </div>
                        @endif

                        @if ($isHR)
                            <div class="mb-0">
                                <label class="form-label fw-bold text-dark fs-12 mb-1">
                                    <i class="ri-award-line text-danger me-1"></i> Ý kiến & Kết luận của Phòng Nhân sự / Ban Giám đốc
                                </label>
                                <textarea name="note" class="form-control fs-12" rows="2" 
                                          style="border-radius: 6px;"
                                          placeholder="Nhập kết luận, định hướng hoặc ghi chú chốt đánh giá..."
                                          {{ $actingAs == 'hr' ? '' : 'disabled' }}>{{ $evaluation->hr_note }}</textarea>
                            </div>
                        @elseif ($evaluation->hr_note)
                            <div class="mb-0 p-2 rounded-2" style="background: #fef2f2; border-left: 3px solid #ef4444;">
                                <div class="d-flex align-items-center mb-0.5">
                                    <i class="ri-award-fill text-danger fs-14 me-1"></i>
                                    <span class="text-danger fw-bold fs-12">Nhận xét của Phòng Nhân sự / BGĐ (Chốt)</span>
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
                                        {{ mb_substr($evaluation->employee->name ?? 'N', 0, 1) }}
                                    </div>
                                </div>
                                <div class="overflow-hidden">
                                    <h6 class="text-white mb-0 fw-bold text-truncate fs-13">{{ $evaluation->employee->name ?? 'N/A' }}</h6>
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
                                    @if ($evaluation->status == 'submitted')
                                        <span class="badge bg-warning text-dark px-1.5 py-0.5 fs-11"><i class="ri-time-line me-1"></i>Chờ QL duyệt</span>
                                    @elseif ($evaluation->status == 'manager_reviewed')
                                        <span class="badge bg-info text-white px-1.5 py-0.5 fs-11"><i class="ri-check-double-line me-1"></i>Chờ HR chốt</span>
                                    @elseif ($evaluation->status == 'hr_approved')
                                        <span class="badge bg-success px-1.5 py-0.5 fs-11"><i class="ri-checkbox-circle-line me-1"></i>Đã chốt</span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center p-1.5 rounded-2 mb-2" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <span class="text-muted fs-11">NV tự chấm:</span>
                                <strong class="text-primary fs-12">{{ (float)$evaluation->self_total_score }} đ</strong>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="h-100 p-2 rounded-2 text-center d-flex flex-column justify-content-between" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <span class="d-block text-muted fs-10 text-uppercase fw-semibold">
                                            {{ $actingAs == 'hr' ? 'Điểm HR chốt' : 'Điểm QL chấm' }}
                                        </span>
                                        <div class="my-auto py-1">
                                            <span class="fs-18 fw-bold text-primary d-block lh-1" id="sidebar-score-display">{{ (float)$displayScore }}</span>
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

                            <!-- Tiến độ chấm -->
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
                            @if ($actingAs == 'manager')
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-secondary btn-sm flex-fill fw-semibold py-1.5 fs-12 text-nowrap">
                                    <i class="ri-save-line me-1"></i> Lưu nháp
                                </button>
                                <button type="button" class="btn btn-primary btn-sm flex-fill fw-semibold py-1.5 btn-approve shadow-sm fs-12 text-nowrap">
                                    <i class="ri-send-plane-2-fill me-1"></i> Duyệt & Gửi HR
                                </button>
                            </div>
                            @elseif ($actingAs == 'hr')
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-secondary btn-sm flex-fill fw-semibold py-1.5 fs-12 text-nowrap">
                                    <i class="ri-save-line me-1"></i> Lưu nháp
                                </button>
                                <button type="button" class="btn btn-success btn-sm flex-fill fw-semibold py-1.5 btn-approve shadow-sm fs-12 text-nowrap">
                                    <i class="ri-shield-check-fill me-1"></i> {{ $evaluation->status == 'hr_approved' ? 'Cập nhật điểm' : 'Chốt điểm' }}
                                </button>
                            </div>
                            @else
                            <div class="d-grid">
                                <a href="{{ route('backend.evaluation-approvals.index') }}" class="btn btn-light btn-sm py-1.5 fs-12">
                                    <i class="ri-arrow-left-line me-1"></i> Quay lại danh sách
                                </a>
                            </div>
                            @endif

                            <div class="text-center text-muted fs-10 mt-2">
                                @if ($isEditable)
                                    <i class="ri-information-line text-info me-1"></i> Nhấp trực tiếp vào dòng để chọn điểm.
                                @else
                                    <i class="ri-eye-line text-secondary me-1"></i> Đang ở chế độ xem kết quả.
                                @endif
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
                    <strong class="text-primary fs-15" id="mobile-score-display">{{ (float)$displayScore }}đ</strong>
                    <span class="badge fs-11 px-2 py-0 ms-1 grade-badge-pill" id="mobile-grade-display" style="background-color: #10b981;">{{ $initialGradeName }}</span>
                </div>
                @if ($isEditable)
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">Lưu</button>
                    <button type="button" class="btn btn-sm btn-primary py-1 px-2 fs-12 btn-approve">Duyệt</button>
                </div>
                @endif
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actingAs = '{{ $actingAs }}';
        const isEditable = {{ $isEditable ? 'true' : 'false' }};
        const activeRadioSelector = actingAs === 'hr' ? '.hr-radio' : '.mgr-radio';
        const activeRadios = document.querySelectorAll(activeRadioSelector);
        const sidebarScoreDisplay = document.getElementById('sidebar-score-display');
        const mobileScoreDisplay = document.getElementById('mobile-score-display');
        const sidebarGradeDisplay = document.getElementById('sidebar-grade-display');
        const mobileGradeDisplay = document.getElementById('mobile-grade-display');
        const completionRatio = document.getElementById('completion-ratio');
        const completionBar = document.getElementById('completion-progress-bar');

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
                const scoreSpan = document.getElementById(actingAs === 'hr' ? `parent-hr-score-${parentId}` : `parent-mgr-score-${parentId}`);
                const checkedRadio = document.querySelector(`.score-radio[data-parent-id="${parentId}"]${activeRadioSelector}:checked`);
                
                // Cập nhật highlight cho các dòng con
                const childRows = document.querySelectorAll(`.child-row-${parentId}`);
                childRows.forEach(cr => {
                    const r = cr.querySelector(activeRadioSelector);
                    const statusIcon = cr.querySelector('.status-icon');
                    if (r && r.checked) {
                        cr.classList.add('is-selected');
                        if (actingAs === 'hr') cr.classList.add('is-hr-selected');
                        if (statusIcon) {
                            statusIcon.innerHTML = actingAs === 'hr' 
                                ? '<i class="ri-checkbox-circle-fill text-primary fs-15"></i>' 
                                : '<i class="ri-checkbox-circle-fill text-success fs-15"></i>';
                        }
                    } else {
                        cr.classList.remove('is-selected');
                        cr.classList.remove('is-hr-selected');
                        if (statusIcon) statusIcon.innerHTML = '<i class="ri-checkbox-blank-circle-line fs-15"></i>';
                    }
                });

                if (checkedRadio) {
                    completedParents++;
                    const score = parseFloat(checkedRadio.dataset.score) || 0;
                    if (scoreSpan) {
                        scoreSpan.innerHTML = `<strong>${score} đ</strong>`;
                    }
                    total += score;
                } else if (scoreSpan && isEditable) {
                    scoreSpan.textContent = '-';
                }
            });

            const singles = document.querySelectorAll(`.score-single${activeRadioSelector}:checked`);
            singles.forEach(chk => {
                completedParents++;
                total += parseFloat(chk.dataset.score) || 0;
            });

            // Làm tròn 2 số thập phân chuẩn tránh floating point error
            total = Math.round(total * 100) / 100;

            if (sidebarScoreDisplay) sidebarScoreDisplay.textContent = total;
            if (mobileScoreDisplay) mobileScoreDisplay.textContent = `${total} đ`;

            // Cập nhật tiến độ
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

                for (let g of sortedGrades) {
                    if (total >= (g.min - 0.001) && total <= (g.max + 0.001)) {
                        currentGradeName = g.name;
                        break;
                    }
                }

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

        if (isEditable) {
            // Cho phép click vào bất kỳ đâu trên dòng con để chọn
            document.querySelectorAll('.child-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target && (e.target.classList.contains('score-radio') || e.target.tagName === 'INPUT')) {
                        return;
                    }
                    const radio = this.querySelector(activeRadioSelector);
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

            activeRadios.forEach(radio => {
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

            document.querySelectorAll(`.score-single${activeRadioSelector}`).forEach(chk => {
                chk.addEventListener('change', updateScoreAndGrade);
            });
        }

        // Chạy khởi tạo lúc tải trang
        updateScoreAndGrade();

        // Xử lý nút Duyệt bằng SweetAlert2
        document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const isHrAction = actingAs === 'hr';
                Swal.fire({
                    title: isHrAction ? 'Xác nhận chốt điểm & xếp loại?' : 'Xác nhận duyệt & gửi lên HR?',
                    text: isHrAction 
                        ? 'Phiếu sẽ được chốt điểm chính thức và chuyển sang trạng thái hoàn thành.'
                        : 'Sau khi duyệt, phiếu sẽ được chuyển tiếp cho Phòng Nhân sự / Ban Giám đốc xét duyệt.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: isHrAction ? '#10b981' : '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: isHrAction ? 'Đồng ý, chốt điểm!' : 'Đồng ý, duyệt ngay!',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('approval-form');
                        let input = form.querySelector('input[name="approve"]');
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'approve';
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
