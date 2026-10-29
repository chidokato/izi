@extends('backend.layouts.app')

@section('title', 'Duyệt đánh giá nhân viên')
@section('page_title', 'Danh sách phiếu đánh giá cần xét duyệt')
@section('breadcrumb', 'Duyệt đánh giá')

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 fs-15 fw-bold text-dark">
                    <i class="ri-user-follow-line text-primary me-2"></i>Phiếu đánh giá nhân viên cấp dưới & phòng ban
                </h5>
                <span class="text-muted fs-12">Xem xét, chấm điểm và phê duyệt kết quả đánh giá năng lực định kỳ</span>
            </div>
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
                                                {{ mb_substr($eval->employee->name ?? 'N', 0, 1) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <strong class="text-dark fs-14">{{ $eval->employee->name ?? 'N/A' }}</strong>
                                                <span class="badge bg-light text-primary border px-2 py-0 fs-11">
                                                    {{ $eval->employee->evaluation_type_name }}
                                                </span>
                                            </div>
                                            <div class="text-muted fs-12">
                                                {{ $eval->employee->department->name ?? 'Phòng ban' }} • {{ $eval->employee->position ?? 'Nhân sự' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="fw-semibold text-dark fs-13">Tháng {{ $eval->month }}/{{ $eval->year }}</span>
                                </td>
                                <td class="py-3">
                                    @if ($eval->status == 'submitted')
                                        <span class="badge bg-soft-warning text-warning border border-warning px-2 py-1 fs-12">
                                            <i class="ri-time-line me-1"></i>Chờ QL duyệt
                                        </span>
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
                                    @if($eval->status != 'submitted' || (float)$mgrScore > 0)
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
                                    Không có phiếu đánh giá nào cần xét duyệt.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
