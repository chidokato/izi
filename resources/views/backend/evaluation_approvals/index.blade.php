@extends('backend.layouts.app')

@section('title', 'Duyệt đánh giá nhân viên')
@section('page_title', 'Danh sách phiếu đánh giá cần xét duyệt')
@section('breadcrumb', 'Duyệt đánh giá')

@push('styles')
<style>
    .nav-tabs-custom .nav-link {
        font-size: 13px;
        padding: 10px 18px;
        color: #495057;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #2563eb;
    }
    .nav-tabs-custom .nav-link.active {
        color: #2563eb !important;
        font-weight: 600;
        border-bottom: 2px solid #2563eb !important;
    }
</style>
@endpush

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <h5 class="card-title mb-0 fs-15 fw-bold text-dark">
                        <i class="ri-user-follow-line text-primary me-2"></i>Phiếu đánh giá nhân viên cấp dưới & phòng ban
                    </h5>
                    <span class="text-muted fs-12">Xem xét, chấm điểm và phê duyệt kết quả đánh giá năng lực định kỳ</span>
                </div>
            </div>

            <!-- TABS PHÂN LOẠI: Chờ duyệt, Chưa duyệt, Đã duyệt, Tất cả -->
            <ul class="nav nav-tabs nav-tabs-custom border-bottom-0 mt-2" role="tablist">
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ $currentStatus === 'waiting' ? 'active' : '' }}" 
                       href="{{ route('backend.evaluation-approvals.index', array_merge(request()->except(['page']), ['status' => 'waiting'])) }}"
                       title="Các phiếu đang chờ bạn duyệt">
                        <i class="ri-time-line me-1 align-bottom text-warning"></i> Chờ duyệt 
                        <span class="badge {{ $currentStatus === 'waiting' ? 'bg-warning text-dark' : 'bg-warning-subtle text-warning' }} rounded-pill align-middle ms-1">
                            {{ $counts['waiting'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ $currentStatus === 'pending' ? 'active' : '' }}" 
                       href="{{ route('backend.evaluation-approvals.index', array_merge(request()->except(['page']), ['status' => 'pending'])) }}"
                       title="Các phiếu chưa hoàn thành phê duyệt">
                        <i class="ri-hourglass-2-line me-1 align-bottom text-info"></i> Chưa duyệt 
                        <span class="badge {{ $currentStatus === 'pending' ? 'bg-info text-white' : 'bg-info-subtle text-info' }} rounded-pill align-middle ms-1">
                            {{ $counts['pending'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ $currentStatus === 'approved' ? 'active' : '' }}" 
                       href="{{ route('backend.evaluation-approvals.index', array_merge(request()->except(['page']), ['status' => 'approved'])) }}"
                       title="Các phiếu đã được phê duyệt">
                        <i class="ri-checkbox-circle-line me-1 align-bottom text-success"></i> Đã duyệt 
                        <span class="badge {{ $currentStatus === 'approved' ? 'bg-success text-white' : 'bg-success-subtle text-success' }} rounded-pill align-middle ms-1">
                            {{ $counts['approved'] ?? 0 }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ $currentStatus === 'all' ? 'active' : '' }}" 
                       href="{{ route('backend.evaluation-approvals.index', array_merge(request()->except(['page']), ['status' => 'all'])) }}"
                       title="Tất cả phiếu đánh giá">
                        <i class="ri-apps-line me-1 align-bottom text-primary"></i> Tất cả 
                        <span class="badge {{ $currentStatus === 'all' ? 'bg-primary text-white' : 'bg-light text-muted border' }} rounded-pill align-middle ms-1">
                            {{ $counts['all'] ?? 0 }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- BỘ LỌC TÌM KIẾM -->
        <div class="card-body border-top border-bottom bg-light py-2 px-3">
            <form method="GET" action="{{ route('backend.evaluation-approvals.index') }}">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <div class="row g-2 align-items-center">
                    <div class="col-xxl-4 col-md-4 col-sm-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="ri-search-line text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" placeholder="Nhập tên nhân viên cần tìm..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-xxl-3 col-md-3 col-sm-6">
                        <select class="form-select form-select-sm" name="department_id">
                            <option value="">Tất cả phòng ban</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl-2 col-md-2 col-sm-4">
                        <select class="form-select form-select-sm" name="month">
                            <option value="">Tất cả tháng</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-xxl-2 col-md-2 col-sm-4">
                        <select class="form-select form-select-sm" name="year">
                            <option value="">Tất cả năm</option>
                            @php $currYear = (int)date('Y'); @endphp
                            @for($y = $currYear; $y >= $currYear - 2; $y--)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-xxl-1 col-md-1 col-sm-4 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Lọc">
                            <i class="ri-filter-2-line"></i>
                        </button>
                        @if(request()->filled('search') || request()->filled('department_id') || request()->filled('month') || request()->filled('year'))
                            <a href="{{ route('backend.evaluation-approvals.index', ['status' => $currentStatus]) }}" class="btn btn-light btn-sm border" title="Xóa bộ lọc">
                                <i class="ri-refresh-line"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            @php
                if (!function_exists('getApprovalGradeBadge')) {
                    function getApprovalGradeBadge($score) {
                        if ($score === null || $score === '' || (float)$score <= 0) {
                            return '<span class="text-muted fs-12">-</span>';
                        }
                        $matched = \App\Models\EvaluationGrade::getGradeForScore($score);
                        if (!$matched) {
                            return '<span class="badge bg-secondary fs-12">-</span> <span class="text-muted fs-12">(' . (float)$score . 'đ)</span>';
                        }
                        $colors = [
                            'A' => 'background-color: #10b981;',
                            'B' => 'background-color: #3b82f6;',
                            'C' => 'background-color: #f59e0b;',
                            'D' => 'background-color: #f97316;',
                            'E' => 'background-color: #ef4444;',
                        ];
                        $style = $colors[strtoupper($matched->name)] ?? 'background-color: #64748b;';
                        return '<span class="badge px-2 py-1 fs-12 text-white" style="' . $style . '">Hạng ' . $matched->name . '</span> <span class="text-muted fs-12 ms-1">(' . (float)$score . 'đ)</span>';
                    }
                }
            @endphp

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show m-3 border-0 shadow-sm" role="alert">
                    <i class="ri-checkbox-circle-line me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3 border-0 shadow-sm" role="alert">
                    <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="ps-4 text-uppercase fs-12 text-muted fw-bold" style="min-width: 260px;">Nhân viên</th>
                            <th class="text-uppercase fs-12 text-muted fw-bold" style="width: 150px;">Kỳ đánh giá</th>
                            <th class="text-uppercase fs-12 text-muted fw-bold" style="width: 180px;">Trạng thái</th>
                            <th class="text-center text-uppercase fs-12 text-muted fw-bold" style="width: 170px;">Tự chấm</th>
                            <th class="text-center text-uppercase fs-12 text-muted fw-bold" style="width: 170px;">Quản lý chấm</th>
                            <th class="text-center text-uppercase fs-12 text-muted fw-bold" style="width: 170px;">HR chốt</th>
                            <th class="pe-4 text-end text-uppercase fs-12 text-muted fw-bold" style="width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evaluations as $eval)
                            @php
                                $mgrScore = $eval->details->sum('manager_score');
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3 flex-shrink-0">
                                            <div class="avatar-title rounded-circle bg-soft-primary text-primary fs-14 fw-bold">
                                                {{ mb_substr($eval->employee?->name ?? 'N', 0, 1) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <strong class="text-dark fs-14">{{ $eval->employee?->name_with_position ?? $eval->employee?->name ?? 'N/A' }}</strong>
                                                <span class="badge bg-light text-primary border px-2 py-0 fs-11">
                                                    {{ $eval->employee?->evaluation_type_name ?? $eval->evaluation_type_name ?? 'Back' }}
                                                </span>
                                            </div>
                                            <div class="text-muted fs-12">
                                                {{ $eval->employee?->department?->name ?? 'Phòng ban' }} • {{ $eval->employee?->position ?? 'Nhân sự' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="fw-semibold text-dark fs-13">Tháng {{ $eval->month }}/{{ $eval->year }}</span>
                                </td>
                                <td class="py-3">
                                    @php
                                        $evalEmp = $eval->employee;
                                        $isSale = ($eval->evaluation_type ?: optional($evalEmp)->evaluation_type) === 'sale';
                                        $hasMgr = !$isSale && ($evalEmp?->manager_l2_id || $evalEmp?->manager_id);
                                    @endphp
                                    @if ($eval->status == 'submitted')
                                        @if ($hasMgr)
                                            <span class="badge bg-soft-warning text-warning border border-warning px-2 py-1 fs-12">
                                                <i class="ri-time-line me-1"></i>Chờ QL duyệt
                                            </span>
                                        @else
                                            <span class="badge bg-soft-info text-info border border-info px-2 py-1 fs-12">
                                                <i class="ri-time-line me-1"></i>Chờ HR duyệt
                                            </span>
                                        @endif
                                    @elseif ($eval->status == 'manager_reviewed')
                                        <span class="badge bg-soft-info text-info border border-info px-2 py-1 fs-12">
                                            <i class="ri-check-double-line me-1"></i>Chờ HR chốt
                                        </span>
                                    @elseif ($eval->status == 'hr_approved')
                                        <span class="badge bg-soft-success text-success border border-success px-2 py-1 fs-12">
                                            <i class="ri-checkbox-circle-line me-1"></i>Đã chốt
                                        </span>
                                    @else
                                        <span class="badge bg-dark px-2 py-1 fs-12">{{ $eval->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center py-3">
                                    {!! getApprovalGradeBadge($eval->self_total_score) !!}
                                </td>
                                <td class="text-center py-3">
                                    @if(!$hasMgr)
                                        <span class="text-muted fs-12">-</span>
                                    @elseif($eval->status != 'submitted' || (float)$mgrScore > 0)
                                        {!! getApprovalGradeBadge($mgrScore) !!}
                                    @else
                                        <span class="text-muted fs-12">-</span>
                                    @endif
                                </td>
                                <td class="text-center py-3">
                                    @if($eval->status == 'hr_approved' && (float)$eval->final_score > 0)
                                        {!! getApprovalGradeBadge($eval->final_score) !!}
                                    @else
                                        <span class="text-muted fs-12">-</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end py-3">
                                    <a href="{{ route('backend.evaluation-approvals.edit', $eval->id) }}" class="btn btn-sm btn-primary px-3 py-1">
                                        <i class="ri-eye-line me-1"></i> Xem / Duyệt
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="ri-inbox-line fs-36 d-block mb-2 text-muted"></i>
                                    @if($currentStatus === 'waiting')
                                        Tuyệt vời! Hiện tại không có phiếu nào đang chờ bạn duyệt.
                                    @elseif($currentStatus === 'pending')
                                        Không có phiếu đánh giá nào đang trong quá trình xét duyệt.
                                    @elseif($currentStatus === 'approved')
                                        Chưa có phiếu đánh giá nào đã được duyệt hoàn tất.
                                    @else
                                        Không tìm thấy phiếu đánh giá nào.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($evaluations->hasPages())
                <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-muted fs-12">
                        Hiển thị từ <strong>{{ $evaluations->firstItem() }}</strong> đến <strong>{{ $evaluations->lastItem() }}</strong> trong tổng số <strong>{{ $evaluations->total() }}</strong> phiếu
                    </span>
                    {{ $evaluations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
