@extends('backend.layouts.app')

@section('title', 'Thêm mới nhân viên')
@section('page_title', 'Thêm mới nhân viên')
@section('breadcrumb', 'Nhân viên')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Thêm mới nhân viên</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('backend.employees.index') }}" class="btn btn-light">Hủy</a>
                <button type="submit" form="employee-form" class="btn btn-primary">Thêm mới</button>
            </div>
        </div>
        <div class="card-body">
            <form id="employee-form" action="{{ route('backend.employees.store') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-xl-9">
                        <div class="card border mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-primary">Thông tin nhân sự</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="employee_code" class="form-label">Mã nhân viên <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('employee_code') is-invalid @enderror" id="employee_code" name="employee_code" value="{{ old('employee_code', null) }}">
                                            @error('employee_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Tên <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', null) }}">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', null) }}">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Phòng ban</label>
                                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                                <option value="">-- Chọn phòng ban --</option>
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept->id }}" @selected(old('department_id', null) == $dept->id)>{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('department_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Vị trí / Chức vụ</label>
                                            <select name="position" class="form-select @error('position') is-invalid @enderror">
                                                <option value="">-- Chọn chức vụ --</option>
                                                @foreach(['employee'=>'Nhân viên','team_leader'=>'Trưởng nhóm','manager'=>'Trưởng phòng','director'=>'Giám đốc'] as $val => $label)
                                                    <option value="{{ $val }}" @selected(old('position', null) == $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('position')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Cấp bậc đặc biệt</label>
                                            <select name="level" class="form-select @error('level') is-invalid @enderror">
                                                <option value="">-- Không có --</option>
                                                @foreach(['HR'=>'Nhân sự (HR)','Admin'=>'Quản trị viên (Admin)','Director'=>'Ban giám đốc (Director)'] as $val => $label)
                                                    <option value="{{ $val }}" @selected(old('level', null) == $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('level')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Quản lý trực tiếp (Người duyệt lần 1)</label>
                                            <select name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                                                <option value="">-- Trống --</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" @selected(old('manager_id', null) == $manager->id)>{{ $manager->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('manager_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Quản lý (Người duyệt lần 2)</label>
                                            <select name="manager_l2_id" class="form-select @error('manager_l2_id') is-invalid @enderror">
                                                <option value="">-- Trống --</option>
                                                @foreach($hrs as $hr)
                                                    <option value="{{ $hr->id }}" @selected(old('manager_l2_id', null) == $hr->id)>{{ $hr->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('manager_l2_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Trạng thái làm việc <span class="text-danger">*</span></label>
                                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                                @foreach(['active'=>'Chính thức','probation'=>'Thử việc','inactive'=>'Công tác viên','resigned'=>'Nghỉ việc'] as $val => $label)
                                                    <option value="{{ $val }}" @selected(old('status', 'active') == $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Ngày vào làm</label>
                                            <input type="date" class="form-control @error('join_date') is-invalid @enderror" name="join_date" value="{{ old('join_date', '') }}">
                                            @error('join_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Ngày nghỉ việc</label>
                                            <input type="date" class="form-control @error('leave_date') is-invalid @enderror" name="leave_date" value="{{ old('leave_date', '') }}">
                                            @error('leave_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0 text-primary">Thông tin liên lạc</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Số điện thoại</label>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', null) }}">
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
