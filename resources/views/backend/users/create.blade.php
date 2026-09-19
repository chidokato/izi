@extends('backend.layouts.app')

@section('title', 'Them User')
@section('page_title', 'Them User')
@section('breadcrumb', 'Them User')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Them user moi</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="user-form" class="btn btn-primary">Luu user</button>
                <a href="{{ route('backend.users.index') }}" class="btn btn-light">Quay lai</a>
            </div>
        </div>
        <div class="card-body">
            <form id="user-form" action="{{ route('backend.users.store') }}" method="POST" enctype="multipart/form-data">
                @include('backend.users._form', ['submitLabel' => 'Luu user'])
            </form>
        </div>
    </div>
@endsection
