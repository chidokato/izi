@extends('backend.layouts.app')

@section('title', 'Thêm phòng ban')
@section('page_title', 'Thêm phòng ban')
@section('breadcrumb', 'Phòng ban')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Thêm phòng ban mới</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('backend.departments.index') }}" class="btn btn-light">Hủy</a>
                <button type="submit" form="department-form" class="btn btn-primary">Thêm mới</button>
            </div>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form id="department-form" action="{{ route('backend.departments.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-xl-9">
                        <div class="card border mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-primary">Thông tin phòng ban</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="code" class="form-label">Mã phòng ban</label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}">
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Tên phòng ban <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phòng ban cấp trên</label>
                                            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                                <option value="">-- Không có (Phòng ban gốc) --</option>
                                                @foreach($departmentsTree as $dept)
                                                    <option value="{{ $dept->id }}" @selected(old('parent_id') == $dept->id)>
                                                        {{ $dept->name_with_prefix }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('parent_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="sort_order" class="form-label">Thứ tự sắp xếp</label>
                                            <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                                            @error('sort_order')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                                <option value="active" @selected(old('status', 'active') === 'active')>Đang hoạt động</option>
                                                <option value="inactive" @selected(old('status') === 'inactive')>Ngừng hoạt động</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
