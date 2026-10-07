@extends('backend.layouts.app')
@section('title', 'Xuất công')
@section('page_title', 'Bảng xuất công')
@section('content')

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3 align-items-end">
            <div class="col-md-3">
                <label for="attendance-q" class="form-label">Nhân viên</label>
                <input id="attendance-q" name="q" class="form-control" placeholder="Mã hoặc tên nhân viên" value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <label for="attendance-department" class="form-label">Phòng ban</label>
                <select id="attendance-department" class="form-select" name="department">
                    <option value="">Tất cả phòng ban</option>
                    @foreach($departments as $id => $department)
                        <option value="{{ $id }}" @selected(request('department') == $id)>{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="attendance-date-range" class="form-label">Thời gian</label>
                <input id="attendance-date-range" class="form-control" type="text" value="" placeholder="Chọn khoảng thời gian" autocomplete="off">
            </div>
            <input type="hidden" name="from" id="attendance-from" value="{{ $from }}">
            <input type="hidden" name="to" id="attendance-to" value="{{ $to }}">
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-secondary w-100">Lọc dữ liệu</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive" style="max-height: 70vh;">
            <table class="table table-bordered table-nowrap align-middle table-sm" style="font-size: 13px;">
                <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th rowspan="2" class="text-center align-middle" style="position: sticky; left: 0; background-color: #f3f6f9; z-index: 2;">Mã NV<br>DXMB</th>
                        <th rowspan="2" class="text-center align-middle" style="position: sticky; left: 80px; background-color: #f3f6f9; z-index: 2;">HỌ TÊN</th>
                        <th rowspan="2" class="text-center align-middle">PHÒNG BAN</th>
                        <th rowspan="2" class="text-center align-middle">TÌNH<br>TRẠNG</th>
                        <th rowspan="2" class="text-center align-middle">Ngày vào làm</th>
                        <th rowspan="2" class="text-center align-middle">Ngày hết thử<br>việc</th>
                        <th colspan="{{ count($dates) }}" class="text-center">THỨ/NGÀY</th>
                        <th rowspan="2" class="text-center align-middle text-wrap" style="min-width: 80px">Tổng công<br>tính lương</th>
                    </tr>
                    <tr>
                        @foreach($dates as $dateStr)
                            @php
                                $d = \Carbon\Carbon::parse($dateStr);
                                $dayOfWeek = $d->dayOfWeek;
                                $dayLabels = ["CN", "T2", "T3", "T4", "T5", "T6", "T7"];
                                $isSunday = $dayOfWeek == 0;
                            @endphp
                            <th class="text-center p-1 {{ $isSunday ? 'bg-primary text-white' : '' }}">
                                <div class="mb-1 border-bottom border-light pb-1">{{ $d->format('d/m') }}</div>
                                <div class="{{ $isSunday ? 'text-white' : 'text-primary' }} fw-bold">{{ $dayLabels[$dayOfWeek] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $emp)
                        <tr>
                            <td class="text-center fw-medium" style="position: sticky; left: 0; background-color: #fff; z-index: 1;">{{ $emp['employee_code'] }}</td>
                            <td style="position: sticky; left: 80px; background-color: #fff; z-index: 1; min-width: 150px;">{{ $emp['name'] }}</td>
                            <td>{{ $emp['department_name'] }}</td>
                            <td class="text-center">
                                @php
                                    $statusMap = [
                                        'active' => 'CT',
                                        'probation' => 'HTV',
                                        'collaborator' => 'CTV',
                                        'ctv' => 'CTV',
                                        'freelancer' => 'CTV'
                                    ];
                                @endphp
                                {{ $statusMap[$emp['status']] ?? strtoupper($emp['status']) }}
                            </td>
                            <td class="text-center">{{ $emp['join_date'] }}</td>
                            <td class="text-center">{{ $emp['probation_end'] }}</td>
                            
                            @foreach($dates as $dateStr)
                                @php
                                    $dayData = $emp['days'][$dateStr];
                                @endphp
                                <td class="text-center {{ $dayData['is_sunday'] ? 'bg-primary bg-opacity-10' : '' }} {{ $dayData['value'] === 'P' ? 'bg-warning bg-opacity-25' : '' }}">
                                    {{ $dayData['value'] }}
                                </td>
                            @endforeach
                            
                            <td class="text-center fw-bold">{{ number_format($emp['total_hours'] / 8, 2, '.', '') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 7 + count($dates) }}" class="text-center text-muted py-4">Chưa có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    /* Make table headers look more like the Excel screenshot */
    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }
    .table thead th {
        border-bottom-width: 1px;
    }
</style>
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
        startDate: start || moment().subtract(1, 'month').date(16),
        endDate: end || moment().date(15),
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
});
</script>
@endpush
