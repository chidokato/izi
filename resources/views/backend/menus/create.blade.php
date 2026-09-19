@extends('backend.layouts.app')

@section('title', 'Them Menu')
@section('page_title', 'Them Menu')
@section('breadcrumb', 'Them Menu')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Them menu moi</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="menu-form" class="btn btn-primary">Luu menu</button>
                <a href="{{ route('backend.menus.index') }}" class="btn btn-light">Quay lai</a>
            </div>
        </div>
        <div class="card-body">
            <form id="menu-form" action="{{ route('backend.menus.store') }}" method="POST">
                @include('backend.menus._form')
            </form>
        </div>
    </div>
@endsection
