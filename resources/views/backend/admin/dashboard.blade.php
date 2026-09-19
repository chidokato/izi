@extends('backend.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="panel section-card" id="overview">
        <div class="page-header">
            <div>
                <div class="badge">Bảng điều khiển</div>
                <h1>Admin cho project bất động sản</h1>
                <p>Khung giao diện đã bám theo kiểu admin của site mẫu, nhưng được dựng lại gọn hơn để mình dễ mở rộng sau này.</p>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a class="button ghost" href="{{ route('backend.admin.dashboard') }}">Làm mới</a>
                <a class="button primary" href="#listings">Xem tin đăng</a>
            </div>
        </div>

        <div class="stat-grid">
            @foreach ($stats as $stat)
                <article class="stat-card">
                    <small>{{ $stat['label'] }}</small>
                    <h3>{{ $stat['value'] }}</h3>
                    <p>{{ $stat['change'] }} so với kỳ trước</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="two-column">
        <article class="panel section-card">
            <div class="section-title">
                <div>
                    <h2>Hoạt động gần đây</h2>
                    <span>Các thay đổi mới nhất trên hệ thống</span>
                </div>
                <a href="#activity">Chi tiết</a>
            </div>

            <div class="timeline" id="activity">
                @foreach ($activities as $activity)
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div>
                            <h4>{{ $activity['title'] }}</h4>
                            <p>{{ $activity['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel section-card">
            <div class="section-title">
                <div>
                    <h2>Thao tác nhanh</h2>
                    <span>Nhảy vào tác vụ quản trị thường dùng</span>
                </div>
            </div>

            <div class="quick-actions">
                <div class="quick-action">
                    <div>
                        <strong>Thêm tin đăng</strong>
                        <span>Tạo bài mới cho căn hộ, nhà phố hoặc đất nền</span>
                    </div>
                    <a class="button ghost" href="#listings">Mở</a>
                </div>
                <div class="quick-action">
                    <div>
                        <strong>Kiểm duyệt form</strong>
                        <span>Xem danh sách khách hàng đã gửi liên hệ</span>
                    </div>
                    <a class="button ghost" href="#activity">Mở</a>
                </div>
                <div class="quick-action">
                    <div>
                        <strong>Chỉnh SEO</strong>
                        <span>Điều chỉnh tiêu đề, mô tả và thông tin hiển thị</span>
                    </div>
                    <a class="button ghost" href="#settings">Mở</a>
                </div>
            </div>
        </article>
    </section>

    <section class="two-column" id="listings">
        <article class="panel section-card">
            <div class="section-title">
                <div>
                    <h2>Danh sách tin đăng</h2>
                    <span>Mẫu bảng quản trị cho nội dung bất động sản</span>
                </div>
                <span>4 mục</span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tên tin</th>
                            <th>Khu vực</th>
                            <th>Trạng thái</th>
                            <th>Giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($properties as $property)
                            <tr>
                                <td>{{ $property['name'] }}</td>
                                <td>{{ $property['city'] }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($property['status']) {
                                            'Đã duyệt' => 'success',
                                            'Chờ duyệt' => 'warning',
                                            default => 'info',
                                        };
                                    @endphp
                                    <span class="status {{ $statusClass }}">{{ $property['status'] }}</span>
                                </td>
                                <td>{{ $property['price'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel section-card" id="settings">
            <div class="section-title">
                <div>
                    <h2>Nhịp hoạt động</h2>
                    <span>Tỷ lệ công việc trong tuần này</span>
                </div>
            </div>

            <div class="mini-chart">
                <div class="chart-row">
                    <strong>Đăng tin</strong>
                    <div class="chart-track"><div class="chart-fill" style="width: 82%"></div></div>
                    <strong>82%</strong>
                </div>
                <div class="chart-row">
                    <strong>Phản hồi</strong>
                    <div class="chart-track"><div class="chart-fill" style="width: 64%"></div></div>
                    <strong>64%</strong>
                </div>
                <div class="chart-row">
                    <strong>SEO</strong>
                    <div class="chart-track"><div class="chart-fill" style="width: 73%"></div></div>
                    <strong>73%</strong>
                </div>
                <div class="chart-row">
                    <strong>Kiểm duyệt</strong>
                    <div class="chart-track"><div class="chart-fill" style="width: 49%"></div></div>
                    <strong>49%</strong>
                </div>
            </div>

            <div style="margin-top: 18px; display:grid; gap:12px;">
                <div class="quick-action">
                    <div>
                        <strong>Chủ đề</strong>
                        <span>Velzon-style dark sidebar, làm lại sạch và dễ đọc</span>
                    </div>
                    <span class="status info">Active</span>
                </div>
                <div class="quick-action">
                    <div>
                        <strong>Trạng thái đăng nhập</strong>
                        <span>{{ session('admin_name', 'Administrator') }}</span>
                    </div>
                    <span class="status success">Ready</span>
                </div>
            </div>
        </article>
    </section>
@endsection
