@extends('backend.layouts.app')
@section('title', 'Cấu hình ngày công')
@section('page_title', 'Cấu hình ngày công thực tế')
@section('breadcrumb', 'Cấu hình ngày công')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thiết lập mới / Cập nhật</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('backend.monthly-settings.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="month" class="form-label">Tháng <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="month" name="month" value="{{ date('Y-m') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="standard_days" class="form-label">Số công chuẩn <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" min="0" max="31" class="form-control" id="standard_days" name="standard_days" placeholder="VD: 24 hoặc 26" required>
                    </div>
                    <div class="mb-3">
                        <label for="public_holidays" class="form-label">Số ngày nghỉ lễ <span class="text-danger">*</span></label>
                        <input type="number" min="0" max="31" class="form-control" id="public_holidays" name="public_holidays" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Ghi chú (ngày nghỉ, phép năm...)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="VD: Nghỉ lễ Quốc khánh 2/9..."></textarea>
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
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách cấu hình các tháng</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tháng</th>
                                <th class="text-center">Số công chuẩn</th>
                                <th class="text-center">Số ngày nghỉ lễ</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($settings as $setting)
                                <tr>
                                    <td class="fw-medium">{{ \Carbon\Carbon::createFromFormat('Y-m', $setting->month)->format('m/Y') }}</td>
                                    <td class="text-center">{{ (float)$setting->standard_days }}</td>
                                    <td class="text-center">{{ $setting->public_holidays }}</td>
                                    <td>{{ $setting->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu cấu hình nào.</td>
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
