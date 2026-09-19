@extends('backend.layouts.app')
@section('title', 'Phòng ban')
@section('page_title', 'Danh sách phòng ban')
@section('breadcrumb', 'Phòng ban')
@section('content')
@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <h5 class="card-title mb-0">Phòng ban <span class="badge bg-primary ms-1">{{ $departments->total() }}</span></h5>
        <a class="btn btn-soft-primary" href="{{ route('backend.attendance.index') }}">Nhập từ file chấm công</a>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('backend.departments.index') }}" class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="department-q" class="form-label">Mã hoặc tên phòng ban</label>
                <input id="department-q" class="form-control" name="q" value="{{ request('q') }}" placeholder="Nhập mã hoặc tên phòng ban">
            </div>
            <div class="col-md-3">
                <label for="department-status" class="form-label">Trạng thái</label>
                <select id="department-status" name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Ngừng hoạt động</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary">Lọc</button>
                <a class="btn btn-light" href="{{ route('backend.departments.index') }}">Bỏ lọc</a>
            </div>
        </form>
        <p class="text-muted">Số nhân viên gồm tất cả trạng thái, chỉ tính người thuộc trực tiếp phòng ban. Bấm vào số lượng để xem danh sách.</p>
        <div class="table-responsive">
            <table class="table table-striped table-nowrap align-middle">
                <thead class="table-light"><tr><th>STT</th><th>Mã phòng ban</th><th>Tên phòng ban</th><th>Phòng ban cấp trên</th><th>Số nhân viên</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr>
                        <td>{{ $departments->firstItem() + $loop->index }}</td>
                        <td>{{ $department->code ?? '—' }}</td>
                        <td>{{ $department->name }}</td>
                        <td>{{ $department->parent_name ?? '—' }}</td>
                        <td><a href="{{ route('backend.employees.index', ['department_id' => $department->id]) }}" aria-label="Xem nhân viên phòng {{ $department->name }}">{{ $department->employee_count }}</a></td>
                        <td><span class="badge {{ $department->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $department->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không có phòng ban phù hợp. Phòng ban mới có thể được tạo khi nhập file chấm công.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $departments->links() }}
    </div>
</div>
@endsection