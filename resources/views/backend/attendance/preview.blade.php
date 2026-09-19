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
@endphp
<div class="card"><div class="card-body"><h5>{{ $batch->filename }}</h5><p>{{ count($rows) }} dòng · {{ $errorCount }} dòng lỗi · {{ $duplicates }} dòng trùng · {{ $newEmployees }} nhân viên mới · {{ $changes }} dòng thay đổi.</p>
<p class="text-muted">Ngày được hiển thị theo ngày/tháng/năm. Dòng lỗi sẽ không nhập; dòng giống dữ liệu cũ được bỏ qua. Bản xem trước có hiệu lực trong 1 giờ. Chưa tính công hay lương.</p>
<form action="{{ route('backend.attendance.confirm',$batch->id) }}" method="post">@csrf
@if($newEmployees)<div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="create" name="create_employees" value="1"><label class="form-check-label" for="create">Tạo {{ $newEmployees }} nhân viên mới và phòng ban còn thiếu theo file (không tạo tài khoản đăng nhập).</label></div>@endif
@if($changes)<div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="replace" name="replace_existing" value="1"><label class="form-check-label" for="replace">Cập nhật {{ $changes }} dòng thay đổi theo giờ mới bên dưới, kể cả khi giờ mới trống.</label></div>@endif
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="confirm" name="confirm" value="1" required><label class="form-check-label" for="confirm">Tôi đã kiểm tra dữ liệu và đồng ý nhập các dòng hợp lệ theo lựa chọn trên.</label></div>
<button class="btn btn-primary" @disabled($errorCount===count($rows))>Xác nhận nhập</button> <a class="btn btn-light" href="{{ route('backend.attendance.index') }}">Quay lại</a>
</form></div></div>
<div class="card"><div class="card-body"><div class="table-responsive" style="max-height:650px"><table class="table table-bordered table-nowrap"><thead class="table-light" style="position:sticky;top:0"><tr><th>Dòng</th><th>Mã NV</th><th>Tên nhân viên</th><th>Phòng ban</th><th>Ngày</th><th>Thứ</th><th>Giờ vào</th><th>Giờ ra</th><th>Kết quả kiểm tra</th></tr></thead><tbody>
@foreach($rows as $row)<tr class="{{ $row['errors'] ? 'table-danger' : ($row['warnings'] ? 'table-warning' : '') }}"><td>{{ $row['line'] }}</td><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['department'] }}</td><td>{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : 'Không hợp lệ' }}</td><td>{{ $row['date'] ? \App\Services\AttendanceFileReader::weekday($row['date']) : '—' }}</td><td>{{ $row['in'] ? substr($row['in'],0,5) : '—' }}@if($row['old'] && !$row['duplicate'])<div class="text-muted">Cũ: {{ $row['old']['checkin'] ?? 'Trống' }}</div>@endif</td><td>{{ $row['out'] ? substr($row['out'],0,5) : '—' }}@if($row['old'] && !$row['duplicate'])<div class="text-muted">Cũ: {{ $row['old']['checkout'] ?? 'Trống' }}</div>@endif</td><td style="white-space:normal;min-width:240px">@foreach($row['errors'] as $error)<div class="text-danger">{{ $error }}</div>@endforeach @foreach($row['warnings'] as $warning)<div>{{ $warning }}</div>@endforeach @if(!$row['errors'])<strong>{{ $row['duplicate'] ? 'Trùng — bỏ qua' : ($row['new_employee'] ? 'Nhân viên mới — cần chọn tạo' : ($row['old'] ? 'Thay đổi — cần chọn cập nhật' : 'Sẵn sàng nhập')) }}</strong>@endif</td></tr>@endforeach
</tbody></table></div></div></div>
@endsection