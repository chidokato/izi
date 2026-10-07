@extends('backend.layouts.app')
@section('title', 'Nhân viên')
@section('page_title', 'Danh sách nhân viên')
@section('breadcrumb', 'Nhân viên')
@section('content')
@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endpush

<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <h5 class="card-title mb-0">Nhân viên <span class="badge bg-primary ms-1">{{ $employees->total() }}</span></h5>
        <div class="d-flex gap-2">
            @if(request('view_deleted'))
                <a href="{{ route('backend.employees.index') }}" class="btn btn-info"><i class="ri-eye-line align-bottom me-1"></i> Danh sách hiện tại</a>
            @else
                <a href="{{ route('backend.employees.index', ['view_deleted' => 1]) }}" class="btn btn-warning"><i class="ri-eye-off-line align-bottom me-1"></i> Đã xóa / Ẩn</a>
            @endif
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-leave-input').click()"><i class="ri-file-excel-line align-bottom me-1"></i> Up file phép năm</button>
            <a href="{{ route('backend.employees.create') }}" class="btn btn-success"><i class="ri-add-line align-bottom me-1"></i> Thêm nhân viên</a>
            <form id="import-leave-form" action="{{ route('backend.employees.import-leave') }}" method="POST" enctype="multipart/form-data" class="d-none">
                @csrf
                <input type="file" name="file" id="import-leave-input" accept=".xlsx,.xls,.csv" onchange="document.getElementById('import-leave-form').submit()">
            </form>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('backend.employees.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="employee-q" class="form-label">Mã/tên nhân viên</label>
                <input id="employee-q" class="form-control" name="q" value="{{ request('q') }}" placeholder="Nhập mã hoặc họ tên">
            </div>
            <div class="col-md-2">
                <label for="employee-department" class="form-label">Phòng ban</label>
                <select id="employee-department" name="department_id" class="form-select select2">
                    <option value="">Tất cả</option>
                    <option value="none" @selected(request('department_id') === 'none')>-- Không thuộc phòng nào --</option>
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
                    @foreach(['active'=>'Chính thức','probation'=>'Thử việc','inactive'=>'Công tác viên','resigned'=>'Nghỉ việc'] as $value=>$label)
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
                <button type="button" class="btn btn-sm btn-primary d-none" id="btn-bulk-edit-manager">Cập nhật hàng loạt</button>
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
                    <th>STT</th><th>Mã nhân viên</th><th>Họ tên</th><th>Phòng ban</th><th>Chức vụ</th><th>Người duyệt lần 1</th><th>Người duyệt lần 2</th><th>Trạng thái</th><th>Phép năm</th><th>Thao tác</th>
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
                        <td>
                            <select class="form-select form-select-sm department-select fw-bold text-dark" data-id="{{ $employee->id }}">
                                <option value="">----</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected($employee->department_id == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm position-select fw-bold {{ $employee->position === 'director' ? 'text-danger' : ($employee->position === 'manager' ? 'text-primary' : ($employee->position === 'team_leader' ? 'text-info' : 'text-secondary')) }}" data-id="{{ $employee->id }}">
                                @foreach(['employee'=>'Nhân viên','team_leader'=>'Trưởng nhóm','manager'=>'Trưởng phòng','director'=>'Giám đốc'] as $value=>$label)
                                    <option value="{{ $value }}" class="text-body" @selected($employee->position === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm manager-select" data-id="{{ $employee->id }}">
                                <option value="">----</option>
                                @foreach($managers as $manager)
                                    @if($manager->id != $employee->id && ($manager->department_id == $employee->department_id || $employee->manager_id == $manager->id))
                                        <option value="{{ $manager->id }}" @selected($employee->manager_id == $manager->id)>{{ $manager->name }} ({{ $manager->employee_code }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm hr-select" data-id="{{ $employee->id }}">
                                <option value="">----</option>
                                @foreach($hrs as $hr)
                                    @if($hr->id != $employee->id && ($hr->department_id == $employee->department_id || $employee->manager_l2_id == $hr->id))
                                        <option value="{{ $hr->id }}" @selected($employee->manager_l2_id == $hr->id)>{{ $hr->name }} ({{ $hr->employee_code }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm status-select fw-bold {{ $employee->status === 'active' ? 'text-success' : ($employee->status === 'inactive' ? 'text-warning' : ($employee->status === 'probation' ? 'text-info' : 'text-danger')) }}" data-id="{{ $employee->id }}" data-old-value="{{ $employee->status }}">
                                @foreach(['active'=>'Chính thức','probation'=>'Thử việc','inactive'=>'Công tác viên','resigned'=>'Nghỉ việc'] as $value=>$label)
                                    <option value="{{ $value }}" class="text-body" @selected($employee->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.5" class="form-control form-control-sm annual-leave-input text-center" data-id="{{ $employee->id }}" value="{{ (float)$employee->annual_leave_balance }}" style="width: 70px;">
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                @if($employee->deleted_at)
                                    <form action="{{ route('backend.employees.restore', $employee->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-soft-success"><i class="ri-refresh-line"></i> Khôi phục</button>
                                    </form>
                                @else
                                    <a href="{{ route('backend.employees.edit', $employee->id) }}" class="btn btn-sm btn-soft-primary"><i class="ri-pencil-fill"></i> Sửa</a>
                                    <form action="{{ route('backend.employees.destroy', $employee->id) }}" method="POST" class="d-inline-block delete-employee-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger"><i class="ri-delete-bin-fill"></i> Xóa</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Không có nhân viên phù hợp. Bạn có thể tạo nhân viên khi nhập file chấm công.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </div>
</div>

<template id="bulk-manager-template">
    <div class="mb-3 text-start">
        <label class="form-label">Phòng ban</label>
        <select id="swal-bulk-department" class="form-select">
            <option value="no_change">-- Giữ nguyên --</option>
            <option value="">-- Bỏ trống --</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3 text-start">
        <label class="form-label">Người duyệt lần 1</label>
        <select id="swal-bulk-manager" class="form-select">
            <option value="no_change">-- Giữ nguyên --</option>
            <option value="">-- Bỏ trống --</option>
            @foreach($managers as $manager)
                <option value="{{ $manager->id }}">{{ $manager->name }} ({{ $manager->employee_code }})</option>
            @endforeach
        </select>
    </div>
    <div class="text-start">
        <label class="form-label">Người duyệt lần 2</label>
        <select id="swal-bulk-hr" class="form-select">
            <option value="no_change">-- Giữ nguyên --</option>
            <option value="">-- Bỏ trống --</option>
            @foreach($hrs as $hr)
                <option value="{{ $hr->id }}">{{ $hr->name }} ({{ $hr->employee_code }})</option>
            @endforeach
        </select>
    </div>
</template>
@endsection

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    /* Fix daterangepicker z-index over sweetalert */
    .daterangepicker {
        z-index: 10000 !important;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
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
                const oldValue = this.dataset.oldValue;
                const url = `{{ route('backend.employees.change-status', ':id') }}`.replace(':id', employeeId);
                const selectElement = this;

                const sendStatusRequest = (payload) => {
                    // Update text color
                    selectElement.classList.remove('text-success', 'text-warning', 'text-danger', 'text-info');
                    if (status === 'active') selectElement.classList.add('text-success');
                    else if (status === 'inactive') selectElement.classList.add('text-warning');
                    else if (status === 'resigned') selectElement.classList.add('text-danger');
                    else if (status === 'probation') selectElement.classList.add('text-info');

                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            selectElement.dataset.oldValue = status;
                            if (payload.annual_leave_balance !== undefined) {
                                // Update the annual leave input next to it
                                const row = selectElement.closest('tr');
                                const leaveInput = row.querySelector('.annual-leave-input');
                                if (leaveInput) leaveInput.value = data.annual_leave_balance;
                            }
                            toastMixin.fire({
                                icon: 'success',
                                title: data.message
                            });
                        } else {
                            selectElement.value = oldValue;
                            toastMixin.fire({
                                icon: 'error',
                                title: data.message || 'Có lỗi xảy ra!'
                            });
                        }
                    })
                    .catch(error => {
                        selectElement.value = oldValue;
                        toastMixin.fire({
                            icon: 'error',
                            title: 'Lỗi máy chủ!'
                        });
                    });
                };

                if (status === 'active' && oldValue !== 'active') {
                    const today = moment().format('DD/MM/YYYY');
                    Swal.fire({
                        title: 'Chuyển sang Chính thức',
                        html: `
                            <div class="text-start mb-3">
                                <label class="form-label">Ngày làm việc chính thức</label>
                                <input type="text" id="swal-join-date" class="form-control" value="${today}">
                            </div>
                            <div class="text-start">
                                <label class="form-label">Phép năm khởi tạo</label>
                                <input type="number" id="swal-annual-leave" class="form-control" value="0" step="0.5" min="0">
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Lưu',
                        cancelButtonText: 'Hủy',
                        didOpen: () => {
                            $('#swal-join-date').daterangepicker({
                                singleDatePicker: true,
                                showDropdowns: true,
                                autoUpdateInput: true,
                                locale: {
                                    format: 'DD/MM/YYYY',
                                    daysOfWeek: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"],
                                    monthNames: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"],
                                    firstDay: 1
                                }
                            });
                        },
                        preConfirm: () => {
                            let joinDateStr = document.getElementById('swal-join-date').value;
                            // Convert DD/MM/YYYY to YYYY-MM-DD for backend
                            let joinDateParts = joinDateStr.split('/');
                            let backendDate = joinDateParts.length === 3 ? `${joinDateParts[2]}-${joinDateParts[1]}-${joinDateParts[0]}` : null;
                            return {
                                status: status,
                                join_date: backendDate,
                                annual_leave_balance: document.getElementById('swal-annual-leave').value
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            sendStatusRequest(result.value);
                        } else {
                            selectElement.value = oldValue;
                        }
                    });
                } else {
                    sendStatusRequest({ status: status });
                }
            });
        });

        document.querySelectorAll('.department-select').forEach(select => {
            select.addEventListener('change', function () {
                const employeeId = this.dataset.id;
                const departmentId = this.value;
                const url = `{{ route('backend.employees.change-department', ':id') }}`.replace(':id', employeeId);

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ department_id: departmentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastMixin.fire({
                            icon: 'success',
                            title: data.message
                        });
                        // Automatically reload the page after a brief delay because changing department 
                        // might invalidate the current options in the Manager 1/2 dropdowns.
                        setTimeout(() => window.location.reload(), 1000);
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

        document.querySelectorAll('.hr-select').forEach(select => {
            select.addEventListener('change', function () {
                const employeeId = this.dataset.id;
                const hrId = this.value;
                const url = `{{ route('backend.employees.change-hr', ':id') }}`.replace(':id', employeeId);
                const selectElement = this;

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ manager_l2_id: hrId })
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
                        if (!hrId) selectElement.value = "";
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

        document.querySelectorAll('.annual-leave-input').forEach(input => {
            input.addEventListener('change', function () {
                const employeeId = this.dataset.id;
                const value = this.value;
                const url = `{{ route('backend.employees.change-annual-leave', ':id') }}`.replace(':id', employeeId);

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ annual_leave_balance: value })
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

        // Bulk edit logic
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
                    title: 'Cập nhật hàng loạt',
                    html: document.getElementById('bulk-manager-template').innerHTML,
                    showCancelButton: true,
                    confirmButtonText: 'Cập nhật',
                    cancelButtonText: 'Hủy',
                    preConfirm: () => {
                        return {
                            department_id: document.getElementById('swal-bulk-department').value,
                            manager_id: document.getElementById('swal-bulk-manager').value,
                            manager_l2_id: document.getElementById('swal-bulk-hr').value
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let payload = { employee_ids: checkedIds };
                        if (result.value.department_id !== 'no_change') payload.department_id = result.value.department_id;
                        if (result.value.manager_id !== 'no_change') payload.manager_id = result.value.manager_id;
                        if (result.value.manager_l2_id !== 'no_change') payload.manager_l2_id = result.value.manager_l2_id;
                        
                        if (Object.keys(payload).length === 1) {
                            return; // nothing changed
                        }

                        fetch('{{ route("backend.employees.bulk-manager") }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
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

        document.querySelectorAll('.delete-employee-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Bạn có chắc chắn?',
                    text: 'Bạn có chắc chắn muốn xóa nhân viên này? Dữ liệu không thể khôi phục!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Đồng ý xóa',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });
</script>
@endpush
