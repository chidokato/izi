@csrf

@php
    $avatarImage = old('existing_avatar', $user->avatar ?? '');
@endphp

<div class="row">
    <div class="col-xl-9">
        <div class="card border">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Ten</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name ?? '') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" readonly>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="job_title" class="form-label">Chuc danh</label>
                            <input type="text" class="form-control @error('job_title') is-invalid @enderror" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title ?? '') }}">
                            @error('job_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="permission" class="form-label">Quyen (Permission)</label>
                            <select class="form-select @error('permission') is-invalid @enderror" id="permission" name="permission">
                                <option value="1" {{ old('permission', $user->permission ?? 6) == 1 ? 'selected' : '' }}>Quan tri vien cap cao (Super Admin)</option>
                                <option value="2" {{ old('permission', $user->permission ?? 6) == 2 ? 'selected' : '' }}>Quan tri vien (Admin)</option>
                                <option value="3" {{ old('permission', $user->permission ?? 6) == 3 ? 'selected' : '' }}>Quan ly (Moderator)</option>
                                <option value="4" {{ old('permission', $user->permission ?? 6) == 4 ? 'selected' : '' }}>Giang vien (Instructor)</option>
                                <option value="5" {{ old('permission', $user->permission ?? 6) == 5 ? 'selected' : '' }}>Hoc vien (Student)</option>
                                <option value="6" {{ old('permission', $user->permission ?? 6) == 6 ? 'selected' : '' }}>Khach (Guest)</option>
                            </select>
                            @error('permission')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label d-block">Avatar</label>
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
                                    <button type="button" class="btn btn-soft-danger btn-sm image-remove-trigger {{ $avatarImage ? '' : 'd-none' }}" data-input="avatar_file" data-remove="remove_avatar">Bo anh</button>
                                </div>
                                @error('avatar_file')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="bio" class="form-label">Mo ta seller</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio', $user->bio ?? '') }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label for="address" class="form-label">Dia chi</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $user->address ?? '') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="phone" class="form-label">So dien thoai</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="secondary_phone" class="form-label">So dien thoai phu</label>
                            <input type="text" class="form-control @error('secondary_phone') is-invalid @enderror" id="secondary_phone" name="secondary_phone" value="{{ old('secondary_phone', $user->secondary_phone ?? '') }}">
                            @error('secondary_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="whatsapp_phone" class="form-label">So WhatsApp</label>
                            <input type="text" class="form-control @error('whatsapp_phone') is-invalid @enderror" id="whatsapp_phone" name="whatsapp_phone" value="{{ old('whatsapp_phone', $user->whatsapp_phone ?? '') }}">
                            @error('whatsapp_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if(isset($user))
                        <div class="col-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="toggle-password-change">
                                <label class="form-check-label" for="toggle-password-change">Sửa mật khẩu</label>
                            </div>
                        </div>
                    @endif

                    <div class="col-lg-6 password-fields">
                        <div class="mb-0">
                            <label for="password" class="form-label">Mat khau {{ isset($user) ? '(de trong neu khong doi)' : '' }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" {{ isset($user) ? 'disabled' : '' }}>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-lg-6 password-fields">
                        <div class="mb-0">
                            <label for="password_confirmation" class="form-label">Nhap lai mat khau</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" {{ isset($user) ? 'disabled' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card border">
            <div class="card-header">
                <h5 class="card-title mb-0">Huong dan</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">Email dung de dang nhap he thong.</p>
                <p class="text-muted mb-2">Thong tin seller se hien thi trong box Contact Sellers o trang chi tiet du an.</p>
                <p class="text-muted mb-2">Avatar co the upload truc tiep; anh se duoc luu trong `public/uploads/users`.</p>
                <p class="text-muted mb-2">Mat khau toi thieu 6 ky tu.</p>
                <p class="text-muted mb-0">Khi sua user, co the de trong mat khau neu khong muon thay doi.</p>
            </div>
        </div>
    </div>
</div>

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
