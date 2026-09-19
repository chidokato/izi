@extends('backend.layouts.app')

@section('title', 'User')
@section('page_title', 'User')
@section('breadcrumb', 'User')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Danh sach user</h4>
            <a href="{{ route('backend.users.create') }}" class="btn btn-primary">Them user</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Ten</th>
                            <th>Chuc danh</th>
                            <th>So dien thoai</th>
                            <th>Email</th>
                            <th>Quyen</th>
                            <th>Ngay tao</th>
                            <th class="text-end">Thao tac</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->job_title }}</td>
                                <td>{{ $user->phone }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge bg-secondary">{{ $user->permission_label }}</span></td>
                                <td>{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('backend.users.edit', $user) }}" class="btn btn-sm btn-soft-warning">Sua</a>
                                    <form action="{{ route('backend.users.destroy', $user) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-message="Ban co chac muon xoa user nay?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Xoa</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Chua co user nao.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
