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

                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label text-primary">Phê duyệt / Xử lý trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="pending" {{ $attendanceRequest->status == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="approved" {{ $attendanceRequest->status == 'approved' ? 'selected' : '' }}>Đồng ý duyệt</option>
                            <option value="rejected" {{ $attendanceRequest->status == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                            <option value="cancelled" {{ $attendanceRequest->status == 'cancelled' ? 'selected' : '' }}>Hủy bỏ</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Cập nhật phiếu</button>
                    <a href="{{ route('backend.attendance-requests.index') }}" class="btn btn-light">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
