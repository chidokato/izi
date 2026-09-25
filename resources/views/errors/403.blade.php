@extends('backend.layouts.app')
@section('title', '403 Forbidden')
@section('page_title', 'Truy cập bị từ chối')
@section('breadcrumb', 'Lỗi 403')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="text-center mt-4 pt-3">
            <div class="mb-5 pb-3">
                <i class="ri-error-warning-line display-1 text-danger"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">403</h1>
            <h3 class="text-uppercase mb-3">Truy cập bị từ chối 😭</h3>
            <p class="text-muted mb-4 fs-15">Xin lỗi, bạn không có quyền truy cập vào chức năng này. <br>Vui lòng liên hệ Quản trị viên để được hỗ trợ.</p>
            <a href="javascript:history.back()" class="btn btn-primary"><i class="ri-arrow-left-line me-1"></i>Quay lại trang trước</a>
            <a href="{{ route('backend.calendar.index') }}" class="btn btn-light ms-2"><i class="ri-calendar-todo-line me-1"></i>Lịch chấm công</a>
        </div>
    </div>
</div>
@endsection
