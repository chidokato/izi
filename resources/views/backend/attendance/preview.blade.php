@extends('backend.layouts.app')
@section('title', 'Xem trước chấm công')
@section('page_title', 'Kiểm tra trước khi nhập')
@section('content')
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@php
$errorCount = count(array_filter($rows, fn($r) => !empty($r['errors'])));
$duplicates = count(array_filter($rows, fn($r) => empty($r['errors']) && $r['duplicate']));
$newEmployees = count(array_unique(array_column(array_filter($rows, fn($r) => empty($r['errors']) && $r['new_employee']), 'code')));
$changes = count(array_filter($rows, fn($r) => empty($r['errors']) && $r['old'] && !$r['duplicate']));
$validCount = count($rows) - $errorCount;

$errorTypes = [];
foreach ($rows as $r) {
    foreach ($r['errors'] as $err) {
        $errorTypes[$err] = ($errorTypes[$err] ?? 0) + 1;
    }
}

$maxDisplay = 500;
$hasMore = count($rows) > $maxDisplay;
if ($hasMore) {
    $displayRows = array_slice($rows, 0, $maxDisplay);
    if ($errorCount > 0) {
        $extraErrors = array_filter(array_slice($rows, $maxDisplay), fn($r) => !empty($r['errors']) || !empty($r['warnings']));
        $displayRows = array_merge($displayRows, $extraErrors);
    }
} else {
    $displayRows = $rows;
}
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ $batch->filename }}</h5>
            <span class="badge bg-light text-dark border fs-13">Bản xem trước hiệu lực trong 1 giờ</span>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="p-2 border rounded bg-light text-center">
                    <div class="text-muted small">Tổng số dòng trong file</div>
                    <div class="fs-18 fw-bold">{{ number_format(count($rows), 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 border rounded bg-success bg-opacity-10 text-center text-success border-success">
                    <div class="small fw-semibold">Hợp lệ (Sẽ nhập vào hệ thống)</div>
                    <div class="fs-18 fw-bold">{{ number_format($validCount, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 border rounded bg-danger bg-opacity-10 text-center text-danger border-danger">
                    <div class="small fw-semibold">Bị lỗi (Sẽ bỏ qua, không nhập)</div>
                    <div class="fs-18 fw-bold">{{ number_format($errorCount, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 border rounded bg-secondary bg-opacity-10 text-center text-secondary">
                    <div class="small">Trùng dữ liệu (Giữ nguyên)</div>
                    <div class="fs-18 fw-bold">{{ number_format($duplicates, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        @if($errorCount > 0)
            <div class="alert alert-danger py-2 mb-3">
                <div class="fw-bold mb-1"><i class="ri-error-warning-line me-1"></i> Danh sách các lỗi phát hiện (hệ thống sẽ KHÔNG nhập các dòng này):</div>
                <ul class="mb-0 ps-3 small">
                    @foreach($errorTypes as $type => $count)
                        <li><span class="fw-semibold text-danger">{{ $type }}</span>: <strong>{{ number_format($count, 0, ',', '.') }} dòng</strong></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($hasMore)
            <div class="alert alert-info py-2 small mb-3">
                <i class="ri-information-line me-1"></i> File có tổng cộng <strong>{{ number_format(count($rows), 0, ',', '.') }}</strong> dòng. Bảng xem trước bên dưới hiển thị <strong>{{ number_format(count($displayRows), 0, ',', '.') }}</strong> dòng (gồm các dòng mẫu đầu tiên và toàn bộ dòng có lỗi/cảnh báo) để trình duyệt không bị giật lag. Toàn bộ <strong>{{ number_format($validCount, 0, ',', '.') }} dòng hợp lệ</strong> sẽ được lưu đầy đủ vào hệ thống khi nhấn <strong>Xác nhận nhập</strong>.
            </div>
        @endif

        <form action="{{ route('backend.attendance.confirm', $batch->id) }}" method="post">
            @csrf
            @if($newEmployees)
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="create" name="create_employees" value="1">
                    <label class="form-check-label fw-medium text-primary" for="create">
                        Tạo {{ $newEmployees }} nhân viên mới vào danh sách nhân sự (không gắn phòng ban, không tạo tài khoản).
                    </label>
                </div>
            @endif
            @if($changes)
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="replace" name="replace_existing" value="1">
                    <label class="form-check-label fw-medium text-warning-emphasis" for="replace">
                        Cập nhật {{ $changes }} dòng thay đổi theo giờ mới bên dưới, kể cả khi giờ mới trống.
                    </label>
                </div>
            @endif
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="confirm" name="confirm" value="1" required>
                <label class="form-check-label" for="confirm">
                    Tôi đã kiểm tra dữ liệu và đồng ý nhập <strong>{{ number_format($validCount, 0, ',', '.') }} dòng hợp lệ</strong> theo lựa chọn trên.
                </label>
            </div>
            <button class="btn btn-primary" @disabled($validCount === 0)>Xác nhận nhập dữ liệu</button> 
            <a class="btn btn-light" href="{{ route('backend.attendance.index') }}">Quay lại</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive" style="max-height:650px">
            <table class="table table-bordered table-nowrap align-middle">
                <thead class="table-light" style="position:sticky;top:0;z-index:2">
                    <tr>
                        <th>Dòng</th>
                        <th>Mã NV</th>
                        <th>Tên nhân viên</th>
                        <th>Phòng ban</th>
                        <th>Ngày</th>
                        <th>Thứ</th>
                        <th>Giờ vào</th>
                        <th>Giờ ra</th>
                        <th>Trạng thái & Kết quả kiểm tra</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($displayRows as $row)
                    @php
                        $isError = !empty($row['errors']);
                        $isWarning = !$isError && !empty($row['warnings']);
                        $rowBg = $isError ? 'table-danger' : ($isWarning ? 'table-warning' : '');
                    @endphp
                    <tr class="{{ $rowBg }}">
                        <td>{{ $row['line'] }}</td>
                        <td><strong>{{ $row['code'] }}</strong></td>
                        <td>{{ $row['name'] }}</td>
                        <td><span class="text-muted" title="Cột này trong file được bỏ qua">{{ $row['department'] }}</span></td>
                        <td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : 'Không hợp lệ' }}</td>
                        <td>{{ $row['date'] ? \App\Services\AttendanceFileReader::weekday($row['date']) : '—' }}</td>
                        <td>
                            {{ $row['in'] ? substr($row['in'],0,5) : '—' }}
                            @if($row['old'] && !$row['duplicate'])
                                <div class="text-muted small">Cũ: {{ $row['old']['checkin'] ?? 'Trống' }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $row['out'] ? substr($row['out'],0,5) : '—' }}
                            @if($row['old'] && !$row['duplicate'])
                                <div class="text-muted small">Cũ: {{ $row['old']['checkout'] ?? 'Trống' }}</div>
                            @endif
                        </td>
                        <td style="white-space:normal;min-width:280px">
                            @if($isError)
                                <div class="mb-1">
                                    <span class="badge bg-danger"><i class="ri-close-circle-fill me-1"></i> KHÔNG NHẬP (LỖI)</span>
                                </div>
                                @foreach($row['errors'] as $error)
                                    <div class="text-danger small fw-semibold"><i class="ri-error-warning-fill me-1"></i>{{ $error }}</div>
                                @endforeach
                                @foreach($row['warnings'] as $warning)
                                    <div class="text-muted small"><i class="ri-information-line me-1"></i>{{ $warning }}</div>
                                @endforeach
                            @else
                                @if($row['duplicate'])
                                    <div class="mb-1"><span class="badge bg-secondary"><i class="ri-skip-forward-line me-1"></i> BỎ QUA (ĐÃ CÓ)</span></div>
                                    <div class="text-muted small">Dữ liệu giống hệt bản ghi đã lưu</div>
                                @elseif($row['new_employee'])
                                    <div class="mb-1"><span class="badge bg-info text-dark"><i class="ri-user-add-line me-1"></i> NHÂN VIÊN MỚI</span></div>
                                    <div class="text-muted small">Cần tích "Tạo nhân viên mới" để lưu công</div>
                                @elseif($row['old'])
                                    <div class="mb-1"><span class="badge bg-primary"><i class="ri-refresh-line me-1"></i> SẼ CẬP NHẬT GIỜ MỚI</span></div>
                                    <div class="text-muted small">Cần tích "Cập nhật dòng thay đổi" để áp dụng</div>
                                @else
                                    <div class="mb-1"><span class="badge bg-success"><i class="ri-check-line me-1"></i> SẼ NHẬP VÀO HỆ THỐNG</span></div>
                                @endif

                                @foreach($row['warnings'] as $warning)
                                    <div class="text-warning-emphasis small fw-medium"><i class="ri-alert-fill me-1"></i>{{ $warning }}</div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
