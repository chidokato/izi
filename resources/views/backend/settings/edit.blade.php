@extends('backend.layouts.app')

@section('title', 'Setting')
@section('page_title', 'Setting')
@section('breadcrumb', 'Setting')

@push('scripts')
    <script src="{{ asset('admin-assets/js/backend-settings.js') }}"></script>
@endpush

@php
    $socialMap = collect($setting->social ?? [])->mapWithKeys(function ($item) {
        return [strtolower($item['label'] ?? '') => $item['url'] ?? ''];
    });
@endphp

@section('content')
    <form action="{{ route('backend.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Thong tin cong ty</h4>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Luu setting</button>
                    <a href="{{ route('backend.admin.dashboard') }}" class="btn btn-light">Quay lai</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Ten cty</label>
                            <input type="text" id="company_name" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $setting->company_name) }}">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $setting->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label for="hotline" class="form-label">Hotline</label>
                            <input type="text" id="hotline" name="hotline" class="form-control @error('hotline') is-invalid @enderror" value="{{ old('hotline', $setting->hotline) }}">
                            @error('hotline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-0">
                            <label for="address" class="form-label">Dia chi</label>
                            <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $setting->address) }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Social</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="facebook" class="form-label">Facebook</label>
                            <input type="url" id="facebook" name="facebook" class="form-control @error('facebook') is-invalid @enderror" value="{{ old('facebook', $socialMap->get('facebook')) }}" placeholder="https://facebook.com/your-page">
                            @error('facebook')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-0">
                            <label for="youtube" class="form-label">Youtube</label>
                            <input type="url" id="youtube" name="youtube" class="form-control @error('youtube') is-invalid @enderror" value="{{ old('youtube', $socialMap->get('youtube')) }}" placeholder="https://youtube.com/@your-channel">
                            @error('youtube')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Hinh anh thuong hieu</h4>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @include('backend.settings.partials.image-field', [
                        'title' => 'Logo',
                        'field' => 'logo_file',
                        'removeField' => 'remove_logo',
                        'image' => $setting->logo,
                    ])

                    @include('backend.settings.partials.image-field', [
                        'title' => 'Logo footer',
                        'field' => 'footer_logo_file',
                        'removeField' => 'remove_footer_logo',
                        'image' => $setting->footer_logo,
                    ])

                    @include('backend.settings.partials.image-field', [
                        'title' => 'Favicon',
                        'field' => 'favicon_file',
                        'removeField' => 'remove_favicon',
                        'image' => $setting->favicon,
                    ])
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Footer 4 cot</h4>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @for ($column = 1; $column <= 4; $column++)
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="mb-3">
                                    <label for="footer_column_{{ $column }}_title" class="form-label">Tieu de cot {{ $column }}</label>
                                    <input
                                        type="text"
                                        id="footer_column_{{ $column }}_title"
                                        name="footer_column_{{ $column }}_title"
                                        class="form-control @error('footer_column_' . $column . '_title') is-invalid @enderror"
                                        value="{{ old('footer_column_' . $column . '_title', data_get($setting, 'footer_column_' . $column . '_title')) }}"
                                    >
                                    @error('footer_column_' . $column . '_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-0">
                                    <label for="footer_column_{{ $column }}_content" class="form-label">Noi dung cot {{ $column }}</label>
                                    <textarea
                                        id="footer_column_{{ $column }}_content"
                                        name="footer_column_{{ $column }}_content"
                                        rows="6"
                                        class="form-control editor @error('footer_column_' . $column . '_content') is-invalid @enderror"
                                    >{{ old('footer_column_' . $column . '_content', data_get($setting, 'footer_column_' . $column . '_content')) }}</textarea>
                                    @error('footer_column_' . $column . '_content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </form>
@endsection
