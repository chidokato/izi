@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Chi tiết phiếu #{{ $attendanceRequest->code }}</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.attendance-requests.update', $attendanceRequest->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Nhân viên</label>
                        <input type="text" class="form-control" value="{{ optional($attendanceRequest->employee)->name }} ({{ optional($attendanceRequest->employee)->employee_code }})" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại phiếu</label>
                        <input type="text" class="form-control" value="@if($attendanceRequest->type == 'paid_leave') Nghỉ phép có lương @elseif($attendanceRequest->type == 'unpaid_leave') Nghỉ không lương @elseif($attendanceRequest->type == 'business_trip') Công tác @elseif($attendanceRequest->type == 'attendance_adjustment') Bổ sung công @elseif($attendanceRequest->type == 'overtime') Làm tăng ca @else {{ $attendanceRequest->type }} @endif" disabled>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">@if($attendanceRequest->type == 'attendance_adjustment') Ngày @else Từ thời gian @endif</label>
                            @if($attendanceRequest->type == 'business_trip' || $attendanceRequest->type == 'overtime')
                                <input type="text" class="form-control" value="{{ $attendanceRequest->start_date->format('H:i d/m/Y') }}" disabled>
                            @elseif($attendanceRequest->type == 'attendance_adjustment')
                                <input type="text" class="form-control" value="{{ $attendanceRequest->start_date->format('d/m/Y') }} (Xác nhận công ra/vào: {{ $attendanceRequest->start_session == 'morning' ? 'Vào' : 'Ra' }})" disabled>
                            @else
                                <input type="text" class="form-control" value="{{ $attendanceRequest->start_date->format('d/m/Y') }} (Buổi: {{ $attendanceRequest->start_session == 'full' ? 'Cả ngày' : ($attendanceRequest->start_session == 'morning' ? 'Sáng' : 'Chiều') }})" disabled>
                            @endif
                        </div>
                        @if($attendanceRequest->type != 'attendance_adjustment')
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Đến thời gian</label>
                            @if($attendanceRequest->type == 'business_trip' || $attendanceRequest->type == 'overtime')
                                <input type="text" class="form-control" value="{{ $attendanceRequest->end_date->format('H:i d/m/Y') }}" disabled>
                            @else
                                <input type="text" class="form-control" value="{{ $attendanceRequest->end_date->format('d/m/Y') }} (Buổi: {{ $attendanceRequest->end_session == 'full' ? 'Cả ngày' : ($attendanceRequest->end_session == 'morning' ? 'Sáng' : 'Chiều') }})" disabled>
                            @endif
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lý do</label>
                        <textarea class="form-control" rows="3" disabled>{{ $attendanceRequest->reason }}</textarea>
                    </div>

                    @php
                        $canApprove = false;
                        if ($attendanceRequest->status == 'pending') {
                            $currentApproval = $attendanceRequest->requestApprovals->where('step', $attendanceRequest->current_approval_step)->first();
                            if ($currentApproval) {
                                if ($currentApproval->approver_id == auth()->id() || auth()->user()->isAdmin() || ($attendanceRequest->current_approval_step == 1 && optional($attendanceRequest->employee)->manager_id == auth()->user()->employee_id)) {
                                    $canApprove = true;
                                }
                            }
                        }
                    @endphp

                    @if($canApprove)
                    <hr>
                    <div class="mb-3">
                        <label class="form-label text-primary">Xử lý yêu cầu</label>
                        <select name="status" class="form-control">
                            <option value="pending" selected>--- Chọn thao tác ---</option>
                            <option value="approved">Đồng ý duyệt</option>
                            <option value="rejected">Từ chối</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Xác nhận</button>
                    @endif
                    <a href="{{ route('backend.attendance-requests.index') }}" class="btn btn-light {{ $canApprove ? '' : 'mt-3' }}">Quay lại</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header align-items-center d-flex border-bottom-dashed">
                <h4 class="card-title mb-0 flex-grow-1">Quá trình duyệt phiếu</h4>
            </div>
            <div class="card-body">
                <div class="profile-timeline">
                    <div class="accordion accordion-flush" id="accordionFlushExample">
                        @forelse($attendanceRequest->requestApprovals as $approval)
                        <div class="accordion-item border-0">
                            <div class="accordion-header" id="heading{{$approval->step}}">
                                <a class="accordion-button p-2 shadow-none text-muted" data-bs-toggle="collapse" href="#collapse{{$approval->step}}" aria-expanded="true">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 avatar-xs">
                                            <div class="avatar-title {{ $approval->status == 'approved' ? 'bg-success' : ($approval->status == 'rejected' ? 'bg-danger' : 'bg-warning') }} rounded-circle">
                                                @if($approval->status == 'approved') <i class="ri-check-line"></i>
                                                @elseif($approval->status == 'rejected') <i class="ri-close-line"></i>
                                                @else <i class="ri-time-line"></i>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-14 mb-0 fw-semibold">Bước {{ $approval->step }}: {{ $approval->step == 1 ? 'Quản lý trực tiếp' : 'Hành chính - Nhân sự' }}</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div id="collapse{{$approval->step}}" class="accordion-collapse collapse show" aria-labelledby="heading{{$approval->step}}" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body pt-0" style="{{ $loop->last ? '' : 'border-left: 2px dashed #ced4da;' }} margin-left: 23px; padding-left: 16px;">
                                    @if($approval->status == 'pending')
                                        @if($approval->step == 1 && optional($attendanceRequest->employee)->manager)
                                            <p class="mb-0 text-muted">Đang chờ <strong class="text-dark">{{ $attendanceRequest->employee->manager->name }}</strong> duyệt.</p>
                                        @else
                                            <p class="mb-0 text-muted">Đang chờ <strong class="text-dark">{{ optional($approval->approver)->name }}</strong> duyệt.</p>
                                        @endif
                                    @elseif($approval->status == 'approved')
                                        <p class="mb-1 text-success"><strong class="text-dark">{{ optional($approval->approver)->name }}</strong> đã duyệt</p>
                                        <p class="mb-0 text-muted"><i class="ri-clock-line align-middle me-1"></i> {{ $approval->acted_at ? $approval->acted_at->format('d/m/Y H:i') : '' }}</p>
                                    @elseif($approval->status == 'rejected')
                                        <p class="mb-1 text-danger"><strong class="text-dark">{{ optional($approval->approver)->name }}</strong> đã từ chối</p>
                                        <p class="mb-0 text-muted"><i class="ri-clock-line align-middle me-1"></i> {{ $approval->acted_at ? $approval->acted_at->format('d/m/Y H:i') : '' }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">Chưa có thông tin duyệt.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
