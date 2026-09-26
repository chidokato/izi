@extends('backend.layouts.app')

@section('title', 'Duyệt đánh giá')
@section('page_title', 'Danh sách phiếu chờ duyệt')
@section('breadcrumb', 'Duyệt đánh giá')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Phiếu đánh giá nhân viên</h4>
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

            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nhân viên</th>
                            <th>Kỳ đánh giá</th>
                            <th>Trạng thái</th>
                            <th>Tự chấm</th>
                            <th>Quản lý chấm</th>
                            <th>HR chốt</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evaluations as $eval)
                            <tr>
                                <td><strong>{{ $eval->employee->name ?? 'N/A' }}</strong></td>
                                <td>Tháng {{ $eval->month }} / {{ $eval->year }}</td>
                                <td>
                                    @if ($eval->status == 'submitted')
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
                                    @if($eval->status != 'submitted')
                                        <strong class="text-warning">{{ getGradeName($eval->details->sum('manager_score'), $grades) }}</strong> 
                                        <span class="text-muted">({{ $eval->details->sum('manager_score') }})</span>
                                    @else
                                        -
                                    @endif
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
                                    <a href="{{ route('backend.evaluation-approvals.edit', $eval->id) }}" class="btn btn-sm btn-soft-primary">
                                        Xem / Duyệt
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Không có phiếu đánh giá nào cần duyệt.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
