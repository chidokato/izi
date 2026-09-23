@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Quản lý phiếu yêu cầu</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Danh sách phiếu</h5>
                    <div class="flex-shrink-0">
                        <a href="{{ route('backend.attendance-requests.create') }}" class="btn btn-primary"><i class="ri-add-line align-bottom me-1"></i> Tạo phiếu mới</a>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-end-0 border-start-0">
                <form>
                    <div class="row g-3">
                        <div class="col-xxl-3 col-sm-4">
                            <select class="form-control" name="type">
                                <option value="">Tất cả loại phiếu</option>
                                <option value="paid_leave" {{ request('type') == 'paid_leave' ? 'selected' : '' }}>Nghỉ phép có lương</option>
                                <option value="unpaid_leave" {{ request('type') == 'unpaid_leave' ? 'selected' : '' }}>Nghỉ không lương</option>
                                <option value="business_trip" {{ request('type') == 'business_trip' ? 'selected' : '' }}>Công tác</option>
                                <option value="attendance_adjustment" {{ request('type') == 'attendance_adjustment' ? 'selected' : '' }}>Bổ sung công</option>
                                <option value="overtime" {{ request('type') == 'overtime' ? 'selected' : '' }}>Làm tăng ca</option>
                            </select>
                        </div>
                        <div class="col-xxl-3 col-sm-4">
                            <select class="form-control" name="status">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                            </select>
                        </div>
                        <div class="col-xxl-1 col-sm-4">
                            <div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-search-line me-1 align-bottom"></i> Lọc
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Nhân viên</th>
                                <th>Loại</th>
                                <th>Thời gian</th>
                                <th>Lý do</th>
                                <th>Ngày tạo</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $req)
                                <tr>
                                    <td>{{ $req->code }}</td>
                                    <td>{{ optional($req->employee)->name }}</td>
                                    <td>
                                        @if($req->type == 'paid_leave') Nghỉ phép
                                        @elseif($req->type == 'unpaid_leave') Không lương
                                        @elseif($req->type == 'business_trip') Công tác
                                        @elseif($req->type == 'attendance_adjustment') Bổ sung công
                                        @elseif($req->type == 'overtime') Làm tăng ca
                                        @else {{ $req->type }} @endif
                                    </td>
                                    <td>
                                        @if($req->type == 'business_trip' || $req->type == 'overtime')
                                            {{ $req->start_date->format('H:i d/m/Y') }} 
                                            @if($req->start_date != $req->end_date)
                                            <br> - {{ $req->end_date->format('H:i d/m/Y') }}
                                            @endif
                                        @elseif($req->type == 'attendance_adjustment')
                                            {{ $req->start_date->format('d/m/Y') }}<br>
                                            <small class="text-muted">Xác nhận công ra/vào: {{ $req->start_session == 'morning' ? 'Vào' : 'Ra' }}</small>
                                        @else
                                            {{ $req->start_date->format('d/m/Y') }} <small class="text-muted">({{ $req->start_session == 'full' ? 'Cả ngày' : ($req->start_session == 'morning' ? 'Sáng' : 'Chiều') }})</small>
                                            @if($req->start_date->format('Y-m-d') != $req->end_date->format('Y-m-d') || $req->start_session != $req->end_session)
                                            <br> - {{ $req->end_date->format('d/m/Y') }} <small class="text-muted">({{ $req->end_session == 'full' ? 'Cả ngày' : ($req->end_session == 'morning' ? 'Sáng' : 'Chiều') }})</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($req->reason, 30) }}</td>
                                    <td>{{ $req->created_at->format('H:i d/m/Y') }}</td>
                                    <td id="status-badge-{{ $req->id }}">
                                        @if($req->status == 'pending') <span class="badge bg-warning">Chờ duyệt</span>
                                        @elseif($req->status == 'approved') <span class="badge bg-success">Đã duyệt</span>
                                        @elseif($req->status == 'rejected') <span class="badge bg-danger">Từ chối</span>
                                        @else <span class="badge bg-secondary">{{ $req->status }}</span> @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 align-items-center" id="action-buttons-{{ $req->id }}">
                                            @if($req->status == 'pending')
                                                <button class="btn btn-sm btn-success btn-quick-approve" data-id="{{ $req->id }}" data-status="approved">Duyệt</button>
                                                <button class="btn btn-sm btn-danger btn-quick-approve" data-id="{{ $req->id }}" data-status="rejected">Từ chối</button>
                                            @endif
                                            <a href="{{ route('backend.attendance-requests.edit', $req->id) }}" class="btn btn-sm btn-info">Chi tiết</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">Chưa có dữ liệu</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-quick-approve').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            let id = this.getAttribute('data-id');
            let status = this.getAttribute('data-status');
            
            if (confirm('Bạn có chắc chắn muốn ' + (status == 'approved' ? 'duyệt' : 'từ chối') + ' phiếu này?')) {
                // Disable buttons
                let buttons = document.querySelectorAll('#action-buttons-' + id + ' .btn-quick-approve');
                buttons.forEach(b => b.disabled = true);
                
                fetch('{{ url("admin/attendance-requests") }}/' + id, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        let badgeHtml = '';
                        if (data.status == 'approved') badgeHtml = '<span class=\"badge bg-success\">Đã duyệt</span>';
                        else if (data.status == 'rejected') badgeHtml = '<span class=\"badge bg-danger\">Từ chối</span>';
                        else badgeHtml = '<span class=\"badge bg-secondary\">' + data.status + '</span>';
                        
                        document.getElementById('status-badge-' + id).innerHTML = badgeHtml;
                        
                        // Remove quick buttons
                        buttons.forEach(b => b.remove());
                    } else {
                        alert('Có lỗi xảy ra, vui lòng thử lại.');
                        buttons.forEach(b => b.disabled = false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi kết nối.');
                    buttons.forEach(b => b.disabled = false);
                });
            }
        });
    });
});
</script>
@endpush
