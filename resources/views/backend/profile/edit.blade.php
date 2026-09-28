@extends('backend.layouts.app')

@section('title', 'Thông tin cá nhân')
@section('page_title', 'Thông tin cá nhân')
@section('breadcrumb', 'Thông tin cá nhân')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Cập nhật thông tin cá nhân</h4>
            <div class="d-flex gap-2">
                <button type="submit" form="profile-form" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </div>
        <div class="card-body">
            <form id="profile-form" action="{{ route('backend.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                @php
                    $avatarImage = old('existing_avatar', $user->avatar ?? '');
                @endphp

                <div class="row">
                    <div class="col-xl-9">
                        <div class="card border mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Tên</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name ?? '') }}">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="bio" class="form-label">Giới thiệu ngắn (Bio)</label>
                                            <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio', $user->bio ?? '') }}</textarea>
                                            @error('bio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    @if($user->employee)
                                        <div class="col-12 mt-3 mb-2">
                                            <hr>
                                            <h5 class="mb-0 text-primary">Thông tin nhân sự</h5>
                                            <p class="text-muted small">Thông tin này được liên kết từ hồ sơ nhân sự, bạn không thể tự thay đổi.</p>
                                        </div>



                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phòng ban</label>
                                                @php
                                                    $department = \Illuminate\Support\Facades\DB::table('departments')->where('id', $user->employee->department_id)->first();
                                                @endphp
                                                <input type="text" class="form-control bg-light" value="{{ $department ? $department->name : 'N/A' }}" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Vị trí / Chức vụ</label>
                                                @php
                                                    $positionMap = [
                                                        'employee' => 'Nhân viên',
                                                        'team_leader' => 'Trưởng nhóm',
                                                        'manager' => 'Quản lý',
                                                        'director' => 'Giám đốc',
                                                    ];
                                                    $pos = $user->employee->position;
                                                    $displayPos = $positionMap[$pos] ?? ($pos ?: 'N/A');
                                                @endphp
                                                <input type="text" class="form-control bg-light" value="{{ $displayPos }}" readonly>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Quản lý trực tiếp</label>
                                                <input type="text" class="form-control bg-light" value="{{ $user->employee->manager ? $user->employee->manager->name : 'N/A' }}" readonly>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nhân sự (HR)</label>
                                                @php
                                                    $hr = \App\Models\Employee::find($user->employee->hr_id);
                                                @endphp
                                                <input type="text" class="form-control bg-light" value="{{ $hr ? $hr->name : 'N/A' }}" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label">Trạng thái làm việc</label>
                                                <input type="text" class="form-control bg-light" value="{{ $user->employee->status === 'active' ? 'Đang làm việc' : 'Đã nghỉ việc' }}" readonly>
                                            </div>
                                        </div>
                                    @endif




                                    
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="secondary_phone" class="form-label">Số điện thoại phụ</label>
                                            <input type="text" class="form-control @error('secondary_phone') is-invalid @enderror" id="secondary_phone" name="secondary_phone" value="{{ old('secondary_phone', $user->secondary_phone ?? '') }}">
                                            @error('secondary_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="whatsapp_phone" class="form-label">Zalo/WhatsApp</label>
                                            <input type="text" class="form-control @error('whatsapp_phone') is-invalid @enderror" id="whatsapp_phone" name="whatsapp_phone" value="{{ old('whatsapp_phone', $user->whatsapp_phone ?? '') }}">
                                            @error('whatsapp_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="card border mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Cấu hình đăng nhập</h5>
                                <p class="text-muted small mb-0 mt-1">Có thể dùng 1 trong 3 thông tin dưới đây để đăng nhập vào hệ thống.</p>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Mã nhân viên</label>
                                            <input type="text" class="form-control bg-light" value="{{ $user->employee->employee_code ?? 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email ?? '') }}">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Số điện thoại</label>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3 mt-2">
                                        <hr class="mt-0">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="toggle-password-change">
                                            <label class="form-check-label" for="toggle-password-change">Thay đổi mật khẩu</label>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 password-fields">
                                        <div class="mb-0">
                                            <label for="password" class="form-label">Mật khẩu mới</label>
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" disabled>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6 password-fields">
                                        <div class="mb-0">
                                            <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
                                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3">
                        <div class="card border mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Avatar</h5>
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                                <input type="hidden" name="existing_avatar" value="{{ $avatarImage }}">
                                <input type="file" id="avatar_file" name="avatar_file" class="d-none" accept="image/*">
                                <div class="border rounded p-3">
                                    <div class="d-flex flex-column gap-2">
                                        <button type="button" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden p-0 image-upload-trigger" data-input="avatar_file" style="height: 220px;">
                                            @if ($avatarImage)
                                                <img src="{{ asset($avatarImage) }}" alt="Avatar" class="w-100 h-100 object-fit-contain">
                                            @else
                                                <div class="text-center text-muted">
                                                    <div class="display-6 mb-2"><i class="ri-image-line"></i></div>
                                                    <div>No image</div>
                                                </div>
                                            @endif
                                        </button>
                                        <button type="button" class="btn btn-soft-danger btn-sm image-remove-trigger {{ $avatarImage ? '' : 'd-none' }}" data-input="avatar_file" data-remove="remove_avatar">Bỏ ảnh</button>
                                    </div>
                                    @error('avatar_file')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var togglePasswordChange = document.getElementById('toggle-password-change');
        if (togglePasswordChange) {
            togglePasswordChange.addEventListener('change', function() {
                var passwordInput = document.getElementById('password');
                var passwordConfirmInput = document.getElementById('password_confirmation');
                
                if (this.checked) {
                    passwordInput.removeAttribute('disabled');
                    passwordConfirmInput.removeAttribute('disabled');
                    passwordInput.focus();
                } else {
                    passwordInput.setAttribute('disabled', 'disabled');
                    passwordConfirmInput.setAttribute('disabled', 'disabled');
                    passwordInput.value = '';
                    passwordConfirmInput.value = '';
                }
            });
        }

        document.querySelectorAll('.image-upload-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                var inputId = button.getAttribute('data-input');
                var input = document.getElementById(inputId);

                if (input) {
                    input.click();
                }
            });
        });

        document.querySelectorAll('input[type="file"]').forEach(function (input) {
            input.addEventListener('change', function (event) {
                var file = event.target.files[0];
                var trigger = document.querySelector('.image-upload-trigger[data-input="' + input.id + '"]');
                var removeButton = document.querySelector('.image-remove-trigger[data-input="' + input.id + '"]');
                var removeField = document.getElementById('remove_avatar');

                if (!file || !trigger) {
                    return;
                }

                if (removeField) {
                    removeField.value = '0';
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    trigger.innerHTML = '<img src="' + e.target.result + '" class="w-100 h-100 object-fit-contain" alt="preview">';
                    if (removeButton) {
                        removeButton.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            });
        });

        document.querySelectorAll('.image-remove-trigger').forEach(function (button) {
            button.addEventListener('click', function () {
                var inputId = button.getAttribute('data-input');
                var removeFieldId = button.getAttribute('data-remove');
                var input = document.getElementById(inputId);
                var removeField = document.getElementById(removeFieldId);
                var trigger = document.querySelector('.image-upload-trigger[data-input="' + inputId + '"]');

                if (input) {
                    input.value = '';
                }

                if (removeField) {
                    removeField.value = '1';
                }

                if (trigger) {
                    trigger.innerHTML = '<div class="text-center text-muted"><div class="display-6 mb-2"><i class="ri-image-line"></i></div><div>No image</div></div>';
                }

                button.classList.add('d-none');
            });
        });
    });
</script>
@endpush
