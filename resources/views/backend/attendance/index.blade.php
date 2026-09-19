@extends('backend.layouts.app')
@section('title', 'Chấm công')
@section('page_title', 'Chấm công — giờ vào / ra')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">Nhập file chấm công Excel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('backend.attendance.preview') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="modal-body">
              <p class="text-muted small">Chỉ nhập mã nhân viên, tên, phòng ban, ngày, thứ, giờ vào và giờ ra. Các cột khác được bỏ qua. Tối đa 5 MB và 2.000 dòng dữ liệu.</p>
              <div class="mb-3">
                  <label class="form-label" for="file">File Excel (.xlsx, .xls) <span class="text-danger">*</span></label>
                  <input id="file" class="form-control" type="file" name="file" accept=".xlsx,.xls" required>
              </div>
              <div class="mb-3">
                  <label class="form-label" for="date-format">Định dạng ngày trong file</label>
                  <select class="form-select" id="date-format" name="date_format">
                      <option value="m/d/Y" @selected(old('date_format') !== 'd/m/Y')>Tháng/ngày/năm — 9/3/2026 là 03/09/2026</option>
                      <option value="d/m/Y" @selected(old('date_format') === 'd/m/Y')>Ngày/tháng/năm — 3/9/2026 là 03/09/2026</option>
                  </select>
              </div>
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary">Đọc file và xem trước</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="card">
    <div class="card-header border-0 d-flex justify-content-between align-items-center pb-0">
        <h5 class="mb-0">Dữ liệu đã nhập</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
            Nhập file Excel
        </button>
    </div>
    <div class="card-body">
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><label for="attendance-q" class="form-label">Nhân viên</label><input id="attendance-q" name="q" class="form-control" placeholder="Mã hoặc tên nhân viên" value="{{ request('q') }}"></div>
    <div class="col-md-3"><label for="attendance-department" class="form-label">Phòng ban</label><select id="attendance-department" class="form-select" name="department"><option value="">Tất cả phòng ban</option>@foreach($departments as $department)<option @selected(request('department')===$department)>{{ $department }}</option>@endforeach</select></div>
    <div class="col-md-4"><label for="attendance-date-range" class="form-label">Thời gian</label><input id="attendance-date-range" class="form-control" type="text" value="" placeholder="Chọn khoảng thời gian" autocomplete="off"></div>
    <input type="hidden" name="from" id="attendance-from" value="{{ request('from') }}">
    <input type="hidden" name="to" id="attendance-to" value="{{ request('to') }}">
    <div class="col-md-2 d-flex align-items-end justify-content-end gap-2"><button class="btn btn-secondary">Lọc / Tính</button><a class="btn btn-light" href="{{ route('backend.attendance.index') }}">Bỏ lọc</a></div>
</form>
<!-- <p class="text-muted small">Công hiển thị bằng giờ thập phân (7,50 = 7 giờ 30 phút). Chỉ trừ phần giờ nghỉ nằm trong thời gian có mặt. Đi muộn/về sớm so với giờ hành chính, lấy số phút tròn; không áp dụng dung sai. Ưu tiên lịch nhân viên có hiệu lực trong ngày; nếu chưa gán, dùng lịch đối chiếu đã chọn. Thiếu giờ hoặc chưa có lịch sẽ hiển thị "—" ở chỉ số chưa tính được. Đây là kết quả đối chiếu, chưa ghi vào bảng công chốt.</p> -->
<div class="table-responsive"><table class="table table-striped table-nowrap"><thead><tr><th>Mã NV</th><th>Tên nhân viên</th><th>Phòng ban</th><th>Ngày</th><th>Thứ</th><th>Giờ vào</th><th>Giờ ra</th><th>Công (giờ)</th><th>Đi muộn (phút)</th><th>Về sớm (phút)</th><th>Kiểm tra</th></tr></thead><tbody>
@forelse($entries as $entry)
@php
    $hours = $entry->metrics['regular_hours'] ?? null;
    if (isset($entry->request) && $entry->request->type == 'paid_leave') {
        $isHalfDay = ($entry->request->start_session == 'afternoon' && substr($entry->request->start_date,0,10) == $entry->work_date) || 
                     ($entry->request->end_session == 'morning' && substr($entry->request->end_date,0,10) == $entry->work_date);
        $hours = ($hours ?? 0) + ($isHalfDay ? 4 : 8);
        if ($hours > 8) $hours = 8;
    } elseif (isset($entry->request) && $entry->request->type == 'business_trip') {
        $hours = ($entry->metrics['total_hours'] ?? 0) + ($entry->business_hours ?? 0);
        if ($hours > 8) $hours = 8;
    }
@endphp
@php($rowClass = isset($entry->request) ? ($entry->request->type == 'attendance_adjustment' ? 'table-danger' : (($entry->request->type == 'paid_leave' || $entry->request->type == 'unpaid_leave') ? 'table-info' : ($entry->request->type == 'business_trip' ? 'table-warning' : ''))) : '')
<tr class="{{ $rowClass }}">
    <td>{{ $entry->employee_code }}</td><td>{{ $entry->employee_name }}</td><td>{{ $entry->department_name }}</td>
    <td>{{ \Carbon\Carbon::parse($entry->work_date)->format('d/m/Y') }}</td><td>{{ \App\Services\AttendanceFileReader::weekday($entry->work_date) }}</td>
    <td class="checkin-val-{{ $entry->id }}">{{ $entry->checkin !== null ? substr($entry->checkin,0,5) : '—' }}</td>
    <td>
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-light p-0 px-1 border-0 shadow-none swap-punch me-1" 
                    data-id="{{ $entry->id }}" title="Đổi giờ vào/ra">
                <i class="ri-arrow-left-right-line"></i>
            </button>
            <span class="checkout-val-{{ $entry->id }}">{{ $entry->checkout !== null ? substr($entry->checkout,0,5) : '—' }}</span>
        </div>
    </td>
    <td title="{{ $entry->calculation_note }}"><strong>{{ $hours === null ? '—' : ($hours == floor($hours) ? number_format($hours, 0) : rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',')) }}</strong></td>
    <td class="{{ ($entry->metrics['late_arrival'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $entry->metrics['late_arrival'] ?? '—' }}</td>
    <td class="{{ ($entry->metrics['early_departure'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $entry->metrics['early_departure'] ?? '—' }}</td>
    <td>
        @if(isset($entry->request))
            <span class="text-primary fw-medium"><i class="ri-file-list-3-line align-middle"></i> {{ $entry->calculation_note }}</span>
            <span class="text-muted small ms-1">
                @if($entry->request->type == 'attendance_adjustment')
                    (Xác nhận: {{ $entry->request->start_session == 'morning' ? 'Vào' : ($entry->request->start_session == 'afternoon' ? 'Ra' : 'Vào/Ra') }})
                @elseif($entry->request->type == 'business_trip')
                    ({{ isset($entry->business_hours) && $entry->business_hours < 8 ? round($entry->business_hours, 1) . ' giờ' : 'Cả ngày' }})
                @elseif($entry->request->start_session == 'afternoon' && $entry->request->start_date == $entry->work_date)
                    (Nửa buổi chiều)
                @elseif($entry->request->end_session == 'morning' && $entry->request->end_date == $entry->work_date)
                    (Nửa buổi sáng)
                @else
                    (Cả ngày)
                @endif
            </span>
        @else
            {{ !$entry->checkin && !$entry->checkout ? 'Chưa có giờ chấm' : (!$entry->checkin ? 'Thiếu giờ vào' : (!$entry->checkout ? 'Thiếu giờ ra' : ($entry->checkout < $entry->checkin ? 'Kiểm tra ca qua đêm' : 'Đủ giờ vào/ra'))) }}
        @endif
    </td>
</tr>
@empty<tr><td colspan="11" class="text-center text-muted">Chưa có dữ liệu chấm công phù hợp.</td></tr>@endforelse
</tbody></table></div>{{ $entries->links() }}</div></div>
<div class="card"><div class="card-body"><h5>10 lần nhập gần nhất của bạn</h5><div class="table-responsive"><table class="table"><thead><tr><th>File</th><th>Thời điểm</th><th>Trạng thái</th><th>Đã lưu</th><th>Trùng</th><th>Lỗi / bỏ qua</th></tr></thead><tbody>@forelse($imports as $import)<tr><td>{{ $import->filename }}</td><td>{{ $import->created_at }}</td><td>@if($import->status==='pending' && $import->expires_at > now())<a href="{{ route('backend.attendance.show',$import->id) }}">Xem trước</a>@else{{ ['pending'=>'Hết hạn','completed'=>'Đã nhập','failed'=>'Hết hạn / lỗi','processing'=>'Đang xử lý'][$import->status] }}@endif</td><td>{{ $import->success_rows }}</td><td>{{ $import->duplicate_rows }}</td><td>{{ $import->error_rows }} lỗi. {{ $import->error_message }}</td></tr>@empty<tr><td colspan="6">Chưa có lịch sử nhập.</td></tr>@endforelse</tbody></table></div></div></div>
@endsection
@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@push('scripts')
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
$(function() {
    var startVal = $('#attendance-from').val();
    var endVal = $('#attendance-to').val();
    
    var start = startVal ? moment(startVal) : null;
    var end = endVal ? moment(endVal) : null;

    $('#attendance-date-range').daterangepicker({
        startDate: start || moment().startOf('month'),
        endDate: end || moment().endOf('month'),
        autoUpdateInput: false,
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: "Áp dụng",
            cancelLabel: "Xóa",
            fromLabel: "Từ",
            toLabel: "Đến",
            customRangeLabel: "Tùy chỉnh",
            daysOfWeek: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"],
            monthNames: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"],
            firstDay: 1
        }
    });

    if (start && end) {
        $('#attendance-date-range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
    }

    $('#attendance-date-range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#attendance-from').val(picker.startDate.format('YYYY-MM-DD'));
        $('#attendance-to').val(picker.endDate.format('YYYY-MM-DD'));
    });

    $('#attendance-date-range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        $('#attendance-from').val('');
        $('#attendance-to').val('');
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
