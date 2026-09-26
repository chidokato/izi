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
                        @if(isset($canBulkApprove) && $canBulkApprove)
                        <button type="button" class="btn btn-success d-none" id="btn-bulk-approve"><i class="ri-check-double-line align-bottom me-1"></i> Duyệt hàng loạt</button>
                        @endif
                        <a href="{{ route('backend.attendance-requests.create') }}" class="btn btn-primary"><i class="ri-add-line align-bottom me-1"></i> Tạo phiếu mới</a>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-end-0 border-start-0">
                <form>
                    <div class="row g-3">
                        <div class="col-xxl-3 col-sm-4">
                            <input type="text" class="form-control" name="search_employee" placeholder="Nhập mã hoặc tên NV" value="{{ request('search_employee') }}">
                        </div>
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
                                @if(isset($canBulkApprove) && $canBulkApprove)
                                <th style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                    </div>
                                </th>
                                @endif
                                <th>Mã phiếu</th>
                                <th>Nhân viên</th>
                                <th>Loại</th>
                                <th>Thời gian</th>
                                <th>Lý do</th>
                                <th>Ngày tạo</th>
                                <th>Quản lý duyệt</th>
                                <th>NS duyệt</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $req)
                                <tr>
                                    @if(isset($canBulkApprove) && $canBulkApprove)
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input request-checkbox" type="checkbox" value="{{ $req->id }}">
                                        </div>
                                    </td>
                                    @endif
                                    <td>{{ $req->code }}</td>
                                    <td>
                                        <div class="fw-medium">{{ optional($req->employee)->name }}</div>
                                        <small class="text-muted">{{ optional($req->employee)->employee_code }}</small>
                                    </td>
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
                                    @php
                                        $step1 = $req->requestApprovals->where('step', 1)->first();
                                        $step2 = $req->requestApprovals->where('step', 2)->first();
                                        
                                        $canApproveStep1 = false;
                                        if ($step1) {
                                            if ($step1->approver_id == auth()->id() || auth()->user()->isAdmin() || optional($req->employee)->manager_id == auth()->user()->employee_id) {
                                                $canApproveStep1 = true;
                                            }
                                        }
                                        
                                        $canApproveStep2 = false;
                                        if ($step2) {
                                            if ($step2->approver_id == auth()->id() || auth()->user()->isAdmin()) {
                                                $canApproveStep2 = true;
                                            }
                                        }
                                    @endphp
                                    <td id="step1-container-{{ $req->id }}">
                                        @if($step1)
                                            @if($canApproveStep1)
                                                <select class="form-select form-select-sm btn-quick-approve-select {{ $step1->status == 'approved' ? 'border-success text-success' : ($step1->status == 'rejected' ? 'border-danger text-danger' : 'border-warning text-warning') }}" data-id="{{ $req->id }}" data-step="1" style="font-weight: 600;">
                                                    <option value="pending" class="text-warning" {{ $step1->status == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                                    <option value="approved" class="text-success" {{ $step1->status == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                                    <option value="rejected" class="text-danger" {{ $step1->status == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                                                </select>
                                            @else
                                                @if($step1->status == 'approved') <span class="badge bg-success">Đã duyệt</span>
                                                @elseif($step1->status == 'rejected') <span class="badge bg-danger">Từ chối</span>
                                                @else <span class="badge bg-warning">Chờ duyệt</span> @endif
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td id="step2-container-{{ $req->id }}">
                                        @if($step2)
                                            @if($canApproveStep2)
                                                <select class="form-select form-select-sm btn-quick-approve-select {{ $step2->status == 'approved' ? 'border-success text-success' : ($step2->status == 'rejected' ? 'border-danger text-danger' : 'border-warning text-warning') }}" data-id="{{ $req->id }}" data-step="2" style="font-weight: 600;">
                                                    <option value="pending" class="text-warning" {{ $step2->status == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                                    <option value="approved" class="text-success" {{ $step2->status == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                                    <option value="rejected" class="text-danger" {{ $step2->status == 'rejected' ? 'selected' : '' }}>Từ chối</option>
                                                </select>
                                            @else
                                                @if($step2->status == 'approved') <span class="badge bg-success">Đã duyệt</span>
                                                @elseif($step2->status == 'rejected') <span class="badge bg-danger">Từ chối</span>
                                                @else <span class="badge bg-warning">Chờ duyệt</span> @endif
                                            @endif
                                        @else
                                            @if($req->status == 'rejected' && $req->current_approval_step == 1)
                                                <span class="badge bg-danger">Từ chối</span>
                                            @elseif($req->status == 'cancelled')
                                                <span class="badge bg-secondary">Đã hủy</span>
                                            @else
                                                <span class="badge bg-light text-dark">Chờ QL duyệt</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 align-items-center" id="action-buttons-{{ $req->id }}">
                                            <a href="{{ route('backend.attendance-requests.edit', $req->id) }}" class="btn btn-sm btn-info">Chi tiết</a>
                                            @if(auth()->user()->isAdmin())
                                                <form action="{{ route('backend.attendance-requests.destroy', $req->id) }}" method="POST" class="d-inline-block form-delete-request">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ (isset($canBulkApprove) && $canBulkApprove) ? 9 : 8 }}" class="text-center">Chưa có dữ liệu</td></tr>
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
    document.querySelectorAll('.btn-quick-approve-select').forEach(function(select) {
        // Save original value to revert if cancelled or failed
        select.dataset.original = select.value;
        
        select.addEventListener('change', function(e) {
            let id = this.getAttribute('data-id');
            let status = this.value;
            let actionText = status == 'approved' ? 'duyệt' : (status == 'rejected' ? 'từ chối' : 'chuyển về chờ duyệt');
            
            Swal.fire({
                title: 'Xác nhận',
                text: `Bạn có chắc chắn muốn ${actionText} phiếu này?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: status == 'approved' ? '#0ab39c' : (status == 'rejected' ? '#f06548' : '#f7b84b'),
                cancelButtonColor: '#878a99',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.disabled = true;

                    fetch('{{ url("admin/attendance-requests") }}/' + id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            status: status,
                            step: this.getAttribute('data-step')
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.disabled = false;
                        if(data.success) {
                            // Update border and text colors based on new status
                            this.classList.remove('border-success', 'text-success', 'border-danger', 'text-danger', 'border-warning', 'text-warning');
                            if (data.status == 'approved') {
                                this.classList.add('border-success', 'text-success');
                            } else if (data.status == 'rejected') {
                                this.classList.add('border-danger', 'text-danger');
                            } else if (data.status == 'pending') {
                                this.classList.add('border-warning', 'text-warning');
                            }
                            
                            Swal.fire({
                                toast: true,
                                position: 'bottom-start',
                                icon: 'success',
                                title: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Lỗi', 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
                            this.value = this.dataset.original; // Revert
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Lỗi', 'Lỗi kết nối.', 'error');
                        this.disabled = false;
                        this.value = this.dataset.original; // Revert
                    });
                } else {
                    this.value = this.dataset.original; // Revert if cancelled
                }
            });
        });
    });

    document.querySelectorAll('.form-delete-request').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Xóa phiếu?',
                text: 'Bạn có chắc chắn muốn xóa phiếu này? Nếu phiếu đã duyệt, số phép năm (nếu có) sẽ được hoàn lại.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#f06548',
                cancelButtonColor: '#878a99',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    const checkAll = document.getElementById('checkAll');
    const requestCheckboxes = document.querySelectorAll('.request-checkbox');
    const btnBulkApprove = document.getElementById('btn-bulk-approve');

    function toggleBulkButton() {
        const checkedCount = document.querySelectorAll('.request-checkbox:checked').length;
        if (checkedCount > 0) {
            btnBulkApprove.classList.remove('d-none');
        } else {
            btnBulkApprove.classList.add('d-none');
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            requestCheckboxes.forEach(cb => cb.checked = this.checked);
            toggleBulkButton();
        });
    }

    requestCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked && checkAll) checkAll.checked = false;
            toggleBulkButton();
        });
    });

    if (btnBulkApprove) {
        btnBulkApprove.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.request-checkbox:checked')).map(cb => cb.value);
            
            Swal.fire({
                title: 'Duyệt phiếu hàng loạt',
                text: `Bạn muốn duyệt ${selectedIds.length} phiếu đã chọn?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0ab39c',
                cancelButtonColor: '#878a99',
                confirmButtonText: 'Đồng ý duyệt',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Đang xử lý...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch('{{ route("backend.attendance-requests.bulk-approve") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ids: selectedIds,
                            status: 'approved'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Lỗi', data.message || 'Có lỗi xảy ra', 'error');
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                    });
                }
            });
        });
    }
});
</script>
@endpush
