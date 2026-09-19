@extends('backend.layouts.app')
@section('title', 'Giờ làm việc')
@section('page_title', 'Quản lý giờ làm việc')
@section('breadcrumb', 'Giờ làm việc')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h5 class="mb-0">Lịch làm việc <span class="badge bg-primary">{{ $schedules->total() }}</span></h5><a class="btn btn-primary" href="{{ route('backend.schedules.create') }}">Thêm lịch làm việc</a></div>
    <p class="text-muted">Quản lý khung giờ hành chính, giờ nghỉ và OT theo tuần. Đây là cấu hình thời gian; chưa tự gán cho nhân viên hoặc quy đổi giờ OT thực tế.</p>
    <form method="get" class="row g-2"><div class="col-md-5"><input aria-label="Mã hoặc tên lịch" name="q" class="form-control" placeholder="Tìm theo mã hoặc tên lịch" value="{{ request('q') }}"></div><div class="col-md-4"><select aria-label="Trạng thái" name="status" class="form-select"><option value="">Tất cả trạng thái</option><option value="active" @selected(request('status')==='active')>Đang sử dụng</option><option value="inactive" @selected(request('status')==='inactive')>Ngừng sử dụng</option></select></div><div class="col-md-3"><button class="btn btn-secondary">Lọc</button> <a class="btn btn-light" href="{{ route('backend.schedules.index') }}">Bỏ lọc</a></div></form>
</div></div>
@forelse($schedules as $schedule)
@php($weekRules = ($rules[$schedule->id] ?? collect())->keyBy('day_of_week'))
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="mb-1">{{ $schedule->name }} <small class="text-muted">{{ $schedule->code }}</small></h5><span class="badge {{ $schedule->status==='active' ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->status==='active' ? 'Đang sử dụng' : 'Ngừng sử dụng' }}</span></div><a class="btn btn-soft-primary" href="{{ route('backend.schedules.edit', $schedule->id) }}">Chỉnh sửa</a></div>
    <div class="card-body">
        @if($schedule->description)<p>{{ $schedule->description }}</p>@endif
        <div class="table-responsive"><table class="table table-striped table-nowrap align-middle"><thead><tr><th>Ngày</th><th>Giờ hành chính</th><th>Giờ nghỉ</th><th>Thời gian hành chính</th><th>Khung giờ OT</th></tr></thead><tbody>
        @foreach($days as $day=>$label)
        @php($rule = $weekRules->get($day))
        <tr><td>{{ $label }}</td><td>{{ $rule && $rule->is_working_day ? substr($rule->start_time,0,5).' – '.substr($rule->end_time,0,5) : 'Không làm hành chính' }}</td><td>{{ $rule && $rule->break_start ? substr($rule->break_start,0,5).' – '.substr($rule->break_end,0,5) : '—' }}</td><td>{{ $rule ? intdiv($rule->required_minutes,60).' giờ '.($rule->required_minutes % 60).' phút' : '—' }}</td><td>{{ $rule && $rule->ot_start ? substr($rule->ot_start,0,5).' – '.substr($rule->ot_end,0,5).($rule->ot_next_day ? ' (+1 ngày)' : '') : 'Không cấu hình' }}</td></tr>
        @endforeach
        </tbody></table></div>
        <div class="text-muted">Tổng thời gian hành chính mỗi tuần: {{ intdiv($weekRules->sum('required_minutes'),60) }} giờ {{ $weekRules->sum('required_minutes') % 60 }} phút (đã trừ giờ nghỉ).</div>
    </div>
</div>
@empty
<div class="card"><div class="card-body text-center text-muted py-4">Chưa có lịch làm việc phù hợp. Chọn “Thêm lịch làm việc” để thiết lập.</div></div>
@endforelse
{{ $schedules->links() }}
@endsection