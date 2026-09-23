@extends('backend.layouts.app')
@section('title', 'Lịch chấm công')
@section('page_title', 'Lịch chấm công nhân viên')
@section('breadcrumb', 'Lịch chấm công')
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.25rem; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
.attendance-calendar { min-width: 840px; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
.attendance-calendar .weekday { text-align: center; font-weight: 600; padding: 10px 4px; color: #495057; }
.attendance-day { padding: 12px; border: 1px solid #dfe3e8; border-radius: 8px; background: #fff; color: #344054; }
.attendance-day.complete { background: #effaf5; border-color: #a5d8bd; }
.attendance-day.missing { background: #fff7e6; border-color: #efc66f; }
.attendance-day.blank { background: #f4f5f7; border-color: #d0d5dd; }
.attendance-day.overnight { background: #f5f0ff; border-color: #cab1ed; }
.attendance-day.leave { background: #e0f2fe; border-color: #bae6fd; }
.attendance-day.adjustment { background: #fce7f3; border-color: #fbcfe8; }
.attendance-day.business { background: #ffedd5; border-color: #fed7aa; }
.attendance-day.outside { background: #fafafa; border-style: dashed; color: #a1a7b0; }
.attendance-day.is-today { outline: 2px solid #405189; outline-offset: -2px; }
.attendance-day .day-number { font-size: 16px; font-weight: 700; }
.attendance-day .day-status { font-size: 12px; margin-top: 9px; line-height: 1.5; }
.attendance-day .punch { display:flex; justify-content:space-between; gap:6px; font-variant-numeric:tabular-nums; margin-top: 5px; }
.attendance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 12px; }
.attendance-summary .metric { background: #fff; border: 1px solid #e3e7ed; border-radius: 8px; padding: 14px; }
.attendance-summary .metric strong { display:block; font-size: 25px; color: #344054; }
.attendance-day details { margin-top: 8px; font-size: 12px; }
.attendance-day summary { cursor: pointer; color: #405189; }
.attendance-day details div { overflow-wrap:anywhere; margin-top:4px; }
</style>
@endpush
@section('content')
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if(auth()->check() && auth()->user()->isAdmin())
<div class="card"><div class="card-body">
    <form action="{{ route('backend.calendar.index') }}" method="get" class="row g-3 align-items-end">
        <div class="col-md-6">
            <label for="calendar-employee" class="form-label">Nhân viên</label>
            <select id="calendar-employee" name="employee_id" class="form-select" @disabled(!$employee)>
                @forelse($employees as $person)
                <option value="{{ $person->id }}" @selected($employee && $person->id === $employee->id)>{{ $person->employee_code }} — {{ $person->name }}</option>
                @empty<option>Chưa có nhân viên</option>@endforelse
            </select>
        </div>
        <div class="col-md-3"><label for="calendar-month" class="form-label">Tháng theo dõi</label><input id="calendar-month" class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}" min="1900-01" max="2199-12" required></div>
        <div class="col-md-3"><button class="btn btn-primary" @disabled(!$employee)>Xem lịch</button> <a class="btn btn-light" href="{{ route('backend.employees.index') }}">Nhân viên</a></div>
    </form>
</div></div>
@endif
@if(!$employee)
<div class="alert alert-info">Chưa có nhân viên. <a href="{{ route('backend.attendance.index') }}">Nhập file chấm công</a> để bắt đầu theo dõi.</div>
@else
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div><h5 class="mb-1">{{ $employee->name }} <span class="text-muted">· {{ $employee->employee_code }}</span></h5><span class="text-muted">{{ $employee->department_name ?? 'Chưa có phòng ban' }}</span></div>
    <div class="d-flex align-items-center gap-2">
        @if($month->format('Y-m') > '1900-01')<a class="btn btn-light" aria-label="Tháng trước" href="{{ route('backend.calendar.index', ['employee_id'=>$employee->id, 'month'=>$month->subMonth()->format('Y-m')]) }}">‹ Trước</a>@endif
        <strong>Tháng {{ $month->format('m/Y') }}</strong>
        @if($month->format('Y-m') < '2199-12')<a class="btn btn-light" aria-label="Tháng sau" href="{{ route('backend.calendar.index', ['employee_id'=>$employee->id, 'month'=>$month->addMonth()->format('Y-m')]) }}">Sau ›</a>@endif
        <a class="btn btn-soft-primary" href="{{ route('backend.calendar.index', ['employee_id'=>$employee->id, 'month'=>now('Asia/Ho_Chi_Minh')->format('Y-m')]) }}">Tháng này</a>
    </div>
</div>
<div class="row g-2 mb-3">
    <div class="col-auto">
        <div class="card h-100 shadow-sm border-0 bg-primary text-white mb-0">
            <div class="card-body p-2 px-3 text-center">
                <h4 class="mb-0 fw-bold text-white">{{ $totalCongThucTe }}</h4>
                <div class="text-white-50" style="font-size: 11px; font-weight: 500;">Ngày công đi làm</div>
            </div>
        </div>
    </div>
</div>
<div class="card"><div class="card-body">
    <p class="text-muted mb-3">Xanh: đủ giờ vào/ra · Vàng: thiếu một giờ · Xám: đã nhập nhưng trống cả hai giờ · Tím: cần kiểm tra ca qua đêm. Ngày chưa có dữ liệu không được xem là ngày nghỉ. Lịch chưa tính công hoặc đi trễ.</p>
    <div class="overflow-auto pb-2">
        <div class="attendance-calendar" aria-label="Lịch chấm công tháng {{ $month->format('m/Y') }}">
            @foreach(['Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy','Chủ nhật'] as $weekday)<div class="weekday">{{ $weekday }}</div>@endforeach
            @foreach($days as $day)
            <section class="attendance-day {{ $day['in_month'] ? $day['status'] : 'outside' }} {{ $day['today'] ? 'is-today' : '' }}" aria-label="{{ $day['date']->format('d/m/Y') }}{{ $day['in_month'] ? ': '.$day['label'] : '' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="day-number">{{ $day['date']->day }}</span>
                    <div>
                        @if($day['today'])<small class="me-1">Hôm nay</small>@endif
                        @if(isset($day['cong']) && $day['cong'] !== null)
                            @php
                                $badgeClass = 'bg-danger';
                                if ($day['cong'] == 1 || ($day['cong'] == 0.5 && $day['date']->dayOfWeek == \Carbon\Carbon::SATURDAY)) {
                                    $badgeClass = 'bg-success';
                                } elseif ($day['cong'] == 0.5) {
                                    $badgeClass = 'bg-warning text-dark';
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }} border-0" style="font-size: 12px; padding: 5px 8px;">{{ $day['cong'] }} công</span>
                        @endif
                    </div>
                </div>
                @if($day['in_month'])
                    @if($day['entry'])
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="punch"><span>Vào</span><strong class="checkin-val-{{ $day['entry']->id ?? 'null' }}">{{ $day['entry']->checkin !== null ? substr($day['entry']->checkin,0,5) : '—' }}</strong></div>
                            @if(isset($day['entry']->id))
                                <button type="button" class="btn btn-sm btn-light p-0 px-1 border-0 shadow-none swap-punch" 
                                        data-id="{{ $day['entry']->id }}" 
                                        title="Đổi giờ vào/ra">
                                    <i class="ri-arrow-left-right-line"></i>
                                </button>
                            @endif
                            <div class="punch"><span>Ra</span><strong class="checkout-val-{{ $day['entry']->id ?? 'null' }}">{{ $day['entry']->checkout !== null ? substr($day['entry']->checkout,0,5) : '—' }}</strong></div>
                        </div>
                    @endif
                    @php
                        $displayLabel = $day['label'];
                        if ($day['status'] === 'complete' && isset($day['metrics'])) {
                            $late = $day['metrics']['late_arrival'] ?? 0;
                            $early = $day['metrics']['early_departure'] ?? 0;
                            if ($late > 0 || $early > 0) {
                                $arr = [];
                                if ($late > 0) $arr[] = "Muộn {$late}p";
                                if ($early > 0) $arr[] = "Sớm {$early}p";
                                $displayLabel = implode(' - ', $arr);
                            }
                        }
                    @endphp
                    <div class="day-status fw-medium {!! $displayLabel != $day['label'] ? 'text-danger' : '' !!}">
                        {{ $displayLabel }}
                        @if(isset($day['request']) && $day['request'])
                            <span class="text-primary fw-normal ms-1" style="font-size: 11px;">
                                <i class="ri-file-list-3-line align-middle"></i> 
                                @if($day['request']->type == 'attendance_adjustment')
                                    (Xác nhận: {{ $day['request']->start_session == 'morning' ? 'Vào' : ($day['request']->start_session == 'afternoon' ? 'Ra' : 'Vào/Ra') }})
                                @elseif($day['request']->type == 'business_trip')
                                    ({{ isset($day['request']->business_hours) && $day['request']->business_hours < 8 ? round($day['request']->business_hours, 1) . ' giờ' : 'Cả ngày' }})
                                @elseif($day['request']->start_session == 'afternoon' && $day['request']->start_date == $day['date']->toDateString())
                                    (Nửa chiều)
                                @elseif($day['request']->end_session == 'morning' && $day['request']->end_date == $day['date']->toDateString())
                                    (Nửa sáng)
                                @else
                                    (Cả ngày)
                                @endif
                            </span>
                        @endif
                    </div>
                @endif
            </section>
            @endforeach
        </div>
    </div>
</div></div>
@endif
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#calendar-employee').select2({
        placeholder: "Chọn nhân viên...",
        allowClear: false,
        width: '100%'
    });
    
    // Auto-submit form when employee changes
    $('#calendar-employee').on('change', function() {
        if($(this).val()) {
            $(this).closest('form').submit();
        }
    });

    $('.swap-punch').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        
        Swal.fire({
            title: 'Cảnh báo',
            text: 'Việc thay đổi này có thể ảnh hưởng trực tiếp đến số lượng công và giờ tính lương của nhân viên. Bạn có chắc chắn muốn hoán đổi giờ vào/ra?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đồng ý, đổi ngay!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                var originalIcon = btn.html();
                btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
                
                $.ajax({
                    url: '{{ route("backend.calendar.swap") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id
                    },
                    success: function(res) {
                        if (res.success) {
                            $('.checkin-val-' + id).text(res.checkin);
                            $('.checkout-val-' + id).text(res.checkout);
                            btn.prop('disabled', false).html(originalIcon);
                            Swal.fire({
                                title: 'Thành công!', 
                                icon: 'success',
                                toast: true,
                                position: 'bottom-start',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi!', 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
                        btn.prop('disabled', false).html(originalIcon);
                    }
                });
            }
        });
    });
});
</script>
@endpush
