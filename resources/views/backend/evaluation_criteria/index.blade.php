@extends('backend.layouts.app')

@section('title', 'Quản lý tiêu chí đánh giá')
@section('page_title', 'Tiêu chí đánh giá')
@section('breadcrumb', 'Tiêu chí đánh giá')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Danh sách tiêu chí</h4>
            <a href="{{ route('backend.evaluation-criteria.create') }}" class="btn btn-primary">Thêm tiêu chí mới</a>
        </div>
        <div class="card-body">
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
                            <th>Thứ tự</th>
                            <th>Tên tiêu chí</th>
                            <th>Điểm tối đa</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($criteria as $criterion)
                            <!-- Parent Row -->
                            <tr class="table-info">
                                <td><strong>{{ $criterion->order }}</strong></td>
                                <td><strong>{{ $criterion->name }}</strong></td>
                                <td>
                                    <strong>{{ $criterion->children->sum('max_score') ?: '-' }}</strong>
                                    <small class="text-muted">(Tổng con)</small>
                                </td>
                                <td>
                                    @if ($criterion->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-danger">Khóa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('backend.evaluation-criteria.duplicate', $criterion->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-soft-warning">Nhân bản</button>
                                    </form>
                                    <a href="{{ route('backend.evaluation-criteria.edit', $criterion->id) }}" class="btn btn-sm btn-soft-info">Sửa</a>
                                    <form action="{{ route('backend.evaluation-criteria.destroy', $criterion->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tiêu chí này (bao gồm cả các tiêu chí con)?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Children Rows -->
                            @foreach($criterion->children as $child)
                                <tr>
                                    <td class="ps-4">{{ $child->order }}</td>
                                    <td class="ps-4">-- {{ $child->name }}</td>
                                    <td>{{ $child->max_score }}</td>
                                    <td>
                                        @if ($child->is_active)
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-danger">Khóa</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('backend.evaluation-criteria.duplicate', $child->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-soft-warning">Nhân bản</button>
                                        </form>
                                        <a href="{{ route('backend.evaluation-criteria.edit', $child->id) }}" class="btn btn-sm btn-soft-info">Sửa</a>
                                        <form action="{{ route('backend.evaluation-criteria.destroy', $child->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tiêu chí này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chưa có tiêu chí đánh giá nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
