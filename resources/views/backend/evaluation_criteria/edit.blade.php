@extends('backend.layouts.app')

@section('title', 'Cập nhật tiêu chí đánh giá')
@section('page_title', 'Sửa tiêu chí')
@section('breadcrumb', 'Sửa tiêu chí đánh giá')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Cập nhật tiêu chí đánh giá</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('backend.evaluation-criteria.update', $criterion->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="parent_id" class="form-label">Thuộc tiêu chí cha (để trống nếu đây là tiêu chí gốc / cha)</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                            <option value="">-- Không có (Tiêu chí gốc / cha) --</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" data-targets="{{ json_encode($parent->target_types ?? ['all']) }}" data-target-names="{{ implode(', ', $parent->target_type_names) }}" {{ old('parent_id', $criterion->parent_id) == $parent->id ? 'selected' : '' }}>
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
                        <textarea class="form-control @error('name') is-invalid @enderror" id="name" name="name" rows="3" required style="min-height: 80px; resize: vertical;" oninput="this.style.height='auto';this.style.height=(this.scrollHeight>80?this.scrollHeight:80)+'px'">{{ old('name', $criterion->name) }}</textarea>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @php
                        $currentTargets = $criterion->target_types ?? ['all'];
                        $isAll = empty($currentTargets) || in_array('all', $currentTargets);
                    @endphp

                    <!-- Đối tượng áp dụng (Cho cả cha và con, con chỉ chọn được các đối tượng có trong cha) -->
                    <div class="col-md-12" id="target_types_group">
                        <label class="form-label d-block fw-semibold">
                            Đối tượng áp dụng
                            <span id="target_scope_hint" class="badge bg-soft-info text-primary fw-normal ms-2"></span>
                        </label>
                        <div class="d-flex flex-wrap gap-3 p-2 border rounded bg-light">
                            <div class="form-check" id="wrap_target_all">
                                <input class="form-check-input target-type-check" type="checkbox" name="target_types[]" value="all" id="target_all" {{ $isAll ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="target_all">Tất cả đối tượng</label>
                            </div>
                            @foreach($targetTypes as $tKey => $tLabel)
                                <div class="form-check wrap-target-item" id="wrap_target_{{ $tKey }}">
                                    <input class="form-check-input target-type-check target-item-check" type="checkbox" name="target_types[]" value="{{ $tKey }}" id="target_{{ $tKey }}" {{ (!$isAll && in_array($tKey, $currentTargets)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="target_{{ $tKey }}">{{ $tLabel }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted" id="target_types_help">Chọn đối tượng áp dụng.</small>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="max_score" class="form-label">Điểm tối đa <small class="text-muted">(Bỏ trống nếu là tiêu chí cha)</small></label>
                        <input type="number" class="form-control @error('max_score') is-invalid @enderror" id="max_score" name="max_score" value="{{ old('max_score', $criterion->max_score) }}" min="0" {{ $criterion->parent_id ? '' : 'disabled' }}>
                        @error('max_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="order" class="form-label">Thứ tự hiển thị</label>
                        <input type="number" class="form-control @error('order') is-invalid @enderror" id="order" name="order" value="{{ old('order', $criterion->order) }}">
                        @error('order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $criterion->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Hoạt động (Hiển thị trong phiếu đánh giá)</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <a href="{{ route('backend.evaluation-criteria.index') }}" class="btn btn-light">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const parentSelect = document.getElementById('parent_id');
        const maxScoreInput = document.getElementById('max_score');
        const targetAllCheck = document.getElementById('target_all');
        const wrapTargetAll = document.getElementById('wrap_target_all');
        const itemChecks = document.querySelectorAll('.target-item-check');
        const targetHint = document.getElementById('target_scope_hint');
        const targetHelp = document.getElementById('target_types_help');

        function updateScope() {
            const isParent = !parentSelect.value;
            if (isParent) {
                targetHint.textContent = 'Tiêu chí cha';
                targetHint.className = 'badge bg-soft-primary text-primary fw-normal ms-2';
                targetHelp.textContent = 'Chọn đối tượng áp dụng cho tiêu chí cha. Các tiêu chí con bên trong sẽ chỉ được chọn trong các đối tượng này.';
                wrapTargetAll.classList.remove('d-none');
                targetAllCheck.disabled = false;

                itemChecks.forEach(cb => {
                    const wrap = document.getElementById(`wrap_target_${cb.value}`);
                    if (wrap) wrap.classList.remove('d-none');
                    cb.disabled = false;
                });

                maxScoreInput.value = '';
                maxScoreInput.disabled = true;
            } else {
                maxScoreInput.disabled = false;
                const selectedOpt = parentSelect.options[parentSelect.selectedIndex];
                let parentTargets = ['all'];
                let parentTargetNames = 'Tất cả đối tượng';

                if (selectedOpt && selectedOpt.dataset.targets) {
                    try {
                        parentTargets = JSON.parse(selectedOpt.dataset.targets);
                    } catch (e) {
                        parentTargets = ['all'];
                    }
                    parentTargetNames = selectedOpt.dataset.targetNames || 'Tất cả đối tượng';
                }

                targetHint.textContent = 'Giới hạn theo tiêu chí cha';
                targetHint.className = 'badge bg-soft-warning text-warning fw-normal ms-2';
                targetHelp.innerHTML = `Tiêu chí con chỉ được chọn trong các đối tượng có trong cha: <strong class="text-primary">${parentTargetNames}</strong>`;

                const parentHasAll = !parentTargets || parentTargets.length === 0 || parentTargets.includes('all');

                if (parentHasAll) {
                    wrapTargetAll.classList.remove('d-none');
                    targetAllCheck.disabled = false;
                    itemChecks.forEach(cb => {
                        const wrap = document.getElementById(`wrap_target_${cb.value}`);
                        if (wrap) wrap.classList.remove('d-none');
                        cb.disabled = false;
                    });
                } else {
                    wrapTargetAll.classList.add('d-none');
                    targetAllCheck.checked = false;
                    targetAllCheck.disabled = true;

                    itemChecks.forEach(cb => {
                        const wrap = document.getElementById(`wrap_target_${cb.value}`);
                        const isAllowed = parentTargets.includes(cb.value);
                        if (isAllowed) {
                            if (wrap) wrap.classList.remove('d-none');
                            cb.disabled = false;
                        } else {
                            cb.checked = false;
                            cb.disabled = true;
                            if (wrap) wrap.classList.add('d-none');
                        }
                    });
                }
            }
        }

        parentSelect.addEventListener('change', updateScope);
        updateScope();

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
                    const anyChecked = Array.from(itemChecks).some(c => c.checked && !c.disabled);
                    const isAllVisible = !wrapTargetAll.classList.contains('d-none');
                    if (!anyChecked && isAllVisible) {
                        targetAllCheck.checked = true;
                    }
                }
            });
        });
    });
</script>
@endpush
