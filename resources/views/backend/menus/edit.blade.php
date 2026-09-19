@extends('backend.layouts.app')

@section('title', 'Sua Menu')
@section('page_title', 'Sua Menu')
@section('breadcrumb', 'Sua Menu')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Cap nhat menu</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="menu-form" class="btn btn-primary">Cap nhat menu</button>
                <a href="{{ route('backend.menus.index') }}" class="btn btn-light">Quay lai</a>
            </div>
        </div>
        <div class="card-body">
            <form id="menu-form" action="{{ route('backend.menus.update', $menu) }}" method="POST">
                @csrf
                @method('PUT')
                @include('backend.menus._form', ['menu' => $menu])
            </form>
        </div>
    </div>
@endsection
