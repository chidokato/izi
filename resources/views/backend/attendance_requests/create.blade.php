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
                    
                    <div class="mb-3">
                        <label class="form-label">Nhân viên <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Chọn nhân viên</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại phiếu <span class="text-danger">*</span></label>
                        <select name="type" id="request-type" class="form-control" required>
                            <option value="paid_leave">Nghỉ phép năm</option>
                            <option value="unpaid_leave">Nghỉ không lương</option>
                            <option value="business_trip">Phiếu công tác</option>
                            <option value="attendance_adjustment">Bổ sung công (quên chấm)</option>
                            <option value="overtime">Làm tăng ca</option>
                        </select>
                        <div id="adjustment-limit-info" class="text-info mt-1" style="display: none; font-size: 0.875rem;">
                            <i class="ri-information-line align-middle"></i> Số phiếu quên chấm còn lại trong tháng: <strong id="limit-count">.../3</strong>
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

                    <button type="submit" class="btn btn-primary">Lưu phiếu</button>
                    <a href="{{ route('backend.attendance-requests.index') }}" class="btn btn-light">Hủy</a>
                </form>
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
            onChange: function() {
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
        const employeeSelect = document.querySelector('select[name="employee_id"]');
        
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
        
        const limitInfo = document.getElementById('adjustment-limit-info');
        const limitCount = document.getElementById('limit-count');

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

        function checkAdjustmentLimit() {
            if (typeSelect.value !== 'attendance_adjustment' || !employeeSelect.value) {
                return;
            }
            const date = startDateAdjInput.value;
            fetch(`{{ route('backend.attendance-requests.check-limit') }}?employee_id=${employeeSelect.value}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    limitCount.innerText = `${data.remaining}/${data.total}`;
                    if (data.remaining <= 0) {
                        limitInfo.classList.remove('text-info');
                        limitInfo.classList.add('text-danger');
                        limitInfo.innerHTML = `<i class="ri-error-warning-line align-middle"></i> Nhân viên này đã sử dụng hết 3 phiếu quên chấm trong tháng ${data.month}.`;
                    } else {
                        limitInfo.classList.remove('text-danger');
                        limitInfo.classList.add('text-info');
                        limitInfo.innerHTML = `<i class="ri-information-line align-middle"></i> Số phiếu quên chấm còn lại trong tháng ${data.month}: <strong>${data.remaining}/${data.total}</strong>`;
                    }
                });
        }

        function updateForm() {
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
                checkAdjustmentLimit();
                
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
                limitInfo.style.display = 'none';
                
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
        employeeSelect.addEventListener('change', checkAdjustmentLimit);
        startDateAdjInput.addEventListener('change', checkAdjustmentLimit);
        
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
        
        leaveDaysInput.addEventListener('input', calculateReturnDate);

        document.getElementById('btn-minus-leave').addEventListener('click', function() {
            let val = parseFloat(leaveDaysInput.value) || 0;
            if (val > 0.5) {
                leaveDaysInput.value = val - 0.5;
                calculateReturnDate();
            }
        });

        document.getElementById('btn-plus-leave').addEventListener('click', function() {
            let val = parseFloat(leaveDaysInput.value) || 0;
            leaveDaysInput.value = val + 0.5;
            calculateReturnDate();
        });

        updateForm();
    });
</script>
@endpush
