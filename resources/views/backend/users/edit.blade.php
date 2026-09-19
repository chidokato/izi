@extends('backend.layouts.app')

@section('title', 'Sua User')
@section('page_title', 'Sua User')
@section('breadcrumb', 'Sua User')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Cap nhat user</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="user-form" class="btn btn-primary">Cap nhat user</button>
                <a href="{{ route('backend.users.index') }}" class="btn btn-light">Quay lai</a>
            </div>
        </div>
        <div class="card-body">
            <form id="user-form" action="{{ route('backend.users.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('backend.users._form', ['submitLabel' => 'Cap nhat user', 'user' => $user])
            </form>
        </div>
    </div>
@endsection
