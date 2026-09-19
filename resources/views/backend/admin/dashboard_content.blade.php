@extends('backend.layouts.app')

@section('title', 'Tổng quan')
@section('page_title', 'Tổng quan')
@section('breadcrumb', 'Quản trị')

@section('content')
    <div class="card">
        <div class="card-body p-4">
            <h1 class="h3">Quản trị IZI</h1>
            <p class="text-muted mb-0">Chào {{ auth()->user()->name }}, bạn đã đăng nhập vào hệ thống.</p>
        </div>
    </div>
    <div class="row">
        @foreach ($stats as $label => $value)
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted">{{ $label }}</p>
                        <h2 class="mb-0">{{ number_format($value) }}</h2>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
