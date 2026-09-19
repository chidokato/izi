@extends('backend.layouts.app')
@section('title', $schedule ? 'Chỉnh sửa lịch làm việc' : 'Thêm lịch làm việc')
@section('page_title', $schedule ? 'Chỉnh sửa lịch làm việc' : 'Thêm lịch làm việc')
@section('breadcrumb', 'Giờ làm việc')
@section('content')
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="post" action="{{ $schedule ? route('backend.schedules.update',$schedule->id) : route('backend.schedules.store') }}">
@csrf
@if($schedule) @method('PUT') @endif
<div class="card"><div class="card-body"><div class="row g-3">
    <div class="col-md-3"><label for="schedule-code" class="form-label">Mã lịch <span class="text-danger">*</span></label><input id="schedule-code" name="code" class="form-control" value="{{ old('code',$schedule->code ?? '') }}" maxlength="50" placeholder="Ví dụ: HC01" required></div>
    <div class="col-md-6"><label for="schedule-name" class="form-label">Tên lịch <span class="text-danger">*</span></label><input id="schedule-name" name="name" class="form-control" value="{{ old('name',$schedule->name ?? '') }}" maxlength="255" placeholder="Ví dụ: Hành chính văn phòng" required></div>
    <div class="col-md-3"><label for="schedule-status" class="form-label">Trạng thái</label><select id="schedule-status" name="status" class="form-select"><option value="active" @selected(old('status',$schedule->status ?? 'active')==='active')>Đang sử dụng</option><option value="inactive" @selected(old('status',$schedule->status ?? 'active')==='inactive')>Ngừng sử dụng</option></select></div>
    <div class="col-12"><label for="schedule-description" class="form-label">Ghi chú</label><textarea id="schedule-description" name="description" class="form-control" rows="2" maxlength="2000">{{ old('description',$schedule->description ?? '') }}</textarea></div>
</div></div></div>
<div class="card"><div class="card-body">
<h5>Khung giờ theo ngày trong tuần</h5>
<p class="text-muted">Nhập giờ theo định dạng 24 giờ HH:mm (ví dụ 08:00, 17:30). Bật “Hành chính” ở ngày làm việc và nhập giờ bắt đầu/kết thúc. Giờ hành chính nằm trong cùng ngày. Có thể để trống giờ nghỉ hoặc OT. Nếu OT kết thúc vào ngày hôm sau, chọn “+1 ngày”.</p>
<div class="d-flex flex-wrap align-items-end gap-2 mb-2">
    <div><label for="copy-schedule-day" class="form-label">Ngày mẫu</label><select id="copy-schedule-day" class="form-select">@foreach($days as $day=>$label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></div>
    <button type="button" id="apply-schedule-all" class="btn btn-soft-primary">Áp dụng cho tất cả các ngày</button>
</div>
<p class="text-muted small">Sao chép giờ hành chính, giờ nghỉ, OT và các ô chọn từ ngày mẫu sang cả 7 ngày, kể cả ô trống. Bạn có thể sửa riêng từng ngày trước khi lưu.</p>
<div id="schedule-copy-status" class="text-success mb-3" role="status" aria-live="polite"></div>
<div class="table-responsive"><table class="table table-bordered align-middle" style="min-width:1120px"><thead class="table-light"><tr><th rowspan="2">Ngày</th><th rowspan="2">Hành chính</th><th colspan="2" class="text-center">Giờ hành chính</th><th colspan="2" class="text-center">Giờ nghỉ</th><th colspan="3" class="text-center">Khung OT</th></tr><tr><th>Bắt đầu</th><th>Kết thúc</th><th>Bắt đầu</th><th>Kết thúc</th><th>Bắt đầu</th><th>Kết thúc</th><th>+1 ngày</th></tr></thead><tbody>
@foreach($days as $day=>$label)
@php($row = old('rules.'.$day, $rules[$day] ?? []))
<tr data-schedule-day="{{ $day }}">
    <th class="text-nowrap">{{ $label }}</th>
    <td class="text-center"><input type="hidden" name="rules[{{ $day }}][is_working_day]" value="0"><input type="checkbox" class="form-check-input" data-rule-field="is_working_day" aria-label="Hành chính {{ $label }}" name="rules[{{ $day }}][is_working_day]" value="1" @checked($row['is_working_day'] ?? false)></td>
    @foreach(['start_time'=>'Bắt đầu hành chính','end_time'=>'Kết thúc hành chính','break_start'=>'Bắt đầu nghỉ','break_end'=>'Kết thúc nghỉ','ot_start'=>'Bắt đầu OT','ot_end'=>'Kết thúc OT'] as $field=>$title)
    <td><input type="text" class="form-control" data-rule-field="{{ $field }}" placeholder="HH:mm" pattern="([01][0-9]|2[0-3]):[0-5][0-9]" maxlength="5" title="Nhập giờ 24 giờ HH:mm, từ 00:00 đến 23:59" autocomplete="off" aria-label="{{ $title }} {{ $label }}" name="rules[{{ $day }}][{{ $field }}]" value="{{ $row[$field] ?? '' }}" style="min-width:115px"></td>
    @endforeach
    <td class="text-center"><input type="hidden" name="rules[{{ $day }}][ot_next_day]" value="0"><input type="checkbox" class="form-check-input" data-rule-field="ot_next_day" aria-label="OT kết thúc ngày hôm sau {{ $label }}" name="rules[{{ $day }}][ot_next_day]" value="1" @checked($row['ot_next_day'] ?? false)></td>
</tr>
@endforeach
</tbody></table></div>
<p class="text-muted mt-3">Thời gian hành chính được tính từ khung giờ và trừ giờ nghỉ. Khung OT không được trùng giờ hành chính. Lưu cấu hình không thay đổi dữ liệu chấm công đã nhập.</p>
<button class="btn btn-primary">Lưu lịch làm việc</button> <a class="btn btn-light" href="{{ route('backend.schedules.index') }}">Quay lại</a>
</div></div></form>
@endsection
@push('scripts')
<script>
(() => {
    const button = document.getElementById('apply-schedule-all');
    const selector = document.getElementById('copy-schedule-day');
    const status = document.getElementById('schedule-copy-status');
    const rows = Array.from(document.querySelectorAll('[data-schedule-day]'));
    button.addEventListener('click', () => {
        const source = rows.find(row => row.dataset.scheduleDay === selector.value);
        if (!source) return;
        const fields = Array.from(source.querySelectorAll('[data-rule-field]'));
        for (const field of fields) {
            if (!field.reportValidity()) return;
        }
        rows.forEach(row => {
            if (row === source) return;
            fields.forEach(field => {
                const target = row.querySelector('[data-rule-field="' + field.dataset.ruleField + '"]');
                if (field.type === 'checkbox') target.checked = field.checked;
                else target.value = field.value;
                target.dispatchEvent(new Event('input', { bubbles: true }));
                target.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        status.textContent = 'Đã áp dụng từ ' + selector.options[selector.selectedIndex].text + ' cho cả 7 ngày. Bấm “Lưu lịch làm việc” để lưu thay đổi.';
    });
})();
</script>
@endpush