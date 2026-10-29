@extends('backend.layouts.app')

@section('title', 'Đánh giá của tôi')
@section('page_title', 'Đánh giá năng lực cá nhân')
@section('breadcrumb', 'Đánh giá của tôi')

@section('content')
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-0 fs-15 fw-bold text-dark">
                    <i class="ri-user-star-line text-primary me-2"></i>Lịch sử đánh giá năng lực của bạn
                </h5>
                <span class="text-muted fs-12">Theo dõi kết quả tự chấm và xếp loại chính thức qua các tháng</span>
            </div>
            @php
                $currentDay = date('j');
                $isOpen = $currentDay >= 16 && $currentDay <= 30;
            @endphp
            <div>
                @if($isOpen)
                    <a href="{{ route('backend.my-evaluations.create') }}" class="btn btn-primary shadow-sm px-3 py-2 fw-semibold">
                        <i class="ri-add-line me-1"></i> Tạo phiếu tháng này (Tháng {{ date('n') }})
                    </a>
                @else
                    <button type="button" class="btn btn-secondary px-3 py-2" disabled title="Chỉ mở từ ngày 16 đến 30 hàng tháng">
                        <i class="ri-lock-line me-1"></i> Tạo phiếu tháng này (Mở từ ngày 16)
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @php
                if (!function_exists('getGradeBadge')) {
                    function getGradeBadge($score) {
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
                            <th class="ps-4 text-uppercase fs-12 text-muted fw-bold" style="width: 200px;">Kỳ đánh giá</th>
                            <th class="text-uppercase fs-12 text-muted fw-bold" style="width: 220px;">Trạng thái</th>
                            <th class="text-center text-uppercase fs-12 text-muted fw-bold" style="width: 200px;">Tổng điểm tự chấm</th>
                            <th class="text-center text-uppercase fs-12 text-muted fw-bold" style="width: 200px;">Điểm chốt (HR)</th>
                            <th class="pe-4 text-end text-uppercase fs-12 text-muted fw-bold" style="width: 180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evaluations as $eval)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title bg-soft-primary text-primary rounded-circle fs-14">
                                                <i class="ri-calendar-check-line"></i>
                                            </span>
                                        </div>
                                        <strong class="text-dark fs-14">Tháng {{ $eval->month }}/{{ $eval->year }}</strong>
                                    </div>
                                </td>
                                <td class="py-3">
                                    @if ($eval->status == 'draft')
                                        <span class="badge bg-soft-secondary text-secondary border px-2 py-1 fs-12">
                                            <i class="ri-edit-line me-1"></i>Bản nháp
                                        </span>
                                    @elseif ($eval->status == 'submitted')
                                        <span class="badge bg-soft-warning text-warning border border-warning px-2 py-1 fs-12">
                                            <i class="ri-time-line me-1"></i>Chờ QL duyệt
                                        </span>
                                    @elseif ($eval->status == 'manager_reviewed')
                                        <span class="badge bg-soft-info text-info border border-info px-2 py-1 fs-12">
                                            <i class="ri-check-double-line me-1"></i>Chờ HR chốt
                                        </span>
                                    @elseif ($eval->status == 'hr_approved')
                                        <span class="badge bg-soft-success text-success border border-success px-2 py-1 fs-12">
                                            <i class="ri-checkbox-circle-line me-1"></i>Đã hoàn thành
                                        </span>
                                    @else
                                        <span class="badge bg-dark px-2 py-1 fs-12">{{ $eval->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center py-3">
                                    {!! getGradeBadge($eval->self_total_score) !!}
                                </td>
                                <td class="text-center py-3">
                                    @if($eval->status == 'hr_approved' && (float)$eval->final_score > 0)
                                        {!! getGradeBadge($eval->final_score) !!}
                                    @else
                                        <span class="text-muted fs-12">Chưa chốt</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end py-3">
                                    <a href="{{ route('backend.my-evaluations.edit', $eval->id) }}" class="btn btn-sm {{ in_array($eval->status, ['draft']) ? 'btn-primary' : 'btn-soft-primary' }} px-3 py-1">
                                        {{ in_array($eval->status, ['draft']) ? 'Chấm điểm' : 'Xem chi tiết' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="ri-file-list-3-line fs-36 d-block mb-2 text-muted"></i>
                                    Bạn chưa có phiếu đánh giá nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
