@extends('backend.layouts.app')

@section('title', 'Sua Category')
@section('page_title', 'Sua Category')
@section('breadcrumb', 'Sua Category')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Cap nhat category</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="category-form" class="btn btn-primary">Cap nhat category</button>
                <a href="{{ route('backend.categories.index') }}" class="btn btn-light">Quay lai</a>
            </div>
        </div>
        <div class="card-body">
            <form id="category-form" action="{{ route('backend.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                @include('backend.categories._form', ['submitLabel' => 'Cap nhat category', 'category' => $category])
            </form>
        </div>
    </div>
@endsection
