@extends('backend.layouts.app')

@section('title', 'Thêm tiêu chí đánh giá')
@section('page_title', 'Thêm tiêu chí')
@section('breadcrumb', 'Thêm tiêu chí đánh giá')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Thêm tiêu chí đánh giá mới</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('backend.evaluation-criteria.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="parent_id" class="form-label">Thuộc tiêu chí cha (để trống nếu đây là tiêu chí gốc)</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                            <option value="">-- Không có (Tiêu chí gốc) --</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="name" class="form-label">Tên tiêu chí <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('name') is-invalid @enderror" id="name" name="name" rows="3" required style="min-height: 80px; resize: vertical;" oninput="this.style.height='auto';this.style.height=(this.scrollHeight>80?this.scrollHeight:80)+'px'">{{ old('name') }}</textarea>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="max_score" class="form-label">Điểm tối đa <small class="text-muted">(Bỏ trống nếu là tiêu chí cha)</small></label>
                        <input type="number" class="form-control @error('max_score') is-invalid @enderror" id="max_score" name="max_score" value="{{ old('max_score') }}" min="0">
                        @error('max_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="order" class="form-label">Thứ tự hiển thị</label>
                        <input type="number" class="form-control @error('order') is-invalid @enderror" id="order" name="order" value="{{ old('order', 0) }}">
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label d-block fw-semibold">Đối tượng áp dụng</label>
                        <div class="d-flex flex-wrap gap-3 p-2 border rounded bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="target_types[]" value="all" id="target_all" checked>
                                <label class="form-check-label fw-bold" for="target_all">Tất cả đối tượng</label>
                            </div>
                            @foreach($targetTypes as $tKey => $tLabel)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="target_types[]" value="{{ $tKey }}" id="target_{{ $tKey }}">
                                    <label class="form-check-label" for="target_{{ $tKey }}">{{ $tLabel }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Chọn "Tất cả" nếu tiêu chí áp dụng chung, hoặc tích chọn từng đối tượng cụ thể.</small>
                    </div>

                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Hoạt động (Hiển thị trong phiếu đánh giá)</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Lưu tiêu chí</button>
                    <a href="{{ route('backend.evaluation-criteria.index') }}" class="btn btn-light">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const targetAllCheck = document.getElementById('target_all');
        const itemChecks = document.querySelectorAll('input[name="target_types[]"]:not(#target_all)');

        if (targetAllCheck) {
            targetAllCheck.addEventListener('change', function() {
                if (this.checked) {
                    itemChecks.forEach(cb => cb.checked = false);
                }
            });
        }

        itemChecks.forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    targetAllCheck.checked = false;
                } else {
                    const anyChecked = Array.from(itemChecks).some(c => c.checked);
                    if (!anyChecked) {
                        targetAllCheck.checked = true;
                    }
                }
            });
        });
    });
</script>
@endpush