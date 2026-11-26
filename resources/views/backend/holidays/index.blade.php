@extends('backend.layouts.app')
@section('title', 'Cấu hình nghỉ lễ')
@section('page_title', 'Cấu hình ngày nghỉ lễ')
@section('breadcrumb', 'Cấu hình nghỉ lễ')

@section('content')
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thiết lập ngày nghỉ lễ</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('backend.holidays.store') }}" method="POST">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="start_date" class="form-label">Từ ngày <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-6">
                            <label for="end_date" class="form-label">Đến ngày</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}">
                        </div>
                        <div class="form-text">Để trống "Đến ngày" nếu chỉ nghỉ 1 ngày.</div>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Tên ngày lễ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="VD: Nghỉ lễ Quốc khánh 2/9" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="type" class="form-label">Loại <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="holiday" @selected(old('type', 'holiday') === 'holiday')>Nghỉ lễ</option>
                                <option value="company_off" @selected(old('type') === 'company_off')>Công ty cho nghỉ</option>
                                <option value="working_day" @selected(old('type') === 'working_day')>Ngày làm bù</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label for="work_value" class="form-label">Công hưởng</label>
                            <input type="number" step="0.5" min="0" max="2" class="form-control" id="work_value" name="work_value" value="{{ old('work_value', '1') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="note" name="note" rows="2" placeholder="Ghi chú thêm (nếu có)...">{{ old('note') }}</textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">Danh sách ngày nghỉ lễ</h5>
                @if($years->isNotEmpty())
                <form method="GET" action="{{ route('backend.holidays.index') }}" class="d-flex align-items-center gap-2">
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tất cả các năm</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" @selected((string)$year === (string)$y)>Năm {{ $y }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ngày</th>
                                <th>Tên ngày lễ</th>
                                <th class="text-center">Loại</th>
                                <th class="text-center">Công</th>
                                <th>Ghi chú</th>
                                <th class="text-center" style="width: 80px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $typeLabels = [
                                    'holiday' => ['label' => 'Nghỉ lễ', 'class' => 'bg-danger-subtle text-danger'],
                                    'company_off' => ['label' => 'Công ty nghỉ', 'class' => 'bg-info-subtle text-info'],
                                    'working_day' => ['label' => 'Làm bù', 'class' => 'bg-warning-subtle text-warning'],
                                ];
                            @endphp
                            @forelse($holidays as $holiday)
                                @php $t = $typeLabels[$holiday->type] ?? ['label' => $holiday->type, 'class' => 'bg-secondary-subtle text-secondary']; @endphp
                                <tr>
                                    <td class="fw-medium text-nowrap">{{ \Carbon\Carbon::parse($holiday->holiday_date)->format('d/m/Y') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td class="text-center"><span class="badge {{ $t['class'] }}">{{ $t['label'] }}</span></td>
                                    <td class="text-center">{{ (float) $holiday->work_value }}</td>
                                    <td>{{ $holiday->note ?: '—' }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('backend.holidays.destroy', $holiday) }}" method="POST" onsubmit="return confirm('Xóa ngày nghỉ lễ {{ \Carbon\Carbon::parse($holiday->holiday_date)->format('d/m/Y') }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger" title="Xóa">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Chưa có ngày nghỉ lễ nào được cấu hình.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
