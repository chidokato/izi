@extends('backend.layouts.app')

@section('title', 'Them Category')
@section('page_title', 'Them Category')
@section('breadcrumb', 'Them Category')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Them category moi</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="category-form" class="btn btn-primary">Luu category</button>
                <a href="{{ route('backend.categories.index') }}" class="btn btn-light">Quay lai</a>
            </div>
        </div>
        <div class="card-body">
            <form id="category-form" action="{{ route('backend.categories.store') }}" method="POST">
                @include('backend.categories._form', ['submitLabel' => 'Luu category'])
            </form>
        </div>
    </div>
@endsection
