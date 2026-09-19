@extends('backend.layouts.app')

@php
    $isCourse = in_array($type, ['course', 'product']);
@endphp

@section('title', $isCourse ? 'Edit Course' : 'Sua ' . $typeLabel)
@section('page_title', $isCourse ? 'Edit Course' : 'Sua ' . $typeLabel)
@section('breadcrumb', $isCourse ? 'Edit Course' : 'Sua ' . $typeLabel)

@push('styles')
    @if ($isCourse)
        <link href="{{ asset('admin-assets/libs/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
    @endif
    <link href="{{ asset('admin-assets/css/backend-content-form.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('scripts')
    @if ($isCourse)
        <script src="{{ asset('admin-assets/libs/jquery/jquery.js') }}"></script>
        <script src="{{ asset('admin-assets/libs/select2/select2.min.js') }}"></script>
    @endif
    <script src="{{ asset('admin-assets/js/backend-content-form.js') }}"></script>
@endpush

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ $isCourse ? 'Edit Course' : 'Cap nhat ' . strtolower($typeLabel) }}</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="content-form" name="save_stay" value="1" class="btn btn-success">{{ $isCourse ? 'Save & Stay' : 'Luu lai' }}</button>
                <button type="submit" form="content-form" class="btn btn-primary">{{ $isCourse ? 'Update Course' : 'Cap nhat ' . strtolower($typeLabel) }}</button>
                <a href="{{ route($isCourse ? 'backend.courses.index' : 'backend.news.index') }}" class="btn btn-light">{{ $isCourse ? 'Back' : 'Quay lai' }}</a>
            </div>
        </div>
        <div class="card-body">
            <form id="content-form" action="{{ route($isCourse ? 'backend.courses.update' : 'backend.news.update', $post) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('backend.contents._form', ['submitLabel' => $isCourse ? 'Update Course' : 'Cap nhat ' . strtolower($typeLabel), 'post' => $post])
            </form>
        </div>
    </div>
@endsection
