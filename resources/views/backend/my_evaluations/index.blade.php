@extends('backend.layouts.app')

@section('title', 'Đánh giá của tôi')
@section('page_title', 'Đánh giá của tôi')
@section('breadcrumb', 'Đánh giá của tôi')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Danh sách phiếu đánh giá</h4>
            <a href="{{ route('backend.my-evaluations.create') }}" class="btn btn-primary">Tạo phiếu tháng này (Tháng {{ date('n') }})</a>
        </div>
        <div class="card-body">
            @php
                function getGradeName($score, $grades) {
                    if ($score === null || $score === '') return '-';
                    foreach ($grades as $g) {
                        if ($score >= $g->min_score && $score <= $g->max_score) {
                            return $g->name;
                        }
                    }
                    return '-';
                }
            @endphp
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kỳ đánh giá</th>
                            <th>Trạng thái</th>
                            <th>Tổng điểm tự chấm</th>
                            <th>Điểm chốt (HR)</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evaluations as $eval)
                            <tr>
                                <td>Tháng {{ $eval->month }} / {{ $eval->year }}</td>
                                <td>
                                    @if ($eval->status == 'draft')
                                        <span class="badge bg-secondary">Bản nháp</span>
                                    @elseif ($eval->status == 'submitted')
                                        <span class="badge bg-warning">Chờ quản lý duyệt</span>
                                    @elseif ($eval->status == 'manager_reviewed')
                                        <span class="badge bg-info">Chờ HR chốt</span>
                                    @elseif ($eval->status == 'hr_approved')
                                        <span class="badge bg-success">Đã hoàn thành</span>
                                    @else
                                        <span class="badge bg-dark">{{ $eval->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-primary">{{ getGradeName($eval->self_total_score, $grades) }}</strong> 
                                    <span class="text-muted">({{ $eval->self_total_score }})</span>
                                </td>
                                <td>
                                    @if($eval->final_score > 0)
                                        <strong class="text-success">{{ getGradeName($eval->final_score, $grades) }}</strong> 
                                        <span class="text-muted">({{ $eval->final_score }})</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('backend.my-evaluations.edit', $eval->id) }}" class="btn btn-sm btn-soft-primary">
                                        {{ in_array($eval->status, ['draft', 'submitted']) ? 'Chi tiết / Chấm điểm' : 'Xem kết quả' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chưa có phiếu đánh giá nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
