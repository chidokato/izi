@extends('backend.layouts.app')

@section('title', 'Cấu hình xếp loại')
@section('page_title', 'Xếp loại đánh giá')
@section('breadcrumb', 'Cấu hình xếp loại')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Danh sách xếp loại</h4>
            <a href="{{ route('backend.evaluation-grades.create') }}" class="btn btn-primary">Thêm xếp loại mới</a>
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
                            <th>Tên xếp loại</th>
                            <th>Từ điểm (Min)</th>
                            <th>Đến điểm (Max)</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grades as $grade)
                            <tr>
                                <td><span class="badge bg-primary fs-14">{{ $grade->name }}</span></td>
                                <td>{{ $grade->min_score }}</td>
                                <td>{{ $grade->max_score }}</td>
                                <td class="text-end">
                                    <a href="{{ route('backend.evaluation-grades.edit', $grade->id) }}" class="btn btn-sm btn-soft-info">Sửa</a>
                                    <form action="{{ route('backend.evaluation-grades.destroy', $grade->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa xếp loại này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Chưa có cấu hình xếp loại nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
