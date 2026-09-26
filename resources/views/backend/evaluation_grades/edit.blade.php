@extends('backend.layouts.app')

@section('title', 'Cập nhật xếp loại')
@section('page_title', 'Sửa xếp loại')
@section('breadcrumb', 'Sửa xếp loại đánh giá')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Cập nhật mức xếp loại</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('backend.evaluation-grades.update', $grade->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Tên xếp loại (vd: A, B, Xuất sắc...) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $grade->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="min_score" class="form-label">Từ điểm <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('min_score') is-invalid @enderror" id="min_score" name="min_score" value="{{ old('min_score', $grade->min_score) }}" required>
                        @error('min_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="max_score" class="form-label">Đến điểm <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('max_score') is-invalid @enderror" id="max_score" name="max_score" value="{{ old('max_score', $grade->max_score) }}" required>
                        @error('max_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <a href="{{ route('backend.evaluation-grades.index') }}" class="btn btn-light">Hủy</a>
                </div>
            </form>
        </div>
    </div>
@endsection
