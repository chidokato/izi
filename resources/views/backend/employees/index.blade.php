@extends('backend.layouts.app')
@section('title', 'Nhân viên')
@section('page_title', 'Danh sách nhân viên')
@section('breadcrumb', 'Nhân viên')
@section('content')
@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <h5 class="card-title mb-0">Nhân viên <span class="badge bg-primary ms-1">{{ $employees->total() }}</span></h5>
        <a class="btn btn-soft-primary" href="{{ route('backend.attendance.index') }}">Nhập từ file chấm công</a>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('backend.employees.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="employee-q" class="form-label">Mã/tên nhân viên</label>
                <input id="employee-q" class="form-control" name="q" value="{{ request('q') }}" placeholder="Nhập mã hoặc họ tên">
            </div>
            <div class="col-md-2">
                <label for="employee-department" class="form-label">Phòng ban</label>
                <select id="employee-department" name="department_id" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string)request('department_id') === (string)$department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="employee-position" class="form-label">Chức vụ</label>
                <select id="employee-position" name="position" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach(['employee'=>'Nhân viên','team_leader'=>'Trưởng nhóm','manager'=>'Trưởng phòng','director'=>'Giám đốc'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('position') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="employee-status" class="form-label">Trạng thái</label>
                <select id="employee-status" name="status" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach(['active'=>'Đang làm việc','inactive'=>'Công tác viên','resigned'=>'Nghỉ việc'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary">Lọc</button>
                <a class="btn btn-light" href="{{ route('backend.employees.index') }}">Bỏ lọc</a>
            </div>
        </form>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <button type="button" class="btn btn-sm btn-primary d-none" id="btn-bulk-edit-manager">Sửa người duyệt hàng loạt</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-nowrap align-middle">
                <thead class="table-light"><tr>
                    <th style="width: 40px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                        </div>
                    </th>
                    <th>STT</th><th>Mã nhân viên</th><th>Họ tên</th><th>Phòng ban</th><th>Chức vụ</th><th>Người duyệt phiếu</th><th>Trạng thái</th><th>Chấm công</th>
                </tr></thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>
                            <div class="form-check">
                                <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}">
                            </div>
                        </td>
                        <td>{{ $employees->firstItem() + $loop->index }}</td>
                        <td>{{ $employee->employee_code }}</td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->department_name ?? 'Chưa có phòng ban' }}</td>
                        <td>
                            <select class="form-select form-select-sm position-select fw-bold {{ $employee->position === 'director' ? 'text-danger' : ($employee->position === 'manager' ? 'text-primary' : ($employee->position === 'team_leader' ? 'text-info' : 'text-secondary')) }}" data-id="{{ $employee->id }}">
                                @foreach(['employee'=>'Nhân viên','team_leader'=>'Trưởng nhóm','manager'=>'Trưởng phòng','director'=>'Giám đốc'] as $value=>$label)
                                    <option value="{{ $value }}" class="text-body" @selected($employee->position === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm manager-select" data-id="{{ $employee->id }}">
                                <option value="">-- Trực tiếp Ban giám đốc --</option>
                                @foreach($managers as $manager)
                                    @if($manager->id != $employee->id)
                                        <option value="{{ $manager->id }}" @selected($employee->manager_id == $manager->id)>{{ $manager->name }} ({{ $manager->employee_code }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm status-select fw-bold {{ $employee->status === 'active' ? 'text-success' : ($employee->status === 'inactive' ? 'text-warning' : 'text-danger') }}" data-id="{{ $employee->id }}">
                                @foreach(['active'=>'Đang làm việc','inactive'=>'Công tác viên','resigned'=>'Nghỉ việc'] as $value=>$label)
                                    <option value="{{ $value }}" class="text-body" @selected($employee->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><a class="btn btn-sm btn-soft-primary" href="{{ route('backend.calendar.index', ['employee_id' => $employee->id]) }}">Xem lịch</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Không có nhân viên phù hợp. Bạn có thể tạo nhân viên khi nhập file chấm công.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </div>
</div>

<template id="bulk-manager-template">
    <select id="swal-bulk-manager" class="form-select">
        <option value="">-- Trực tiếp Ban giám đốc --</option>
        @foreach($managers as $manager)
            <option value="{{ $manager->id }}">{{ $manager->name }} ({{ $manager->employee_code }})</option>
        @endforeach
    </select>
</template>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toastMixin = Swal.mixin({
            toast: true,
            position: 'bottom-start',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function () {
                const employeeId = this.dataset.id;
                const status = this.value;
                const url = `{{ route('backend.employees.change-status', ':id') }}`.replace(':id', employeeId);
                const selectElement = this;

                // Update text color
                selectElement.classList.remove('text-success', 'text-warning', 'text-danger');
                if (status === 'active') selectElement.classList.add('text-success');
                else if (status === 'inactive') selectElement.classList.add('text-warning');
                else if (status === 'resigned') selectElement.classList.add('text-danger');

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastMixin.fire({
                            icon: 'success',
                            title: data.message
                        });
                    } else {
                        toastMixin.fire({
                            icon: 'error',
                            title: data.message || 'Có lỗi xảy ra!'
                        });
                    }
                })
                .catch(error => {
                    toastMixin.fire({
                        icon: 'error',
                        title: 'Lỗi máy chủ!'
                    });
                });
            });
        });

        document.querySelectorAll('.position-select').forEach(select => {
            select.addEventListener('change', function () {
                const employeeId = this.dataset.id;
                const position = this.value;
                const url = `{{ route('backend.employees.change-position', ':id') }}`.replace(':id', employeeId);
                const selectElement = this;

                // Update text color
                selectElement.classList.remove('text-secondary', 'text-info', 'text-primary', 'text-danger');
                if (position === 'employee') selectElement.classList.add('text-secondary');
                else if (position === 'team_leader') selectElement.classList.add('text-info');
                else if (position === 'manager') selectElement.classList.add('text-primary');
                else if (position === 'director') selectElement.classList.add('text-danger');

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ position: position })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastMixin.fire({
                            icon: 'success',
                            title: data.message
                        });
                    } else {
                        toastMixin.fire({
                            icon: 'error',
                            title: data.message || 'Có lỗi xảy ra!'
                        });
                    }
                })
                .catch(error => {
                    toastMixin.fire({
                        icon: 'error',
                        title: 'Lỗi máy chủ!'
                    });
                });
            });
        });

        document.querySelectorAll('.manager-select').forEach(select => {
            select.addEventListener('change', function () {
                const employeeId = this.dataset.id;
                const managerId = this.value;
                const url = `{{ route('backend.employees.change-manager', ':id') }}`.replace(':id', employeeId);
                const selectElement = this;

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ manager_id: managerId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastMixin.fire({
                            icon: 'success',
                            title: data.message
                        });
                    } else {
                        // Revert if self-selected
                        if (!managerId) selectElement.value = "";
                        toastMixin.fire({
                            icon: 'error',
                            title: data.message || 'Có lỗi xảy ra!'
                        });
                    }
                })
                .catch(error => {
                    toastMixin.fire({
                        icon: 'error',
                        title: 'Lỗi máy chủ!'
                    });
                });
            });
        });

        // Bulk edit manager logic
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        const btnBulk = document.getElementById('btn-bulk-edit-manager');

        function toggleBulkBtn() {
            const checkedCount = document.querySelectorAll('.employee-checkbox:checked').length;
            if (checkedCount > 0) {
                btnBulk.classList.remove('d-none');
            } else {
                btnBulk.classList.add('d-none');
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleBulkBtn();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked && checkAll) checkAll.checked = false;
                toggleBulkBtn();
            });
        });

        if (btnBulk) {
            btnBulk.addEventListener('click', function() {
                const checkedIds = Array.from(document.querySelectorAll('.employee-checkbox:checked')).map(cb => cb.value);
                if (checkedIds.length === 0) return;

                Swal.fire({
                    title: 'Chọn người duyệt hàng loạt',
                    html: document.getElementById('bulk-manager-template').innerHTML,
                    showCancelButton: true,
                    confirmButtonText: 'Cập nhật',
                    cancelButtonText: 'Hủy',
                    preConfirm: () => {
                        return document.getElementById('swal-bulk-manager').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route("backend.employees.bulk-manager") }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                employee_ids: checkedIds,
                                manager_id: result.value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Thành công', data.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Lỗi', data.message || 'Có lỗi xảy ra', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Lỗi', 'Lỗi kết nối máy chủ', 'error');
                        });
                    }
                });
            });
        }

    });
</script>
@endpush