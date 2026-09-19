@extends('backend.layouts.app')

@php
    $isCourse = in_array($type, ['course', 'product']);
@endphp

@section('title', $typeLabel)
@section('page_title', $typeLabel)
@section('breadcrumb', $typeLabel)

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ $isCourse ? 'Course Management' : 'Quan ly ' . strtolower($typeLabel) }}</h4>
            <a href="{{ route($isCourse ? 'backend.courses.create' : 'backend.news.create') }}" class="btn btn-primary">
                {{ $isCourse ? 'Add Course' : 'Them ' . strtolower($typeLabel) }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ $isCourse ? 'Title' : 'Tieu de' }}</th>
                            <th>Category</th>
                            @if ($isCourse)
                                <th>Tài liệu bài học</th>
                                <th>Featured Course</th>
                            @endif
                            <th>{{ $isCourse ? 'Status' : 'Trang thai' }}</th>
                            <th>{{ $isCourse ? 'Published At' : 'Ngay dang' }}</th>
                            <th class="text-end">{{ $isCourse ? 'Actions' : 'Thao tac' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $post->title }}</div>
                                    <div class="text-muted small">{{ $post->slug }}</div>
                                </td>
                                <td>{{ optional($post->category)->name ?: '-' }}</td>
                                @if ($isCourse)
                                    <td>
                                        @if ($post->pdf_file)
                                            <a href="{{ asset($post->pdf_file) }}" target="_blank" class="badge bg-soft-danger text-danger d-inline-flex align-items-center gap-1 p-2 text-decoration-none" title="Xem tài liệu bài học">
                                                <i class="ri-file-pdf-2-line fs-6"></i>
                                                <span>{{ strtoupper(pathinfo($post->pdf_file, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                            </a>
                                        @else
                                            <span class="text-muted small">Chưa có</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <button
                                                type="button"
                                                class="status-toggle {{ $post->is_featured ? 'is-active' : 'is-inactive' }}"
                                                data-toggle-status
                                                data-url="{{ route('backend.courses.toggle-featured', $post) }}"
                                                aria-pressed="{{ $post->is_featured ? 'true' : 'false' }}"
                                            ></button>
                                            <span class="status-toggle-label {{ $post->is_featured ? 'text-success' : 'text-danger' }}" data-status-label>
                                                {{ $post->is_featured ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    </td>
                                @endif
                                <td>
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button
                                            type="button"
                                            class="status-toggle {{ $post->is_active ? 'is-active' : 'is-inactive' }}"
                                            data-toggle-status
                                            data-url="{{ route($isCourse ? 'backend.courses.toggle-status' : 'backend.news.toggle-status', $post) }}"
                                            aria-pressed="{{ $post->is_active ? 'true' : 'false' }}"
                                        ></button>
                                        <span class="status-toggle-label {{ $post->is_active ? 'text-success' : 'text-danger' }}" data-status-label>
                                            {{ $isCourse ? ($post->is_active ? 'Active' : 'Inactive') : ($post->is_active ? 'Hien thi' : 'An') }}
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $post->published_at ? $post->published_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route($isCourse ? 'backend.courses.edit' : 'backend.news.edit', $post) }}" class="btn btn-sm btn-soft-warning">{{ $isCourse ? 'Edit' : 'Sua' }}</a>
                                    <form action="{{ route($isCourse ? 'backend.courses.destroy' : 'backend.news.destroy', $post) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-message="{{ $isCourse ? 'Are you sure you want to delete this course?' : 'Ban co chac muon xoa ' . strtolower($typeLabel) . ' nay?' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">{{ $isCourse ? 'Delete' : 'Xoa' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isCourse ? 7 : 5 }}" class="text-center text-muted py-4">
                                    {{ $isCourse ? 'No courses found.' : 'Chua co ' . strtolower($typeLabel) . ' nao.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $posts->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
