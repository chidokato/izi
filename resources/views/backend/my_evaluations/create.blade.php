@extends('backend.layouts.app')

@section('title', 'Tạo phiếu đánh giá')
@section('page_title', 'Tạo phiếu đánh giá mới')
@section('breadcrumb', 'Tạo phiếu đánh giá')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Chọn kỳ đánh giá</h4>
                </div>
                <div class="card-body">
                    @if(session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('backend.my-evaluations.store') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="month" class="form-label">Tháng</label>
                                <select name="month" id="month" class="form-select" required>
                                    @for($i=1; $i<=12; $i++)
                                        <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>Tháng {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="year" class="form-label">Năm</label>
                                <select name="year" id="year" class="form-select" required>
                                    @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                                        <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Bắt đầu đánh giá</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
