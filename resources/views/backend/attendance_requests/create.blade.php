@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Tạo phiếu mới</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('backend.attendance-requests.store') }}" method="POST">
                    @csrf
                    
                    @if($errors->any())
                        <div class="alert alert-danger d-none" id="server-errors-data">
                            @foreach($errors->all() as $err)
                                <div class="server-error-item">{{ $err }}</div>
                            @endforeach
                        </div>
                    @endif
                    
                    @if(auth()->check() && auth()->user()->isAdmin())
                    <div class="mb-3">
                        <label class="form-label">Nhân viên <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Chọn nhân viên</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-manager="{{ $emp->manager ? $emp->manager->name : 'Ban giám đốc' }}" {{ old('employee_id', auth()->user()->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->name }} ({{ $emp->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        @if(!auth()->user()->employee_id)
                            <div class="alert alert-warning">
                                <strong>Lưu ý:</strong> Tài khoản của bạn chưa được liên kết với hồ sơ nhân viên trên hệ thống. Vui lòng liên hệ Quản trị viên để được thiết lập trước khi tạo phiếu.
                            </div>
                        @endif
                        <input type="hidden" name="employee_id" value="{{ auth()->user()->employee_id }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Loại phiếu <span class="text-danger">*</span></label>
                        <select name="type" id="request-type" class="form-control" required>
                            <option value="unpaid_leave" {{ old('type', 'unpaid_leave') == 'unpaid_leave' ? 'selected' : '' }}>Nghỉ không lương</option>
                            <option value="paid_leave" {{ old('type') == 'paid_leave' ? 'selected' : '' }}>Nghỉ phép năm</option>
                            <option value="business_trip" {{ old('type') == 'business_trip' ? 'selected' : '' }}>Phiếu công tác</option>
                            <option value="attendance_adjustment" {{ old('type') == 'attendance_adjustment' ? 'selected' : '' }}>Bổ sung công (quên chấm)</option>
                            <option value="overtime" {{ old('type') == 'overtime' ? 'selected' : '' }}>Làm tăng ca</option>
                        </select>
                        <div id="limit-info" class="text-info mt-1" style="display: none; font-size: 0.875rem;">
                            <i class="ri-information-line align-middle"></i>
                        </div>
                    </div>

                    <!-- GIAO DIỆN: NGHỈ PHÉP -->
                    <div id="group-leave" class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày xin nghỉ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="start_date_leave" class="form-control date-picker" placeholder="Chọn ngày">
                                <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Buổi <span class="text-danger">*</span></label>
                            <select name="start_session_leave" class="form-control" id="start_session_leave" disabled>
                                <option value="morning">Sáng</option>
                                <option value="afternoon">Chiều</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Số ngày nghỉ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary" type="button" id="btn-minus-leave" disabled><i class="ri-subtract-line"></i></button>
                                <input type="number" name="leave_days" id="leave_days" class="form-control text-center" step="0.5" min="0.5" value="0.5" placeholder="Ví dụ: 0.5, 1, 1.5..." disabled>
                                <button class="btn btn-outline-secondary" type="button" id="btn-plus-leave" disabled><i class="ri-add-line"></i></button>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Ngày đi làm</label>
                            <input type="text" id="return_date_display" class="form-control bg-light" readonly placeholder="Tự động tính toán...">
                        </div>
                    </div>

                    <!-- GIAO DIỆN: BỔ SUNG CÔNG -->
                    <div id="group-adjustment" class="row" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày bổ sung <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="start_date_adj" class="form-control date-picker" placeholder="Chọn ngày">
                                <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Xác nhận công ra/vào <span class="text-danger">*</span></label>
                            <select name="start_session_adj" class="form-control">
                                <option value="morning">Vào</option>
                                <option value="afternoon">Ra</option>
                            </select>
                        </div>
                    </div>

                    <!-- GIAO DIỆN: CÔNG TÁC -->
                    <div id="group-business" class="row" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Từ thời gian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="start_date_business" class="form-control datetime-picker" placeholder="Chọn ngày giờ">
                                <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Đến thời gian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="end_date_business" class="form-control datetime-picker" placeholder="Chọn ngày giờ">
                                <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                            </div>
                        </div>
                    </div>

                    <!-- GIAO DIỆN: TĂNG CA -->
                    <div id="group-overtime" class="row" style="display: none;">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ngày tăng ca <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="date_overtime" class="form-control date-picker" placeholder="Chọn ngày">
                                <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="start_time_overtime" class="form-control time-picker" placeholder="Chọn giờ">
                                <span class="input-group-text"><i class="ri-time-line"></i></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giờ kết thúc <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="end_time_overtime" class="form-control time-picker" placeholder="Chọn giờ">
                                <span class="input-group-text"><i class="ri-time-line"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lý do / Diễn giải <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    @if(!auth()->user()->isAdmin() && !auth()->user()->employee_id)
                        <button type="button" class="btn btn-primary" id="btn-submit" disabled>Lưu phiếu</button>
                    @else
                        <button type="submit" class="btn btn-primary" id="btn-submit">Lưu phiếu</button>
                    @endif
                    <a href="{{ route('backend.attendance-requests.index') }}" class="btn btn-light">Hủy</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header align-items-center d-flex border-bottom-dashed">
                <h4 class="card-title mb-0 flex-grow-1">Quy trình duyệt</h4>
            </div>
            <div class="card-body">
                <div class="profile-timeline">
                    <div class="accordion accordion-flush" id="accordionFlushExample">
                        <div class="accordion-item border-0">
                            <div class="accordion-header" id="headingOne">
                                <a class="accordion-button p-2 shadow-none text-muted" data-bs-toggle="collapse" href="#collapseOne" aria-expanded="true">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 avatar-xs">
                                            <div class="avatar-title bg-primary rounded-circle">
                                                <i class="ri-user-2-line"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-14 mb-0 fw-semibold">
                                                Bước 1: Quản lý trực tiếp
                                            </h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body pt-0" style="border-left: 2px dashed #ced4da; margin-left: 23px; padding-left: 16px;">
                                    <h6 class="text-primary mb-1">
                                        Người duyệt: 
                                        @if(!auth()->user()->isAdmin() && $currentUserEmployee)
                                            <strong class="text-decoration-underline">{{ $currentUserEmployee->manager ? $currentUserEmployee->manager->name : 'Ban giám đốc' }}</strong>
                                        @elseif(auth()->user()->isAdmin())
                                            <strong class="text-decoration-underline" id="manager-name-display"></strong>
                                        @endif
                                    </h6>
                                    <p class="mb-0 mt-2 text-muted">Duyệt phiếu, kiểm tra tính hợp lý của yêu cầu xin nghỉ/công tác.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0">
                            <div class="accordion-header" id="headingTwo">
                                <a class="accordion-button p-2 shadow-none text-muted" data-bs-toggle="collapse" href="#collapseTwo" aria-expanded="true">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 avatar-xs">
                                            <div class="avatar-title bg-success rounded-circle">
                                                <i class="ri-team-line"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="fs-14 mb-0 fw-semibold">Bước 2: Hành chính - Nhân sự</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo" data-bs-parent="#accordionFlushExample">
                                <div class="accordion-body pt-0" style="margin-left: 23px; padding-left: 16px;">
                                    <p class="mb-0 text-muted">Duyệt cuối, xác nhận lưu hệ thống và tính công/trừ phép.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .select2-container .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 0.25rem; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .flatpickr-calendar {
        box-shadow: 0 5px 20px rgba(0,0,0,0.1) !important;
        border: 0 !important;
        border-radius: 8px !important;
        padding-bottom: 10px;
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
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

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<script>
    $(document).ready(function() {
        $('select[name="employee_id"]').select2({
            placeholder: "Chọn nhân viên",
            allowClear: false,
            width: '100%'
        });
        
        // Trigger native change event when Select2 changes
        $('select[name="employee_id"]').on('select2:select', function (e) {
            this.dispatchEvent(new Event('change'));
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Init flatpickr for Business Trip
        const endDateBusiness = flatpickr("input[name='end_date_business']", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "d/m/Y H:i",
            time_24hr: true,
            locale: "vn",
            minuteIncrement: 5
        });

        flatpickr("input[name='start_date_business']", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "d/m/Y H:i",
            time_24hr: true,
            locale: "vn",
            minuteIncrement: 5,
            onChange: function(selectedDates, dateStr, instance) {
                if(selectedDates.length > 0) {
                    endDateBusiness.set('minDate', selectedDates[0]);
                }
            }
        });

        // Init flatpickr for Leave
        flatpickr("input[name='start_date_leave']", {
            enableTime: false,
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            locale: "vn",
            onChange: function(selectedDates, dateStr, instance) {
                const hasDate = !!dateStr;
                const startSessionLeaveSelect = document.getElementById('start_session_leave');
                const leaveDaysInput = document.querySelector('input[name="leave_days"]');
                if (startSessionLeaveSelect) startSessionLeaveSelect.disabled = !hasDate;
                if (leaveDaysInput) leaveDaysInput.disabled = !hasDate;
                const btnMinus = document.getElementById('btn-minus-leave');
                const btnPlus = document.getElementById('btn-plus-leave');
                if (btnMinus) btnMinus.disabled = !hasDate;
                if (btnPlus) btnPlus.disabled = !hasDate;

                if (!hasDate && startSessionLeaveSelect) {
                    startSessionLeaveSelect.value = 'morning';
                }
                calculateReturnDate();
            }
        });

        // Init flatpickr for Adjustment
        flatpickr("input[name='start_date_adj']", {
            enableTime: false,
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            locale: "vn",
            onChange: function() {
                checkAdjustmentLimit();
            }
        });

        // Init flatpickr for Overtime
        flatpickr("input[name='date_overtime']", {
            enableTime: false,
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            locale: "vn"
        });

        flatpickr("input[name='start_time_overtime']", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            locale: "vn",
            minuteIncrement: 5
        });

        flatpickr("input[name='end_time_overtime']", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            locale: "vn",
            minuteIncrement: 5
        });

        // Form logic
        const typeSelect = document.getElementById('request-type');
        const employeeSelect = document.querySelector('[name="employee_id"]');
        
        // Leave Group
        const groupLeave = document.getElementById('group-leave');
        const startDateLeaveInput = document.querySelector('input[name="start_date_leave"]');
        const startSessionLeaveSelect = document.getElementById('start_session_leave');
        const leaveDaysInput = document.querySelector('input[name="leave_days"]');
        const returnDateDisplay = document.getElementById('return_date_display');
        
        // Adjustment Group
        const groupAdjustment = document.getElementById('group-adjustment');
        const startDateAdjInput = document.querySelector('input[name="start_date_adj"]');
        
        // Business Group
        const groupBusiness = document.getElementById('group-business');
        
        // Overtime Group
        const groupOvertime = document.getElementById('group-overtime');
        
        const limitInfo = document.getElementById('limit-info');

        function calculateReturnDate() {
            if (typeSelect.value === 'business_trip' || typeSelect.value === 'attendance_adjustment' || typeSelect.value === 'overtime') {
                return;
            }

            const startDateStr = startDateLeaveInput.value;
            const startSession = startSessionLeaveSelect.value;
            const daysToTake = parseFloat(leaveDaysInput.value);

            if (!startDateStr || isNaN(daysToTake) || daysToTake <= 0) {
                returnDateDisplay.value = '';
                return;
            }

            const dateParts = startDateStr.split('-');
            let current = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            current.setHours(0,0,0,0);
            
            let daysAccumulated = 0;
            let lastLeaveDate = new Date(current);
            let isFirstDay = true;
            let lastDayValConsumed = 0;

            // Calculate the last day of leave
            while (daysAccumulated < daysToTake) {
                const dayOfWeek = current.getDay();
                let dailyValue = 0;
                
                if (dayOfWeek >= 1 && dayOfWeek <= 5) { // Mon-Fri
                    dailyValue = 1;
                } else if (dayOfWeek === 6) { // Saturday
                    dailyValue = 0.5;
                } // Sunday is 0

                if (isFirstDay && startSession === 'afternoon' && dailyValue === 1) {
                    dailyValue = 0.5; // Only taking the afternoon
                } else if (isFirstDay && startSession === 'afternoon' && dailyValue === 0.5) {
                    dailyValue = 0; // Saturday afternoon is 0
                }

                if (dailyValue > 0) {
                    if (daysAccumulated + dailyValue > daysToTake) {
                        // Takes fraction of the day
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

            // Return date calculation
            let returnDate = new Date(lastLeaveDate);
            let endsMidDay = false;
            
            // If they consumed only 0.5 on the last day, and that day normally has 1.0 value
            // (or if it's the first day and they started in the morning and consumed 0.5)
            // Wait, if lastDayValConsumed is 0.5 on a weekday, they end their leave at midday.
            if (lastLeaveDate.getDay() >= 1 && lastLeaveDate.getDay() <= 5) {
                let startedAfternoonOnLastDay = (lastLeaveDate.getTime() === (new Date(dateParts[0], dateParts[1] - 1, dateParts[2])).getTime()) && (startSession === 'afternoon');
                if (lastDayValConsumed === 0.5 && !startedAfternoonOnLastDay) {
                    endsMidDay = true;
                }
            }

            if (!endsMidDay) {
                returnDate.setDate(returnDate.getDate() + 1);
                // Find next working day
                while (returnDate.getDay() === 0 || returnDate.getDay() === 6) { 
                    if (returnDate.getDay() === 6) { // return Saturday morning
                        break;
                    }
                    returnDate.setDate(returnDate.getDate() + 1);
                }
            }

            const dd = String(returnDate.getDate()).padStart(2, '0');
            const mm = String(returnDate.getMonth() + 1).padStart(2, '0');
            const yyyy = returnDate.getFullYear();
            
            let returnSessionStr = endsMidDay ? '(Buổi chiều)' : '(Buổi sáng)';
            returnDateDisplay.value = `${dd}/${mm}/${yyyy} ${returnSessionStr}`;
        }

        const isOrphanUser = {{ (!auth()->user()->isAdmin() && !auth()->user()->employee_id) ? 'true' : 'false' }};
        function setSubmitButtonState(isDisabled) {
            const btnSubmit = document.getElementById('btn-submit');
            if (btnSubmit) {
                btnSubmit.disabled = isOrphanUser ? true : isDisabled;
            }
        }

        let currentLeaveBalance = 0;

        function fetchLimits() {
            if (!employeeSelect.value) return;
            const date = startDateAdjInput.value || (new Date().toISOString().split('T')[0]);
            fetch(`{{ route('backend.attendance-requests.check-limit') }}?employee_id=${employeeSelect.value}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (typeSelect.value === 'attendance_adjustment') {
                        if (data.remaining <= 0) {
                            limitInfo.style.display = 'block';
                            limitInfo.classList.remove('text-info');
                            limitInfo.classList.add('text-danger');
                            limitInfo.innerHTML = `<i class="ri-error-warning-line align-middle"></i> Nhân viên này đã sử dụng hết 3 phiếu quên chấm trong tháng ${data.month}.`;
                            
                            setSubmitButtonState(true);
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Đã hết lượt bổ sung công',
                                text: `Nhân viên này đã sử dụng hết 3 phiếu quên chấm trong tháng ${data.month}. Không thể làm thêm phiếu này.`,
                                confirmButtonColor: '#0ab39c',
                            });
                        } else {
                            limitInfo.style.display = 'block';
                            limitInfo.classList.remove('text-danger');
                            limitInfo.classList.add('text-info');
                            limitInfo.innerHTML = `<i class="ri-information-line align-middle"></i> Số phiếu quên chấm còn lại trong tháng ${data.month}: <strong>${data.remaining}/${data.total}</strong>`;
                            
                            setSubmitButtonState(false);
                        }
                    } else if (typeSelect.value === 'paid_leave') {
                        currentLeaveBalance = parseFloat(data.leave_balance) || 0;
                        limitInfo.style.display = 'block';
                        limitInfo.classList.remove('text-danger');
                        limitInfo.classList.add('text-info');
                        limitInfo.innerHTML = `<i class="ri-information-line align-middle"></i> Số phép năm còn lại: <strong>${currentLeaveBalance} ngày</strong>`;
                        validateLeaveBalance();
                    }
                });
        }

        function validateLeaveBalance() {
            if (typeSelect.value !== 'paid_leave') return;
            const requestedDays = parseFloat(leaveDaysInput.value) || 0;
            if (requestedDays > currentLeaveBalance) {
                limitInfo.classList.remove('text-info');
                limitInfo.classList.add('text-danger');
                limitInfo.innerHTML = `<i class="ri-error-warning-line align-middle"></i> Số phép năm còn lại (${currentLeaveBalance} ngày) không đủ cho yêu cầu này (${requestedDays} ngày).`;
                setSubmitButtonState(true);
                Swal.fire({
                    icon: 'error',
                    title: 'Không đủ phép năm',
                    text: `Nhân viên này chỉ còn ${currentLeaveBalance} ngày phép năm, nhưng đang xin nghỉ ${requestedDays} ngày. Không thể tạo phiếu.`,
                    confirmButtonColor: '#0ab39c',
                });
            } else {
                limitInfo.classList.remove('text-danger');
                limitInfo.classList.add('text-info');
                limitInfo.innerHTML = `<i class="ri-information-line align-middle"></i> Số phép năm còn lại: <strong>${currentLeaveBalance} ngày</strong>`;
                setSubmitButtonState(false);
            }
        }

        function updateForm() {
            setSubmitButtonState(false);
            const type = typeSelect.value;
            
            if (type === 'business_trip') {
                groupLeave.style.display = 'none';
                groupAdjustment.style.display = 'none';
                groupOvertime.style.display = 'none';
                groupBusiness.style.display = 'flex';
                limitInfo.style.display = 'none';
                
                document.querySelectorAll('#group-leave input, #group-leave select').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-adjustment input, #group-adjustment select').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-overtime input').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-business input').forEach(el => el.disabled = false);
            } else if (type === 'attendance_adjustment') {
                groupLeave.style.display = 'none';
                groupAdjustment.style.display = 'flex';
                groupBusiness.style.display = 'none';
                groupOvertime.style.display = 'none';
                limitInfo.style.display = 'block';
                fetchLimits();
                
                document.querySelectorAll('#group-business input').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-leave input, #group-leave select').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-overtime input').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-adjustment input, #group-adjustment select').forEach(el => el.disabled = false);
            } else if (type === 'overtime') {
                groupLeave.style.display = 'none';
                groupAdjustment.style.display = 'none';
                groupBusiness.style.display = 'none';
                groupOvertime.style.display = 'flex';
                limitInfo.style.display = 'none';
                
                document.querySelectorAll('#group-business input').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-adjustment input, #group-adjustment select').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-leave input, #group-leave select').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-overtime input').forEach(el => el.disabled = false);
            } else { // paid_leave, unpaid_leave, etc.
                groupLeave.style.display = 'flex';
                groupAdjustment.style.display = 'none';
                groupBusiness.style.display = 'none';
                groupOvertime.style.display = 'none';
                if (type === 'paid_leave') {
                    limitInfo.style.display = 'block';
                    fetchLimits();
                } else {
                    limitInfo.style.display = 'none';
                }
                
                document.querySelectorAll('#group-business input').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-adjustment input, #group-adjustment select').forEach(el => el.disabled = true);
                document.querySelectorAll('#group-overtime input').forEach(el => el.disabled = true);
                
                // For leave group, only enable start_date_leave unconditionally.
                // Session and leave_days depend on sequential logic.
                document.querySelector('input[name="start_date_leave"]').disabled = false;
                
                // Evaluate sequential logic
                if (startDateLeaveInput.value) {
                    startSessionLeaveSelect.disabled = false;
                    leaveDaysInput.disabled = false;
                    document.getElementById('btn-minus-leave').disabled = false;
                    document.getElementById('btn-plus-leave').disabled = false;
                } else {
                    startSessionLeaveSelect.disabled = true;
                    leaveDaysInput.disabled = true;
                    document.getElementById('btn-minus-leave').disabled = true;
                    document.getElementById('btn-plus-leave').disabled = true;
                }

                calculateReturnDate();
            }
        }

        typeSelect.addEventListener('change', updateForm);
        
        function updateManagerDisplay() {
            if (employeeSelect && employeeSelect.tagName === 'SELECT') {
                const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
                const managerDisplay = document.getElementById('manager-name-display');
                if (managerDisplay && selectedOption && selectedOption.value) {
                    managerDisplay.textContent = selectedOption.getAttribute('data-manager') || 'Ban giám đốc';
                } else if (managerDisplay) {
                    managerDisplay.textContent = '';
                }
            }
        }
        
        if (employeeSelect) {
            employeeSelect.addEventListener('change', () => {
                fetchLimits();
                updateManagerDisplay();
            });
            // Initial call
            updateManagerDisplay();
        }

        if (startDateAdjInput) {
            startDateAdjInput.addEventListener('change', fetchLimits);
        }
        
        startDateLeaveInput.addEventListener('change', () => {
            const hasDate = !!startDateLeaveInput.value;
            startSessionLeaveSelect.disabled = !hasDate;
            leaveDaysInput.disabled = !hasDate;
            document.getElementById('btn-minus-leave').disabled = !hasDate;
            document.getElementById('btn-plus-leave').disabled = !hasDate;
            
            if (!hasDate) {
                startSessionLeaveSelect.value = 'morning';
            }
            calculateReturnDate();
        });
        
        startSessionLeaveSelect.addEventListener('change', () => {
            calculateReturnDate();
        });
        
        leaveDaysInput.addEventListener('input', () => {
            calculateReturnDate();
            validateLeaveBalance();
        });

        document.getElementById('btn-minus-leave').addEventListener('click', function() {
            try {
                leaveDaysInput.stepDown();
            } catch(e) {
                let valStr = leaveDaysInput.value.replace(',', '.');
                let val = parseFloat(valStr) || 0;
                if (val > 0.5) leaveDaysInput.value = val - 0.5;
            }
            leaveDaysInput.dispatchEvent(new Event('input'));
        });

        document.getElementById('btn-plus-leave').addEventListener('click', function() {
            try {
                leaveDaysInput.stepUp();
            } catch(e) {
                let valStr = leaveDaysInput.value.replace(',', '.');
                let val = parseFloat(valStr) || 0;
                leaveDaysInput.value = val + 0.5;
            }
            leaveDaysInput.dispatchEvent(new Event('input'));
        });

        updateForm();

        // Show server errors as SweetAlert
        const errorData = document.getElementById('server-errors-data');
        if (errorData) {
            const items = errorData.querySelectorAll('.server-error-item');
            if (items.length > 0) {
                let errorHtml = '<ul class="text-start mb-0">';
                items.forEach(item => {
                    errorHtml += `<li>${item.textContent}</li>`;
                });
                errorHtml += '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Đã có lỗi xảy ra',
                    html: errorHtml,
                    confirmButtonText: 'Đóng'
                });
            }
        }
    });
</script>
@endpush
