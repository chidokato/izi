@extends('backend.layouts.app')

@section('title', 'Menu')
@section('page_title', 'Menu')
@section('breadcrumb', 'Menu')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Quan ly menu</h4>
            <a href="{{ route('backend.menus.create') }}" class="btn btn-primary">Them menu</a>
        </div>
        <div class="card-body">
            @if ($menuTree->isEmpty())
                <div class="text-center text-muted py-4">Chua co menu nao.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ten menu</th>
                                <th>Slug</th>
                                <th>Target</th>
                                <th>Thu tu</th>
                                <th>Trang thai</th>
                                <th class="text-end">Thao tac</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($menuTree as $menu)
                                @include('backend.menus._row', ['menu' => $menu, 'level' => 0])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
