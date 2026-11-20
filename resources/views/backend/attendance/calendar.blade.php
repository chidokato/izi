@extends('backend.layouts.app')
@section('title', 'Lịch chấm công')
@section('page_title', 'Lịch chấm công nhân viên')
@section('breadcrumb', 'Lịch chấm công')
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.select2-container .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.25rem; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
.attendance-calendar { min-width: 840px; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
.attendance-calendar .weekday { text-align: center; font-weight: 600; padding: 10px 4px; color: #495057; }
.attendance-day { padding: 12px; border: 1px solid #dfe3e8; border-radius: 8px; background: #fff; color: #344054; position: relative; transition: box-shadow 0.2s ease, border-color 0.2s ease; }
.attendance-day:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
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
.attendance-day .day-status { font-size: 12px; margin-top: 9px; line-height: 1.5; padding-right: 22px; }
.attendance-day .punch { display:flex; justify-content:space-between; gap:6px; font-variant-numeric:tabular-nums; margin-top: 5px; }
.attendance-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 12px; }
.attendance-summary .metric { background: #fff; border: 1px solid #e3e7ed; border-radius: 8px; padding: 14px; }
.attendance-summary .metric strong { display:block; font-size: 25px; color: #344054; }
.attendance-day details { margin-top: 8px; font-size: 12px; }
.attendance-day summary { cursor: pointer; color: #405189; }
.attendance-day details div { overflow-wrap:anywhere; margin-top:4px; }

/* Quick Request Button in Calendar Cell */
.btn-quick-request {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 22px;
    height: 22px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    background: rgba(64, 81, 137, 0.08);
    color: #405189;
    border: 1px solid rgba(64, 81, 137, 0.18);
    font-size: 12px;
    cursor: pointer;
    z-index: 5;
    opacity: 0.45;
    transition: opacity 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
}
.attendance-day:hover .btn-quick-request {
    opacity: 0.85;
    background: rgba(64, 81, 137, 0.14);
    border-color: rgba(64, 81, 137, 0.3);
}
.btn-quick-request:hover {
    opacity: 1 !important;
    background: #405189 !important;
    border-color: #405189 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(64, 81, 137, 0.35);
    transform: scale(1.12);
}
.btn-quick-request:active {
    transform: scale(0.92) !important;
}

/* Offcanvas custom styling & hiệu ứng trượt mượt mà (smooth cubic-bezier transition) */
#quickRequestOffcanvas {
    --vz-offcanvas-transition: transform 0.42s cubic-bezier(0.16, 1, 0.3, 1);
    transition: transform 0.42s cubic-bezier(0.16, 1, 0.3, 1) !important;
    width: 500px;
    max-width: 95vw;
    box-shadow: -12px 0 36px -4px rgba(15, 23, 42, 0.16), -4px 0 12px -2px rgba(15, 23, 42, 0.08) !important;
    border-left: 1px solid rgba(226, 232, 240, 0.8) !important;
    will-change: transform;
}

.offcanvas-backdrop.fade {
    transition: opacity 0.38s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

.offcanvas-backdrop.show {
    opacity: 0.35 !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
}

#quickRequestOffcanvas .offcanvas-header {
    background: #f8fafc;
    border-bottom: 1px solid #edf2f7;
}

#quickRequestOffcanvas .offcanvas-body {
    transition: opacity 0.35s ease-out 0.08s, transform 0.35s cubic-bezier(0.16, 1, 0.3, 1) 0.08s;
}

#quickRequestOffcanvas:not(.show) .offcanvas-body {
    opacity: 0;
    transform: translateX(18px);
}

#quickRequestOffcanvas.show .offcanvas-body {
    opacity: 1;
    transform: translateX(0);
}
.flatpickr-calendar {
    box-shadow: 0 5px 20px rgba(0,0,0,0.15) !important;
    border: 0 !important;
    border-radius: 8px !important;
    padding-bottom: 8px;
    z-index: 1060 !important;
}
.flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
    background: #405189 !important;
    border-color: #405189 !important;
}
.flatpickr-time {
    border-top: 1px solid #e9ebec !important;
    margin-top: 5px;
    padding-top: 5px;
}
.flatpickr-time input {
    font-weight: 500 !important;
}
</style>
@endpush
@section('content')
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
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
<div class="alert alert-info">
    @if(auth()->check() && auth()->user()->isAdmin())
        Chưa có nhân viên. <a href="{{ route('backend.attendance.index') }}">Nhập file chấm công</a> để bắt đầu theo dõi.
    @else
        Tài khoản của bạn chưa được liên kết với hồ sơ nhân viên. Vui lòng liên hệ quản trị viên để được hỗ trợ.
    @endif
</div>
@else
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div><h5 class="mb-1">{{ $employee->name }} <span class="text-muted">· {{ $employee->employee_code }}</span></h5><span class="text-muted">{{ $employee->department_name ?? 'Chưa có phòng ban' }}</span></div>
    <div class="d-flex align-items-center gap-2">
        @if($month->format('Y-m') > '1900-01')<a class="btn btn-light" aria-label="Tháng trước" href="{{ route('backend.calendar.index', ['employee_id'=>$employee->id, 'month'=>$month->subMonth()->format('Y-m')]) }}">‹ Trước</a>@endif
        <strong>Kỳ {{ $periodStart->format('d/m') }} - {{ $periodEnd->format('d/m/Y') }}</strong>
        @if($month->format('Y-m') < '2199-12')<a class="btn btn-light" aria-label="Tháng sau" href="{{ route('backend.calendar.index', ['employee_id'=>$employee->id, 'month'=>$month->addMonth()->format('Y-m')]) }}">Sau ›</a>@endif
        @php
            $now = now('Asia/Ho_Chi_Minh');
            $currentCycleMonth = $now->day >= 16 ? $now->copy()->addMonth() : $now;
        @endphp
        <a class="btn btn-soft-primary" href="{{ route('backend.calendar.index', ['employee_id'=>$employee->id, 'month'=>$currentCycleMonth->format('Y-m')]) }}">Kỳ này</a>
    </div>
</div>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        @php
            $formatCong = function($val) {
                if ($val === null) return '0';
                $r = round($val, 2);
                return ($r == floor($r)) ? (string)(int)$r : str_replace('.', ',', (string)$r);
            };
        @endphp
        <div class="card shadow-sm border-0 bg-primary text-white mb-0" style="min-width: 140px;">
            <div class="card-body p-2 px-3 text-center">
                <h4 class="mb-0 fw-bold text-white">{{ $formatCong($totalCongTinhLuong) }}/{{ $formatCong($totalCongChuan ?? 0) }}</h4>
                <div class="text-white-50" style="font-size: 11px; font-weight: 500;" title="Tổng số công đi làm / Tổng số công chuẩn">Công đi làm / Công chuẩn</div>
            </div>
        </div>
        <div class="card shadow-sm border-0 bg-success text-white mb-0" style="min-width: 120px;">
            <div class="card-body p-2 px-3 text-center">
                <h4 class="mb-0 fw-bold text-white">{{ floatval($employee->annual_leave_balance ?? 0) }}</h4>
                <div class="text-white-50" style="font-size: 11px; font-weight: 500;">Tồn phép năm</div>
            </div>
        </div>
    </div>
    <div>
        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#attendanceRulesModal">
            <i class="ri-questionnaire-line fs-17"></i>
            <span class="fw-semibold">Cách tính công</span>
        </button>
    </div>
</div>
<div class="card"><div class="card-body">
    <p class="text-muted mb-3">Xanh: đủ giờ vào/ra · Vàng: thiếu một giờ · Xám: đã nhập nhưng trống cả hai giờ · Tím: cần kiểm tra ca qua đêm. Ngày chưa có dữ liệu không được xem là ngày nghỉ. Lịch chưa tính công hoặc đi trễ.</p>
    <div class="overflow-auto pb-2">
        <div class="attendance-calendar" aria-label="Lịch chấm công tháng {{ $month->format('m/Y') }}">
            @foreach(['Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy','Chủ nhật'] as $weekday)<div class="weekday">{{ $weekday }}</div>@endforeach
            @foreach($days as $day)
                @php
                    $requestColor = '';
                    if (isset($day['request'])) {
                        switch ($day['request']->type) {
                            case 'paid_leave': $requestColor = '#bae6fd'; break; // light blue
                            case 'unpaid_leave': $requestColor = '#e5e7eb'; break; // light gray
                            case 'business_trip': $requestColor = '#fed7aa'; break; // light orange
                            case 'attendance_adjustment': $requestColor = '#fbcfe8'; break; // light pink
                            default: $requestColor = '#e3e7ed'; // default gray
                        }
                    }
                @endphp
            <section class="attendance-day {{ $day['in_month'] ? $day['status'] : 'outside' }} {{ $day['today'] ? 'is-today' : '' }}" 
                     style="{{ $requestColor ? 'border-bottom: 4px solid '.$requestColor : '' }}"
                     aria-label="{{ $day['date']->format('d/m/Y') }}{{ $day['in_month'] ? ': '.$day['label'] : '' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="day-number">{{ $day['date']->day == 1 ? $day['date']->format('d/m') : $day['date']->day }}</span>
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
                            $late = max(0, ($day['metrics']['late_arrival'] ?? 0) - ($day['grace_period'] ?? 0));
                            $early = max(0, $day['metrics']['early_departure'] ?? 0);
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
                                @if(!empty($day['is_non_working_day']) || $day['date']->dayOfWeek == 0)
                                    (Nghỉ)
                                @elseif($day['request']->type == 'attendance_adjustment')
                                    (XN: {{ $day['request']->start_session == 'morning' ? 'Vào' : ($day['request']->start_session == 'afternoon' ? 'Ra' : 'Vào/Ra') }})
                                @elseif($day['request']->type == 'business_trip')
                                    ({{ isset($day['request']->business_hours) && $day['request']->business_hours < 8 ? round($day['request']->business_hours, 1) . 'h' : 'Cả ngày' }})
                                @elseif($day['request']->start_session == 'afternoon' && substr($day['request']->start_date, 0, 10) == $day['date']->toDateString())
                                    (Nửa chiều)
                                @elseif($day['request']->end_session == 'morning' && substr($day['request']->end_date, 0, 10) == $day['date']->toDateString())
                                    (Nửa sáng)
                                @else
                                    (Cả ngày)
                                @endif
                            </span>
                        @endif
                    </div>
                    <!-- Nút làm phiếu nhanh ở góc ngày -->
                    <button type="button" 
                            class="btn-quick-request" 
                            data-date="{{ $day['date']->format('Y-m-d') }}"
                            data-date-vn="{{ $day['date']->format('d/m/Y') }}"
                            data-weekday="{{ ['Chủ nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy'][$day['date']->dayOfWeek] }}"
                            data-status="{{ $day['status'] }}"
                            data-checkin="{{ ($day['entry'] && $day['entry']->checkin !== null) ? substr($day['entry']->checkin, 0, 5) : '' }}"
                            data-checkout="{{ ($day['entry'] && $day['entry']->checkout !== null) ? substr($day['entry']->checkout, 0, 5) : '' }}"
                            data-day-of-week="{{ $day['date']->dayOfWeek }}"
                            title="Làm phiếu nhanh ({{ $day['date']->format('d/m') }})">
                        <i class="ri-file-add-line"></i>
                    </button>
                @endif
            </section>
            @endforeach
        </div>
    </div>
</div></div>
@endif

<!-- Div trượt từ bên phải sang: Giao diện tạo phiếu yêu cầu nhanh -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="quickRequestOffcanvas" aria-labelledby="quickRequestOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom py-3">
        <div>
            <h5 class="offcanvas-title d-flex align-items-center gap-2 mb-1" id="quickRequestOffcanvasLabel">
                <i class="ri-file-add-line text-primary fs-18"></i>
                <span class="fw-bold">Làm phiếu nhanh</span>
            </h5>
            <div class="text-muted fs-12">
                <i class="ri-calendar-event-line me-1"></i> Ngày được chọn: <span class="fw-semibold text-primary" id="quick-selected-date-badge"></span>
            </div>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-3 p-md-4">
        <form id="quick-request-form" action="{{ route('backend.attendance-requests.store') }}" method="POST">
            @csrf
            
            <div id="quick-alert-container"></div>
            
            @if(auth()->check() && auth()->user()->isAdmin())
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nhân viên <span class="text-danger">*</span></label>
                    <select name="employee_id" id="quick-employee-id" class="form-select" required>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" 
                                    data-manager="{{ $emp->manager ? $emp->manager->name : 'Bỏ qua' }}" 
                                    data-manager-l2="{{ $emp->managerL2 ? $emp->managerL2->name : 'Bỏ qua' }}"
                                    data-leave-balance="{{ $emp->annual_leave_balance ?? 0 }}"
                                    {{ ($employee && $employee->id == $emp->id) ? 'selected' : '' }}>
                                {{ $emp->employee_code }} — {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="employee_id" id="quick-employee-id" value="{{ auth()->user()->employee_id }}">
                @if(!auth()->user()->employee_id)
                    <div class="alert alert-warning py-2 px-3 fs-13 mb-3">
                        <i class="ri-alert-line align-middle me-1"></i> Tài khoản của bạn chưa được liên kết với hồ sơ nhân viên.
                    </div>
                @else
                    <div class="p-2 mb-3 bg-light rounded border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary fs-14">
                                    <i class="ri-user-line"></i>
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0 fs-13">{{ auth()->user()->employee->name ?? auth()->user()->name }}</h6>
                                <span class="text-muted fs-11">{{ auth()->user()->employee->employee_code ?? '' }}</span>
                            </div>
                        </div>
                        <span class="badge bg-soft-success text-success fs-11" id="quick-user-leave-badge">
                            Phép còn: {{ auth()->user()->employee->annual_leave_balance ?? 0 }} ngày
                        </span>
                    </div>
                @endif
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Loại phiếu <span class="text-danger">*</span></label>
                <select name="type" id="quick-request-type" class="form-select" required>
                    <option value="attendance_adjustment">Bổ sung công (quên chấm)</option>
                    <option value="paid_leave">Nghỉ phép năm</option>
                    <option value="unpaid_leave">Nghỉ không lương</option>
                    <option value="business_trip">Phiếu công tác</option>
                </select>
                <div id="quick-limit-info" class="text-info mt-1 fs-12" style="display: none;">
                    <i class="ri-information-line align-middle"></i> <span id="quick-limit-text"></span>
                </div>
            </div>

            <!-- GIAO DIỆN: BỔ SUNG CÔNG (attendance_adjustment) -->
            <div id="quick-group-adjustment" class="quick-group-fields">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ngày bổ sung <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="start_date_adj" id="quick_start_date_adj" class="form-control" placeholder="YYYY-MM-DD" required>
                            <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Xác nhận công <span class="text-danger">*</span></label>
                        <select name="start_session_adj" id="quick_start_session_adj" class="form-select" required>
                            <option value="morning">Giờ vào</option>
                            <option value="afternoon">Giờ ra</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-info py-2 px-3 fs-12 mb-3">
                    <i class="ri-lightbulb-line me-1"></i> Bổ sung giờ chấm cho ca làm việc khi quên quẹt thẻ. Tối đa 3 lần/tháng.
                </div>
            </div>

            <!-- GIAO DIỆN: NGHỈ PHÉP (paid_leave / unpaid_leave) -->
            <div id="quick-group-leave" class="quick-group-fields" style="display: none;">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ngày xin nghỉ <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="start_date_leave" id="quick_start_date_leave" class="form-control" placeholder="YYYY-MM-DD">
                            <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Buổi bắt đầu <span class="text-danger">*</span></label>
                        <select name="start_session_leave" id="quick_start_session_leave" class="form-select">
                            <option value="morning">Sáng</option>
                            <option value="afternoon">Chiều</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Số ngày nghỉ <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="quick_btn_minus_leave"><i class="ri-subtract-line"></i></button>
                            <input type="number" name="leave_days" id="quick_leave_days" class="form-control text-center" step="0.5" min="0.5" value="1">
                            <button class="btn btn-outline-secondary" type="button" id="quick_btn_plus_leave"><i class="ri-add-line"></i></button>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Ngày đi làm lại</label>
                        <input type="text" id="quick_return_date_display" class="form-control bg-light" readonly placeholder="Tự động tính...">
                    </div>
                </div>
            </div>

            <!-- GIAO DIỆN: CÔNG TÁC (business_trip) -->
            <div id="quick-group-business" class="quick-group-fields" style="display: none;">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Từ thời gian <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="start_date_business" id="quick_start_date_business" class="form-control" placeholder="YYYY-MM-DD HH:mm">
                            <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Đến thời gian <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="end_date_business" id="quick_end_date_business" class="form-control" placeholder="YYYY-MM-DD HH:mm">
                            <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GIAO DIỆN: TĂNG CA (overtime) -->
            <div id="quick-group-overtime" class="quick-group-fields" style="display: none;">
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ngày tăng ca <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="date_overtime" id="quick_date_overtime" class="form-control" placeholder="YYYY-MM-DD">
                            <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Giờ bắt đầu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="start_time_overtime" id="quick_start_time_overtime" class="form-control" placeholder="HH:mm" value="17:30">
                            <span class="input-group-text"><i class="ri-time-line"></i></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Giờ kết thúc <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="end_time_overtime" id="quick_end_time_overtime" class="form-control" placeholder="HH:mm" value="19:30">
                            <span class="input-group-text"><i class="ri-time-line"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LÝ DO -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Lý do / Diễn giải <span class="text-danger">*</span></label>
                <textarea name="reason" id="quick_reason" class="form-control" rows="3" placeholder="Nhập lý do cụ thể..." required></textarea>
            </div>

            <!-- QUY TRÌNH DUYỆT -->
            <div class="card border border-dashed mb-3 bg-light">
                <div class="card-body p-2 px-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-semibold fs-12 text-muted text-uppercase">Quy trình phê duyệt</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 fs-12">
                        <span class="badge bg-primary">1</span>
                        <span class="text-muted">Quản lý:</span>
                        <strong id="quick-manager-name" class="text-dark">
                            @if($employee && $employee->manager_id)
                                {{ $employees->firstWhere('id', $employee->manager_id)->name ?? 'Bỏ qua' }}
                            @elseif($currentUserEmployee && $currentUserEmployee->manager)
                                {{ $currentUserEmployee->manager->name }}
                            @else
                                Bỏ qua
                            @endif
                        </strong>
                        <i class="ri-arrow-right-s-line text-muted"></i>
                        <span class="badge bg-success">2</span>
                        <span class="text-muted">Nhân sự duyệt</span>
                    </div>
                </div>
            </div>

            <!-- NÚT THAO TÁC -->
            <div class="d-flex gap-2 justify-content-end pt-2 border-top">
                <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Đóng</button>
                <button type="submit" class="btn btn-primary px-4" id="quick-btn-submit">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true" id="quick-submit-spinner"></span>
                    <i class="ri-send-plane-fill me-1" id="quick-submit-icon"></i>
                    <span>Gửi phiếu</span>
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Giải thích Quy định & Cách tính công -->
<div class="modal fade" id="attendanceRulesModal" tabindex="-1" aria-labelledby="attendanceRulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-3 border-bottom">
                <h5 class="modal-title d-flex align-items-center gap-2" id="attendanceRulesModalLabel">
                    <i class="ri-book-read-line text-primary fs-20"></i>
                    <span class="fw-bold">Quy chế & Cách tính công chấm công</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                
                <!-- 1. Giờ làm việc tiêu chuẩn -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary d-flex align-items-center gap-1 mb-2">
                        <i class="ri-time-line"></i> 1. Giờ làm việc tiêu chuẩn
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-2 text-center fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th>Thứ / Ngày</th>
                                    <th>Giờ vào ca</th>
                                    <th>Nghỉ trưa</th>
                                    <th>Giờ tan ca</th>
                                    <th>Tiêu chuẩn giờ</th>
                                    <th>Mức công chuẩn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-start ps-3">Thứ 2 – Thứ 6</td>
                                    <td><span class="badge bg-soft-primary text-primary fs-12">08:30</span></td>
                                    <td>12:30 – 13:30</td>
                                    <td><span class="badge bg-soft-primary text-primary fs-12">17:30</span></td>
                                    <td>8 giờ</td>
                                    <td><span class="badge bg-success fs-12">1.0 công</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-start ps-3">Thứ Bảy</td>
                                    <td><span class="badge bg-soft-primary text-primary fs-12">08:30</span></td>
                                    <td>Không nghỉ trưa</td>
                                    <td><span class="badge bg-soft-primary text-primary fs-12">12:30</span></td>
                                    <td>4 giờ</td>
                                    <td><span class="badge bg-success fs-12">0.5 công</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-start ps-3">Chủ nhật</td>
                                    <td colspan="4" class="text-muted">Nghỉ hàng tuần</td>
                                    <td><span class="badge bg-secondary fs-12">0 công</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning py-2 px-3 fs-12 mb-0 d-flex align-items-center gap-2">
                        <i class="ri-alert-line fs-18 text-warning flex-shrink-0"></i>
                        <div>
                            <strong>Quy định giờ giấc:</strong> Nhân viên chỉ được phép đi muộn tối đa <strong>10 phút</strong> (đến trước <strong>08:40</strong>). Công ty <strong>không cho phép về sớm</strong> trước giờ tan ca (17:30 ngày thường, 12:30 Thứ Bảy).
                        </div>
                    </div>
                </div>

                <!-- 2. Cách xác định thời gian Đi muộn & Về sớm -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary d-flex align-items-center gap-1 mb-2">
                        <i class="ri-timer-flash-line"></i> 2. Cách xác định thời gian làm việc & Đi muộn, Về sớm
                    </h6>
                    <div class="p-3 bg-light rounded border fs-13">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="fw-semibold text-danger mb-1"><i class="ri-arrow-right-circle-line me-1"></i> Đi muộn (Late Arrival):</div>
                                <p class="text-muted mb-0">Chỉ tính khi vào làm sau 08:40 (quá 10 phút cho phép). Ví dụ: quẹt vào lúc 08:50 tính là đi muộn <strong>10 phút</strong>.</p>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-semibold text-warning mb-1"><i class="ri-arrow-left-circle-line me-1"></i> Về sớm (Early Departure):</div>
                                <p class="text-muted mb-0">Tính từ trước giờ kết thúc ca làm việc (17:30 ngày thường, 12:30 Thứ Bảy). Ví dụ: quẹt ra lúc 17:20 tính là về sớm <strong>10 phút</strong>.</p>
                            </div>
                            <div class="col-12 border-top pt-2 mt-2">
                                <div class="fw-semibold text-dark">
                                    <i class="ri-calculator-line me-1 text-primary"></i> Nguyên tắc tính giờ làm việc: 
                                    <span class="text-muted fw-normal">Hệ thống chỉ tính tổng thời gian có mặt thực tế <strong>trong giờ làm việc theo lịch</strong> (đã trừ thời gian nghỉ trưa). Không cộng dồn phần đến sớm trước ca hoặc về muộn sau ca.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Bảng quy định tính công -->
                <div class="mb-4">
                    <h6 class="fw-bold text-primary d-flex align-items-center gap-1 mb-2">
                        <i class="ri-scales-3-line"></i> 3. Quy định tính 1 công, nửa (0.5) công & 0 công
                    </h6>

                    <!-- Ngày thường -->
                    <div class="card border mb-3">
                        <div class="card-header bg-light py-2">
                            <strong class="fs-13 text-dark">Áp dụng cho ngày thường (Thứ 2 đến Thứ 6 - Ca 8 giờ):</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 fs-13">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Mức công tính</th>
                                            <th style="width: 35%;">Điều kiện thời gian làm việc trong giờ</th>
                                            <th>Chi tiết quy đổi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <span class="badge bg-success fs-12 px-2 py-1"><i class="ri-checkbox-circle-line me-1"></i> Đủ 1.0 công</span>
                                            </td>
                                            <td>
                                                <strong class="text-success">Thời gian làm việc &ge; 6 tiếng</strong>
                                                <div class="text-dark fw-semibold fs-12 mt-1"><i class="ri-time-line me-1 text-success"></i>Từ 6 giờ trở lên (trong ca 8h)</div>
                                            </td>
                                            <td class="text-muted">
                                                Quẹt đủ vào/ra, tổng thời gian làm việc thực tế trong khung giờ làm việc đạt từ <strong>6 tiếng trở lên</strong>. Được tính trọn vẹn <strong>1.0 công</strong>.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge bg-warning text-dark fs-12 px-2 py-1"><i class="ri-time-line me-1"></i> Tính 0.5 công</span>
                                            </td>
                                            <td>
                                                <strong class="text-warning">Từ 2 tiếng đến dưới 6 tiếng</strong>
                                                <div class="text-dark fw-semibold fs-12 mt-1"><i class="ri-time-line me-1 text-warning"></i>2 giờ &le; Thời gian làm &lt; 6 giờ</div>
                                                <div class="text-muted fs-11 mt-1">Ví dụ: làm 1 buổi hoặc đi muộn/về sớm</div>
                                            </td>
                                            <td class="text-muted">
                                                Tổng thời gian làm việc thực tế trong khung giờ đạt từ <strong>2 tiếng đến dưới 6 tiếng</strong> (như làm một buổi sáng hoặc chiều). Được tính <strong>0.5 công (nửa công)</strong>.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge bg-danger fs-12 px-2 py-1"><i class="ri-close-circle-line me-1"></i> Tính 0 công</span>
                                            </td>
                                            <td>
                                                <strong class="text-danger">Thời gian làm việc &lt; 2 tiếng</strong>
                                                <div class="text-dark fw-semibold fs-12 mt-1"><i class="ri-time-line me-1 text-danger"></i>Dưới 2 giờ làm việc</div>
                                                <div class="text-muted fs-11 mt-1">Hoặc thiếu giờ vào / ra / không chấm công</div>
                                            </td>
                                            <td class="text-muted">
                                                Tổng thời gian làm việc thực tế trong khung giờ <strong>dưới 2 tiếng</strong>, hoặc chỉ quẹt 1 lần (thiếu giờ vào hoặc ra chưa được duyệt bổ sung). Tính <strong>0 công</strong>.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Thứ 7 -->
                    <div class="card border mb-0">
                        <div class="card-header bg-light py-2">
                            <strong class="fs-13 text-dark">Áp dụng cho Thứ Bảy (Ca làm việc 4 giờ sáng):</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 fs-13">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Mức công tính</th>
                                            <th style="width: 35%;">Điều kiện thời gian làm việc trong giờ</th>
                                            <th>Chi tiết quy đổi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <span class="badge bg-success fs-12 px-2 py-1"><i class="ri-checkbox-circle-line me-1"></i> Đủ 0.5 công</span>
                                            </td>
                                            <td>
                                                <strong class="text-success">Thời gian làm việc &gt; 2 tiếng</strong>
                                                <div class="text-dark fw-semibold fs-12 mt-1"><i class="ri-time-line me-1 text-success"></i>Hơn 2 giờ (trong ca 4h)</div>
                                            </td>
                                            <td class="text-muted">
                                                Làm việc Thứ Bảy có tổng thời gian làm việc thực tế trong ca đạt <strong>hơn 2 tiếng</strong>. Được tính trọn vẹn ca Thứ Bảy là <strong>0.5 công (nửa công)</strong>.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge bg-danger fs-12 px-2 py-1"><i class="ri-close-circle-line me-1"></i> Tính 0 công</span>
                                            </td>
                                            <td>
                                                <strong class="text-danger">Thời gian làm việc &le; 2 tiếng</strong>
                                                <div class="text-dark fw-semibold fs-12 mt-1"><i class="ri-time-line me-1 text-danger"></i>Từ 2 giờ trở xuống</div>
                                                <div class="text-muted fs-11 mt-1">Hoặc thiếu giờ vào / ra / không chấm công</div>
                                            </td>
                                            <td class="text-muted">
                                                Tổng thời gian làm việc ca Thứ Bảy chỉ đạt <strong>từ 2 tiếng trở xuống</strong>, hoặc thiếu lượt chấm công. Tính <strong>0 công</strong>.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Phiếu yêu cầu & Xử lý quên chấm -->
                <div>
                    <h6 class="fw-bold text-primary d-flex align-items-center gap-1 mb-2">
                        <i class="ri-file-edit-line"></i> 4. Quên chấm công & Các loại phiếu yêu cầu
                    </h6>
                    <div class="row g-2 fs-13">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-white h-100 shadow-none">
                                <div class="fw-semibold text-primary mb-2 d-flex align-items-center gap-1">
                                    <i class="ri-file-add-line fs-16"></i> Phiếu bổ sung công (quên chấm):
                                </div>
                                <ul class="text-muted ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                                    <li>Mỗi nhân viên được tạo tối đa <strong>3 phiếu/tháng</strong>.</li>
                                    <li>Bù lại chính xác giờ chuẩn ca (08:30 hoặc 17:30).</li>
                                    <li>Sau khi được quản lý duyệt, ngày đó sẽ được tính đủ công.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-white h-100 shadow-none">
                                <div class="fw-semibold text-success mb-2 d-flex align-items-center gap-1">
                                    <i class="ri-calendar-check-line fs-16"></i> Phiếu nghỉ phép năm:
                                </div>
                                <ul class="text-muted ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                                    <li>Nghỉ cả ngày: tính <strong>1.0 công</strong> (trừ 1 ngày phép tồn).</li>
                                    <li>Nghỉ nửa buổi (sáng/chiều): tính <strong>0.5 công</strong> (trừ 0.5 ngày phép).</li>
                                    <li>Ngày nghỉ phép năm vẫn được tính công hưởng nguyên lương.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
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

    // ----------------------------------------------------
    // LOGIC CHO NÚT LÀM PHIẾU NHANH & DIV TRƯỢT TỪ PHẢI SANG
    // ----------------------------------------------------
    let currentLeaveBalance = {{ floatval($employee->annual_leave_balance ?? 0) }};

    // Flatpickr instances
    const fpDateAdj = flatpickr("#quick_start_date_adj", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        locale: "vn",
        onChange: function() { fetchQuickLimits(); }
    });

    const fpDateLeave = flatpickr("#quick_start_date_leave", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        locale: "vn",
        onChange: function() {
            calculateQuickReturnDate();
            validateQuickLeaveBalance();
        }
    });

    const fpEndBusiness = flatpickr("#quick_end_date_business", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "d/m/Y H:i",
        time_24hr: true,
        locale: "vn",
        minuteIncrement: 5
    });

    const fpStartBusiness = flatpickr("#quick_start_date_business", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        altInput: true,
        altFormat: "d/m/Y H:i",
        time_24hr: true,
        locale: "vn",
        minuteIncrement: 5,
        onChange: function(selected) {
            if (selected.length > 0) fpEndBusiness.set('minDate', selected[0]);
        }
    });

    const fpDateOvertime = flatpickr("#quick_date_overtime", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        locale: "vn"
    });

    flatpickr("#quick_start_time_overtime", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 5
    });

    flatpickr("#quick_end_time_overtime", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 5
    });

    function getSelectedEmployeeId() {
        return $('#quick-employee-id').val();
    }

    function updateQuickForm() {
        const type = $('#quick-request-type').val();
        $('.quick-group-fields').hide();

        if (type === 'attendance_adjustment') {
            $('#quick-group-adjustment').show();
            $('#quick-group-adjustment input, #quick-group-adjustment select').prop('disabled', false);
            $('#quick-group-leave input, #quick-group-leave select').prop('disabled', true);
            $('#quick-group-business input').prop('disabled', true);
            $('#quick-group-overtime input').prop('disabled', true);
            fetchQuickLimits();
        } else if (type === 'paid_leave' || type === 'unpaid_leave') {
            $('#quick-group-leave').show();
            $('#quick-group-leave input, #quick-group-leave select').prop('disabled', false);
            $('#quick-group-adjustment input, #quick-group-adjustment select').prop('disabled', true);
            $('#quick-group-business input').prop('disabled', true);
            $('#quick-group-overtime input').prop('disabled', true);
            calculateQuickReturnDate();
            if (type === 'paid_leave') {
                fetchQuickLimits();
            } else {
                $('#quick-limit-info').hide();
                $('#quick-btn-submit').prop('disabled', false);
            }
        } else if (type === 'business_trip') {
            $('#quick-group-business').show();
            $('#quick-group-business input').prop('disabled', false);
            $('#quick-group-adjustment input, #quick-group-adjustment select').prop('disabled', true);
            $('#quick-group-leave input, #quick-group-leave select').prop('disabled', true);
            $('#quick-group-overtime input').prop('disabled', true);
            $('#quick-limit-info').hide();
            $('#quick-btn-submit').prop('disabled', false);
        } else if (type === 'overtime') {
            $('#quick-group-overtime').show();
            $('#quick-group-overtime input').prop('disabled', false);
            $('#quick-group-adjustment input, #quick-group-adjustment select').prop('disabled', true);
            $('#quick-group-leave input, #quick-group-leave select').prop('disabled', true);
            $('#quick-group-business input').prop('disabled', true);
            $('#quick-limit-info').hide();
            $('#quick-btn-submit').prop('disabled', false);
        }
    }

    $('#quick-request-type').on('change', updateQuickForm);

    $('#quick-employee-id').on('change', function() {
        const selected = $(this).find('option:selected');
        if (selected.length) {
            $('#quick-manager-name').text(selected.data('manager') || 'Bỏ qua');
            currentLeaveBalance = parseFloat(selected.data('leave-balance')) || 0;
            fetchQuickLimits();
        }
    });

    function fetchQuickLimits() {
        const empId = getSelectedEmployeeId();
        if (!empId) return;

        const type = $('#quick-request-type').val();
        let targetDate = $('#quick_start_date_adj').val();
        if (type === 'paid_leave' || type === 'unpaid_leave') {
            targetDate = $('#quick_start_date_leave').val();
        }
        if (!targetDate) targetDate = new Date().toISOString().split('T')[0];

        fetch(`{{ route('backend.attendance-requests.check-limit') }}?employee_id=${empId}&date=${targetDate}`)
            .then(res => res.json())
            .then(data => {
                if (type === 'attendance_adjustment') {
                    $('#quick-limit-info').show();
                    if (data.remaining <= 0) {
                        $('#quick-limit-info').removeClass('text-info').addClass('text-danger');
                        $('#quick-limit-text').html(`Nhân viên đã dùng hết 3 phiếu quên chấm trong tháng ${data.month}.`);
                        $('#quick-btn-submit').prop('disabled', true);
                    } else {
                        $('#quick-limit-info').removeClass('text-danger').addClass('text-info');
                        $('#quick-limit-text').html(`Số phiếu quên chấm còn lại trong tháng ${data.month}: <strong>${data.remaining}/${data.total}</strong>`);
                        $('#quick-btn-submit').prop('disabled', false);
                    }
                } else if (type === 'paid_leave') {
                    currentLeaveBalance = parseFloat(data.leave_balance) || 0;
                    $('#quick-limit-info').show();
                    validateQuickLeaveBalance();
                }
            })
            .catch(() => {});
    }

    function validateQuickLeaveBalance() {
        if ($('#quick-request-type').val() !== 'paid_leave') return;
        const requested = parseFloat($('#quick_leave_days').val()) || 0;
        if (requested > currentLeaveBalance) {
            $('#quick-limit-info').show().removeClass('text-info').addClass('text-danger');
            $('#quick-limit-text').html(`Số phép năm còn lại (${currentLeaveBalance} ngày) không đủ cho yêu cầu này (${requested} ngày).`);
            $('#quick-btn-submit').prop('disabled', true);
        } else {
            $('#quick-limit-info').show().removeClass('text-danger').addClass('text-info');
            $('#quick-limit-text').html(`Số phép năm còn lại: <strong>${currentLeaveBalance} ngày</strong>`);
            $('#quick-btn-submit').prop('disabled', false);
        }
    }

    function calculateQuickReturnDate() {
        const type = $('#quick-request-type').val();
        if (type !== 'paid_leave' && type !== 'unpaid_leave') return;

        const startDateStr = $('#quick_start_date_leave').val();
        const startSession = $('#quick_start_session_leave').val();
        const daysToTake = parseFloat($('#quick_leave_days').val()) || 0;

        if (!startDateStr || daysToTake <= 0) {
            $('#quick_return_date_display').val('');
            return;
        }

        const parts = startDateStr.split('-');
        let current = new Date(parts[0], parts[1] - 1, parts[2]);
        current.setHours(0,0,0,0);

        let daysAccumulated = 0;
        let lastLeaveDate = new Date(current);
        let isFirstDay = true;
        let lastDayValConsumed = 0;

        while (daysAccumulated < daysToTake) {
            const dayOfWeek = current.getDay();
            let dailyValue = 0;
            if (dayOfWeek >= 1 && dayOfWeek <= 5) dailyValue = 1;
            else if (dayOfWeek === 6) dailyValue = 0.5;

            if (isFirstDay && startSession === 'afternoon' && dailyValue === 1) dailyValue = 0.5;
            else if (isFirstDay && startSession === 'afternoon' && dailyValue === 0.5) dailyValue = 0;

            if (dailyValue > 0) {
                if (daysAccumulated + dailyValue > daysToTake) {
                    let consumed = daysToTake - daysAccumulated;
                    daysAccumulated += consumed;
                    lastDayValConsumed = consumed;
                } else {
                    daysAccumulated += dailyValue;
                    lastDayValConsumed = dailyValue;
                }
                lastLeaveDate = new Date(current);
            }

            if (daysAccumulated < daysToTake) {
                current.setDate(current.getDate() + 1);
                isFirstDay = false;
            }
        }

        let returnDate = new Date(lastLeaveDate);
        let endsMidDay = false;
        if (lastLeaveDate.getDay() >= 1 && lastLeaveDate.getDay() <= 5) {
            let startedAfternoonOnLastDay = (lastLeaveDate.getTime() === (new Date(parts[0], parts[1] - 1, parts[2])).getTime()) && (startSession === 'afternoon');
            if (lastDayValConsumed === 0.5 && !startedAfternoonOnLastDay) endsMidDay = true;
        }

        if (!endsMidDay) {
            returnDate.setDate(returnDate.getDate() + 1);
            while (returnDate.getDay() === 0) {
                returnDate.setDate(returnDate.getDate() + 1);
            }
        }

        const dd = String(returnDate.getDate()).padStart(2, '0');
        const mm = String(returnDate.getMonth() + 1).padStart(2, '0');
        const yyyy = returnDate.getFullYear();
        let sessionStr = endsMidDay ? '(Buổi chiều)' : '(Buổi sáng)';
        $('#quick_return_date_display').val(`${dd}/${mm}/${yyyy} ${sessionStr}`);
    }

    $('#quick_start_session_leave').on('change', calculateQuickReturnDate);

    $('#quick_leave_days').on('input', function() {
        calculateQuickReturnDate();
        validateQuickLeaveBalance();
    });

    $('#quick_btn_minus_leave').on('click', function() {
        let val = parseFloat($('#quick_leave_days').val()) || 1;
        if (val > 0.5) {
            $('#quick_leave_days').val(val - 0.5);
            calculateQuickReturnDate();
            validateQuickLeaveBalance();
        }
    });

    $('#quick_btn_plus_leave').on('click', function() {
        let val = parseFloat($('#quick_leave_days').val()) || 0;
        $('#quick_leave_days').val(val + 0.5);
        calculateQuickReturnDate();
        validateQuickLeaveBalance();
    });

    // Mở drawer tạo phiếu nhanh
    function openQuickRequestDrawer(btn) {
        const dateStr = btn.data('date');
        const dateVn = btn.data('date-vn');
        const weekday = btn.data('weekday');
        const status = btn.data('status');
        const checkin = btn.data('checkin');
        const checkout = btn.data('checkout');
        const dayOfWeek = parseInt(btn.data('day-of-week'));

        $('#quick-selected-date-badge').text(`${weekday}, ${dateVn}`);
        $('#quick-alert-container').empty();
        $('#quick_reason').val('');

        // Cập nhật flatpickr ngày
        if (fpDateAdj) fpDateAdj.setDate(dateStr, true);
        if (fpDateLeave) fpDateLeave.setDate(dateStr, true);
        if (fpDateOvertime) fpDateOvertime.setDate(dateStr, true);

        const busEndTime = dayOfWeek === 6 ? '12:00' : '17:30';
        if (fpStartBusiness) fpStartBusiness.setDate(`${dateStr} 08:30`, true);
        if (fpEndBusiness) fpEndBusiness.setDate(`${dateStr} ${busEndTime}`, true);

        const defaultLeaveDays = dayOfWeek === 6 ? 0.5 : 1;
        $('#quick_leave_days').val(defaultLeaveDays);

        // Tự động chọn loại phiếu và phiên thông minh theo trạng thái ngày
        if (status === 'missing' || status === 'blank') {
            $('#quick-request-type').val('attendance_adjustment');
            if (!checkin && checkout) {
                $('#quick_start_session_adj').val('morning');
            } else if (checkin && !checkout) {
                $('#quick_start_session_adj').val('afternoon');
            } else {
                $('#quick_start_session_adj').val('morning');
            }
        }

        updateQuickForm();
        calculateQuickReturnDate();
        fetchQuickLimits();

        const offcanvasEl = document.getElementById('quickRequestOffcanvas');
        const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
        bsOffcanvas.show();
    }

    // Chỉ mở drawer tạo phiếu khi click vào nút làm phiếu
    $(document).on('click', '.btn-quick-request', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openQuickRequestDrawer($(this));
    });

    // Submit form tạo phiếu bằng AJAX
    $('#quick-request-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = $('#quick-btn-submit');
        const spinner = $('#quick-submit-spinner');
        const icon = $('#quick-submit-icon');

        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        icon.addClass('d-none');
        $('#quick-alert-container').empty();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                let errorMessages = [];
                if (data.errors) {
                    Object.values(data.errors).forEach(errArr => {
                        if (Array.isArray(errArr)) {
                            errorMessages.push(...errArr);
                        } else if (typeof errArr === 'string') {
                            errorMessages.push(errArr);
                        }
                    });
                }
                if (errorMessages.length === 0 && data.message) {
                    errorMessages.push(data.message);
                }
                if (errorMessages.length === 0) {
                    errorMessages.push('Đã xảy ra lỗi khi tạo phiếu. Vui lòng kiểm tra lại thông tin.');
                }
                throw new Error(errorMessages.join('<br>'));
            }
            return data;
        })
        .then(data => {
            const offcanvasEl = document.getElementById('quickRequestOffcanvas');
            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (bsOffcanvas) bsOffcanvas.hide();

            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: data.message || 'Đã tạo phiếu thành công.',
                confirmButtonColor: '#405189',
                confirmButtonText: 'Đóng'
            }).then(() => {
                window.location.reload();
            });
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Không thể tạo phiếu',
                html: err.message || 'Đã có lỗi xảy ra',
                confirmButtonColor: '#405189',
            });
        })
        .finally(() => {
            submitBtn.prop('disabled', false);
            spinner.addClass('d-none');
            icon.removeClass('d-none');
        });
    });
});
</script>
@endpush
