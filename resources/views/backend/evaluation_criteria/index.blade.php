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
                                <td>
                                    <input type="text" class="form-control fw-bold quick-edit-name" data-id="{{ $criterion->id }}" value="{{ $criterion->name }}" style="width: 100%; min-width: 200px; border-color: transparent; background: transparent;">
                                </td>
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
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2 text-muted">--</span>
                                            <input type="text" class="form-control quick-edit-name" data-id="{{ $child->id }}" value="{{ $child->name }}" style="width: 100%; min-width: 200px; border-color: transparent; background: transparent;">
                                        </div>
                                    </td>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.quick-edit-name');
        
        inputs.forEach(input => {
            let originalValue = input.value;
            
            // Thêm hiệu ứng focus để nhận biết đang sửa
            input.addEventListener('focus', function() {
                this.style.borderColor = '#878a99';
                this.style.background = '#fff';
            });

            input.addEventListener('blur', function() {
                this.style.borderColor = 'transparent';
                this.style.background = 'transparent';
                
                const newValue = this.value.trim();
                const id = this.getAttribute('data-id');

                if (newValue !== originalValue && newValue !== '') {
                    // Hiển thị loading nhẹ
                    this.style.opacity = '0.5';

                    fetch(`/admin/evaluation-criteria/${id}/quick-update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ name: newValue })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.style.opacity = '1';
                        if (data.success) {
                            originalValue = newValue;
                            // Optional: Hiển thị toast nhẹ góc màn hình
                            if (typeof Toastify !== 'undefined') {
                                Toastify({
                                    text: "Đã lưu tên tiêu chí",
                                    duration: 2000,
                                    gravity: "top",
                                    position: "right",
                                    backgroundColor: "#4fC6E1",
                                }).showToast();
                            }
                        } else {
                            this.value = originalValue;
                            alert('Có lỗi xảy ra khi lưu.');
                        }
                    })
                    .catch(error => {
                        this.style.opacity = '1';
                        this.value = originalValue;
                        console.error('Error:', error);
                        alert('Lỗi kết nối.');
                    });
                } else if (newValue === '') {
                    this.value = originalValue;
                }
            });

            // Lắng nghe phím Enter
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.blur();
                }
            });
        });
    });
</script>
@endpush
