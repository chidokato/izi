@extends('backend.layouts.app')

@section('title', 'Category')
@section('page_title', 'Category')
@section('breadcrumb', 'Category')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Quan ly category</h4>
            <a href="{{ route('backend.categories.create') }}" class="btn btn-primary">Them category</a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @foreach ($types as $type => $label)
                    <div class="col-12">
                        <div class="border rounded">
                            <div class="border-bottom px-3 py-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1">{{ $label }} Categories</h5>
                                    <p class="text-muted mb-0">Danh muc rieng cho {{ strtolower($label) }}.</p>
                                </div>
                                <a href="{{ route('backend.categories.create', ['type' => $type]) }}" class="btn btn-soft-primary btn-sm">Them {{ strtolower($label) }}</a>
                            </div>
                            <div class="p-3">
                                @if ($groupedTrees[$type]->isEmpty())
                                    <div class="text-muted">Chua co category {{ strtolower($label) }}.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-nowrap align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Ten danh muc</th>
                                                    <th>Slug</th>
                                                    <th>Thu tu</th>
                                                    <th>Trang thai</th>
                                                    <th class="text-end">Thao tac</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($groupedTrees[$type] as $category)
                                                    @include('backend.categories._row', ['category' => $category, 'level' => 0])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
