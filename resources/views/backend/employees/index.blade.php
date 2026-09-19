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
            <div class="col-md-4">
                <label for="employee-q" class="form-label">Mã hoặc tên nhân viên</label>
                <input id="employee-q" class="form-control" name="q" value="{{ request('q') }}" placeholder="Nhập mã hoặc họ tên">
            </div>
            <div class="col-md-3">
                <label for="employee-department" class="form-label">Phòng ban</label>
                <select id="employee-department" name="department_id" class="form-select">
                    <option value="">Tất cả phòng ban</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string)request('department_id') === (string)$department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="employee-status" class="form-label">Trạng thái</label>
                <select id="employee-status" name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(['active'=>'Đang làm việc','inactive'=>'Ngừng hoạt động','resigned'=>'Đã nghỉ việc'] as $value=>$label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary">Lọc</button>
                <a class="btn btn-light" href="{{ route('backend.employees.index') }}">Bỏ lọc</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-nowrap align-middle">
                <thead class="table-light"><tr><th>STT</th><th>Mã nhân viên</th><th>Họ tên</th><th>Phòng ban</th><th>Trạng thái</th><th>Chấm công</th></tr></thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employees->firstItem() + $loop->index }}</td>
                        <td>{{ $employee->employee_code }}</td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->department_name ?? 'Chưa có phòng ban' }}</td>
                        <td><span class="badge {{ $employee->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ['active'=>'Đang làm việc','inactive'=>'Ngừng hoạt động','resigned'=>'Đã nghỉ việc'][$employee->status] ?? $employee->status }}</span></td>
                        <td><a class="btn btn-sm btn-soft-primary" href="{{ route('backend.calendar.index', ['employee_id' => $employee->id]) }}">Xem lịch</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không có nhân viên phù hợp. Bạn có thể tạo nhân viên khi nhập file chấm công.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </div>
</div>
@endsection