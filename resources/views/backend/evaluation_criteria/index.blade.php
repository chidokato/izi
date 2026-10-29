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

            <!-- Tabs phân loại đối tượng -->
            <ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $currentType === 'all' ? 'active' : '' }}" href="{{ route('backend.evaluation-criteria.index', ['type' => 'all']) }}">
                        <i class="ri-apps-line me-1 align-bottom"></i> Tất cả đối tượng
                    </a>
                </li>
                @foreach($targetTypes as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $currentType === $key ? 'active' : '' }}" href="{{ route('backend.evaluation-criteria.index', ['type' => $key]) }}">
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Thứ tự</th>
                            <th>Tên tiêu chí</th>
                            <th>Đối tượng áp dụng</th>
                            <th>Điểm tối đa</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($criteria as $criterion)
                            <!-- Parent Row -->
                            <tr id="criterion-row-{{ $criterion->id }}" class="table-info">
                                <td class="criterion-order"><strong>{{ $criterion->order }}</strong></td>
                                <td class="criterion-name" style="white-space: normal;"><strong>{!! nl2br(e($criterion->name)) !!}</strong></td>
                                <td class="criterion-target-types">
                                    @if(empty($criterion->target_types) || in_array('all', $criterion->target_types))
                                        <span class="badge bg-secondary">Tất cả</span>
                                    @else
                                        @foreach($criterion->target_type_names as $k => $name)
                                            <span class="badge bg-primary me-1">{{ $name }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td></td>
                                <td class="criterion-status">
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
                                    <button type="button" class="btn btn-sm btn-soft-info btn-edit-criterion"
                                        data-bs-toggle="modal" data-bs-target="#editCriterionModal"
                                        data-id="{{ $criterion->id }}"
                                        data-name="{{ $criterion->name }}"
                                        data-parent-id=""
                                        data-max-score=""
                                        data-order="{{ $criterion->order }}"
                                        data-target-types="{{ json_encode($criterion->target_types ?? ['all']) }}"
                                        data-is-active="{{ $criterion->is_active ? 1 : 0 }}"
                                        data-is-parent="1">
                                        Sửa
                                    </button>
                                    <form action="{{ route('backend.evaluation-criteria.destroy', $criterion->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tiêu chí này (bao gồm cả các tiêu chí con)?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Children Rows -->
                            @foreach($criterion->children as $child)
                                <tr id="criterion-row-{{ $child->id }}">
                                    <td class="ps-4 criterion-order">{{ $child->order }}</td>
                                    <td class="ps-4 criterion-name" style="white-space: normal;">-- {!! nl2br(e($child->name)) !!}</td>
                                    <td class="criterion-target-types">
                                        @if(empty($child->target_types) || in_array('all', $child->target_types))
                                            <span class="badge bg-secondary">Tất cả</span>
                                        @else
                                            @foreach($child->target_type_names as $k => $name)
                                                <span class="badge bg-primary me-1">{{ $name }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="criterion-max-score">{{ $child->max_score !== null ? (float)$child->max_score : '' }}</td>
                                    <td class="criterion-status">
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
                                        <button type="button" class="btn btn-sm btn-soft-info btn-edit-criterion"
                                            data-bs-toggle="modal" data-bs-target="#editCriterionModal"
                                            data-id="{{ $child->id }}"
                                            data-name="{{ $child->name }}"
                                            data-parent-id="{{ $child->parent_id }}"
                                            data-max-score="{{ $child->max_score !== null ? (float)$child->max_score : '' }}"
                                            data-order="{{ $child->order }}"
                                            data-target-types="{{ json_encode($child->target_types ?? ['all']) }}"
                                            data-is-active="{{ $child->is_active ? 1 : 0 }}"
                                            data-is-parent="0">
                                            Sửa
                                        </button>
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
                                <td colspan="6" class="text-center text-muted py-4">Chưa có tiêu chí đánh giá nào cho mục này.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Sửa Tiêu Chí -->
    <div class="modal fade" id="editCriterionModal" tabindex="-1" aria-labelledby="editCriterionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCriterionModalLabel">Sửa tiêu chí đánh giá</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCriterionForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_criterion_id" name="id">
                    <input type="hidden" id="edit_original_parent_id" name="original_parent_id">
                    
                    <div class="modal-body">
                        <div id="modal_error_alert" class="alert alert-danger d-none"></div>

                        <div class="mb-3" id="parent_select_group">
                            <label for="edit_parent_id" class="form-label">Thuộc tiêu chí cha</label>
                            <select class="form-select" id="edit_parent_id" name="parent_id">
                                <option value="">-- Không có (Tiêu chí gốc / cha) --</option>
                                @foreach($allParents as $p)
                                    <option value="{{ $p['id'] }}" data-targets="{{ json_encode($p['target_types']) }}" data-target-names="{{ $p['target_type_names'] }}">{{ $p['name'] }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="parent_help_text">Để trống nếu là tiêu chí gốc.</small>
                        </div>

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Tên tiêu chí <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_name" name="name" rows="3" required placeholder="Nhập tên tiêu chí..." style="min-height: 80px; overflow-y: hidden; resize: vertical;"></textarea>
                        </div>

                        <!-- Đối tượng áp dụng (Cho cả cha và con, con bị giới hạn theo cha) -->
                        <div class="mb-3" id="target_types_group">
                            <label class="form-label d-block fw-semibold">
                                Đối tượng áp dụng
                                <span id="target_scope_hint" class="badge bg-soft-info text-primary fw-normal ms-2"></span>
                            </label>
                            <div class="d-flex flex-wrap gap-3 p-2 border rounded bg-light">
                                <div class="form-check" id="wrap_target_all">
                                    <input class="form-check-input target-type-check" type="checkbox" name="target_types[]" value="all" id="target_all">
                                    <label class="form-check-label fw-bold text-dark" for="target_all">Tất cả đối tượng</label>
                                </div>
                                @foreach($targetTypes as $tKey => $tLabel)
                                    <div class="form-check wrap-target-item" id="wrap_target_{{ $tKey }}">
                                        <input class="form-check-input target-type-check target-item-check" type="checkbox" name="target_types[]" value="{{ $tKey }}" id="target_{{ $tKey }}">
                                        <label class="form-check-label" for="target_{{ $tKey }}">{{ $tLabel }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted" id="target_types_help">Chọn đối tượng áp dụng.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3" id="max_score_group">
                                <label for="edit_max_score" class="form-label">Điểm tối đa</label>
                                <input type="number" class="form-control" id="edit_max_score" name="max_score" min="0" step="any" placeholder="VD: 10 hoặc 0.5">
                                <small class="text-muted" id="score_help_text">Bỏ trống nếu là tiêu chí cha.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="edit_order" class="form-label">Thứ tự hiển thị</label>
                                <input type="number" class="form-control" id="edit_order" name="order" value="0">
                            </div>
                        </div>

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">Hoạt động (Hiển thị trong phiếu đánh giá)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary" id="btn_save_criterion">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true" id="save_spinner"></span>
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editCriterionModal');
        const editForm = document.getElementById('editCriterionForm');
        const modalErrorAlert = document.getElementById('modal_error_alert');
        const saveBtn = document.getElementById('btn_save_criterion');
        const spinner = document.getElementById('save_spinner');
        const nameTextarea = document.getElementById('edit_name');
        const parentSelect = document.getElementById('edit_parent_id');
        const maxScoreInput = document.getElementById('edit_max_score');
        const targetAllCheck = document.getElementById('target_all');
        const wrapTargetAll = document.getElementById('wrap_target_all');
        const itemChecks = document.querySelectorAll('.target-item-check');
        const targetHint = document.getElementById('target_scope_hint');
        const targetHelp = document.getElementById('target_types_help');

        function autoResizeTextarea(textarea) {
            if (!textarea) return;
            textarea.style.height = 'auto';
            const minHeight = 80;
            textarea.style.height = (textarea.scrollHeight > minHeight ? textarea.scrollHeight : minHeight) + 'px';
        }

        if (nameTextarea) {
            nameTextarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        }

        // Logic checkbox
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

        // Hàm cập nhật phạm vi checkbox đối tượng theo tiêu chí cha
        function updateTargetScope(isParent, keepValues = false) {
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
            } else {
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
                    // Cha có danh sách cụ thể: Ẩn "Tất cả", chỉ cho phép chọn các đối tượng cha có
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

                    // Nếu không có ô nào được chọn thì mặc định chọn ô đầu tiên cho phép
                    if (!keepValues) {
                        const anyChecked = Array.from(itemChecks).some(c => c.checked && !c.disabled);
                        if (!anyChecked) {
                            const firstAllowed = Array.from(itemChecks).find(c => !c.disabled);
                            if (firstAllowed) firstAllowed.checked = true;
                        }
                    }
                }
            }
        }

        parentSelect.addEventListener('change', function() {
            const isParentNow = !this.value;
            updateTargetScope(isParentNow, false);
        });

        editModal.addEventListener('shown.bs.modal', function() {
            if (nameTextarea) {
                autoResizeTextarea(nameTextarea);
            }
        });

        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            modalErrorAlert.classList.add('d-none');
            modalErrorAlert.innerHTML = '';

            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name') || '';
            const parentId = button.getAttribute('data-parent-id') || '';
            const maxScore = button.getAttribute('data-max-score');
            const order = button.getAttribute('data-order') || 0;
            const isActive = button.getAttribute('data-is-active') === '1';
            const isParent = button.getAttribute('data-is-parent') === '1';

            let targetTypes = [];
            try {
                targetTypes = JSON.parse(button.getAttribute('data-target-types') || '["all"]');
            } catch (e) {
                targetTypes = ['all'];
            }

            document.getElementById('edit_criterion_id').value = id;
            document.getElementById('edit_original_parent_id').value = parentId;
            document.getElementById('edit_name').value = name;
            autoResizeTextarea(nameTextarea);
            document.getElementById('edit_order').value = order;
            document.getElementById('edit_is_active').checked = isActive;

            // Reset options visibility trong dropdown cha
            Array.from(parentSelect.options).forEach(opt => {
                opt.disabled = false;
                if (opt.value == id) {
                    opt.disabled = true;
                }
            });

            if (isParent) {
                parentSelect.value = '';
                parentSelect.disabled = true;
                document.getElementById('parent_help_text').textContent = 'Đây là tiêu chí gốc/cha.';
                maxScoreInput.value = '';
                maxScoreInput.disabled = true;
                document.getElementById('score_help_text').textContent = 'Tiêu chí cha không nhập điểm (tính theo con).';

                updateTargetScope(true, true);

                if (!Array.isArray(targetTypes) || targetTypes.length === 0 || targetTypes.includes('all')) {
                    targetAllCheck.checked = true;
                    itemChecks.forEach(cb => cb.checked = false);
                } else {
                    targetAllCheck.checked = false;
                    itemChecks.forEach(cb => {
                        cb.checked = targetTypes.includes(cb.value);
                    });
                }
            } else {
                parentSelect.disabled = false;
                parentSelect.value = parentId;
                document.getElementById('parent_help_text').textContent = 'Chọn nhóm tiêu chí cha.';
                maxScoreInput.disabled = false;
                maxScoreInput.value = (maxScore !== null && maxScore !== '') ? maxScore : '';
                document.getElementById('score_help_text').textContent = 'Điểm tối đa của tiêu chí này.';

                updateTargetScope(false, true);

                if (Array.isArray(targetTypes) && !targetTypes.includes('all')) {
                    targetAllCheck.checked = false;
                    itemChecks.forEach(cb => {
                        cb.checked = !cb.disabled && targetTypes.includes(cb.value);
                    });
                } else {
                    if (!targetAllCheck.disabled) {
                        targetAllCheck.checked = true;
                        itemChecks.forEach(cb => cb.checked = false);
                    } else {
                        // Nếu cha không có all, check các đối tượng cha có
                        itemChecks.forEach(cb => {
                            cb.checked = !cb.disabled;
                        });
                    }
                }
            }
        });

        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const id = document.getElementById('edit_criterion_id').value;
            modalErrorAlert.classList.add('d-none');
            modalErrorAlert.innerHTML = '';
            saveBtn.disabled = true;
            spinner.classList.remove('d-none');

            const formData = new FormData(this);
            if (parentSelect.disabled || !parentSelect.value) {
                formData.set('parent_id', '');
            }
            if (!document.getElementById('edit_is_active').checked) {
                formData.delete('is_active');
            }

            const url = `{{ route('backend.evaluation-criteria.update', ':id') }}`.replace(':id', id);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async response => {
                let data = null;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    try {
                        data = await response.json();
                    } catch (e) {
                        data = null;
                    }
                }
                if (!response.ok || !data) {
                    throw (data || { message: `Lỗi máy chủ (${response.status})` });
                }
                return data;
            })
            .then(data => {
                saveBtn.disabled = false;
                spinner.classList.add('d-none');

                const bsModal = bootstrap.Modal.getInstance(editModal);
                if (bsModal) {
                    bsModal.hide();
                }

                const row = document.getElementById(`criterion-row-${id}`);
                const isParent = row ? row.classList.contains('table-info') : false;
                const origParentId = document.getElementById('edit_original_parent_id').value;
                const newParentId = document.getElementById('edit_parent_id').value;

                // Nếu là tiêu chí cha hoặc thay đổi cha: reload để cây tiêu chí luôn đồng bộ
                if (isParent || origParentId !== newParentId) {
                    window.location.reload();
                    return;
                }

                // Nếu đang ở Tab lọc cụ thể và tiêu chí con không còn thuộc tab đó nữa -> reload để loại khỏi view
                const currentType = '{{ $currentType }}';
                if (currentType !== 'all') {
                    const targets = data.target_types || ['all'];
                    if (!targets.includes('all') && !targets.includes(currentType)) {
                        window.location.reload();
                        return;
                    }
                }

                // Cập nhật DOM trực tiếp cho tiêu chí con
                if (row) {
                    const orderCell = row.querySelector('.criterion-order');
                    if (orderCell) {
                        orderCell.textContent = data.criterion.order ?? 0;
                    }

                    const nameCell = row.querySelector('.criterion-name');
                    if (nameCell) {
                        const formattedName = (data.criterion.name || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/\r\n|\r|\n/g, '<br>');
                        nameCell.innerHTML = `-- ${formattedName}`;
                    }

                    const targetCell = row.querySelector('.criterion-target-types');
                    if (targetCell) {
                        let badgesHtml = '';
                        if (!data.target_types || data.target_types.includes('all') || data.target_types.length === 0) {
                            badgesHtml = '<span class="badge bg-secondary">Tất cả</span>';
                        } else {
                            for (const [key, name] of Object.entries(data.target_type_names || {})) {
                                badgesHtml += `<span class="badge bg-primary me-1">${name}</span>`;
                            }
                        }
                        targetCell.innerHTML = badgesHtml;
                    }

                    const scoreCell = row.querySelector('.criterion-max-score');
                    const parsedScore = (data.criterion.max_score !== null && data.criterion.max_score !== undefined && data.criterion.max_score !== '') ? parseFloat(data.criterion.max_score) : '';
                    if (scoreCell) {
                        scoreCell.textContent = parsedScore;
                    }

                    const statusCell = row.querySelector('.criterion-status');
                    if (statusCell) {
                        if (data.criterion.is_active) {
                            statusCell.innerHTML = `<span class="badge bg-success">Hoạt động</span>`;
                        } else {
                            statusCell.innerHTML = `<span class="badge bg-danger">Khóa</span>`;
                        }
                    }

                    const editBtn = row.querySelector('.btn-edit-criterion');
                    if (editBtn) {
                        editBtn.setAttribute('data-name', data.criterion.name);
                        editBtn.setAttribute('data-max-score', parsedScore);
                        editBtn.setAttribute('data-order', data.criterion.order ?? 0);
                        editBtn.setAttribute('data-is-active', data.criterion.is_active ? '1' : '0');
                        editBtn.setAttribute('data-parent-id', data.criterion.parent_id ?? '');
                        editBtn.setAttribute('data-target-types', JSON.stringify(data.target_types || ['all']));
                    }
                }

                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: data.message || 'Cập nhật tiêu chí thành công!'
                    });
                }
            })
            .catch(error => {
                saveBtn.disabled = false;
                spinner.classList.add('d-none');
                let errorMsg = 'Có lỗi xảy ra, vui lòng thử lại.';
                if (error && error.errors) {
                    errorMsg = Object.values(error.errors).flat().join('<br>');
                } else if (error && error.message) {
                    errorMsg = error.message;
                }
                modalErrorAlert.innerHTML = errorMsg;
                modalErrorAlert.classList.remove('d-none');
            });
        });
    });
</script>
@endpush
