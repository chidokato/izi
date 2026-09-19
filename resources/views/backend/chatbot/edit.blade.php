@extends('backend.layouts.app')

@section('title', 'Cấu hình AI ChatBot')
@section('page_title', 'Cấu hình AI ChatBot')
@section('breadcrumb', 'AI ChatBot')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-double-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('backend.chatbot.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- CARD 1: CẤU HÌNH CHUNG -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0"><i class="ri-palette-line me-2 text-primary"></i> 1. Trạng thái & Giao diện Trợ lý AI</h4>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Lưu cấu hình</button>
                    <a href="{{ route('backend.admin.dashboard') }}" class="btn btn-light">Quay lại</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $config['is_active']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">
                                Bật Widget AI ChatBot trên Website
                            </label>
                            <div class="form-text">Khi bật, hộp thoại AI sẽ tự động xuất hiện ở góc dưới bên phải tất cả các trang Frontend.</div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <label for="bot_name" class="form-label fw-semibold">Tên Trợ lý AI</label>
                        <input type="text" id="bot_name" name="bot_name" class="form-control @error('bot_name') is-invalid @enderror" value="{{ old('bot_name', $config['bot_name']) }}" placeholder="Ví dụ: Indochine AI Assistant" required>
                        @error('bot_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="bot_subtitle" class="form-label fw-semibold">Tiêu đề phụ (Subtitle)</label>
                        <input type="text" id="bot_subtitle" name="bot_subtitle" class="form-control @error('bot_subtitle') is-invalid @enderror" value="{{ old('bot_subtitle', $config['bot_subtitle']) }}" placeholder="Ví dụ: Trợ lý Khóa học & Lộ trình 24/7" required>
                        @error('bot_subtitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="welcome_message" class="form-label fw-semibold">Lời chào đầu tiên (Welcome Message)</label>
                        <textarea id="welcome_message" name="welcome_message" rows="3" class="form-control @error('welcome_message') is-invalid @enderror" placeholder="Nhập lời chào đầu tiên khi học viên mở hộp thoại AI..." required>{{ old('welcome_message', $config['welcome_message']) }}</textarea>
                        @error('welcome_message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: CHẾ ĐỘ AI & API KEY -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="ri-cpu-line me-2 text-info"></i> 2. Chế độ Trí tuệ nhân tạo (AI Engine & API Keys)</h4>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="ai_provider" class="form-label fw-semibold">Chọn AI Provider</label>
                        <select id="ai_provider" name="ai_provider" class="form-select @error('ai_provider') is-invalid @enderror">
                            <option value="smart_local" {{ old('ai_provider', $config['ai_provider']) === 'smart_local' ? 'selected' : '' }}>⚡ Smart Local Mode (Miễn phí, Tự động gợi ý từ DB)</option>
                            <option value="gemini" {{ old('ai_provider', $config['ai_provider']) === 'gemini' ? 'selected' : '' }}>🌟 Google Gemini API (Khuyên dùng - Nhanh & Thông minh)</option>
                            <option value="openai" {{ old('ai_provider', $config['ai_provider']) === 'openai' ? 'selected' : '' }}>🤖 OpenAI ChatGPT API (GPT-4o / GPT-4o-mini)</option>
                        </select>
                        <div class="form-text">
                            <strong>Mẹo:</strong> Nếu bạn chưa có API Key, hãy dùng <code>Smart Local Mode</code> để AI tự động tìm khóa học trong Database mà không mất phí.
                        </div>
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <!-- Gemini Config -->
                    <div class="col-lg-8">
                        <label for="gemini_api_key" class="form-label fw-semibold">Google Gemini API Key</label>
                        <input type="password" id="gemini_api_key" name="gemini_api_key" class="form-control @error('gemini_api_key') is-invalid @enderror" value="{{ old('gemini_api_key', $config['gemini_api_key']) }}" placeholder="AIzaSy... (Để trống nếu dùng mặc định trong .env)">
                        @error('gemini_api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Nhận API Key miễn phí tại <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.</div>
                    </div>

                    <div class="col-lg-4">
                        <label for="gemini_model" class="form-label fw-semibold">Gemini Model</label>
                        <input type="text" id="gemini_model" name="gemini_model" class="form-control" value="{{ old('gemini_model', $config['gemini_model']) }}" placeholder="gemini-1.5-flash">
                    </div>

                    <div class="col-12">
                        <hr class="my-2">
                    </div>

                    <!-- OpenAI Config -->
                    <div class="col-lg-8">
                        <label for="openai_api_key" class="form-label fw-semibold">OpenAI API Key</label>
                        <input type="password" id="openai_api_key" name="openai_api_key" class="form-control @error('openai_api_key') is-invalid @enderror" value="{{ old('openai_api_key', $config['openai_api_key']) }}" placeholder="sk-proj-... (Để trống nếu không dùng)">
                        @error('openai_api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="openai_model" class="form-label fw-semibold">OpenAI Model</label>
                        <input type="text" id="openai_model" name="openai_model" class="form-control" value="{{ old('openai_model', $config['openai_model']) }}" placeholder="gpt-4o-mini">
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: SYSTEM PROMPT MỞ RỘNG -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="ri-book-read-line me-2 text-warning"></i> 3. Tùy chỉnh Ngữ cảnh RAG (System Prompt bổ sung)</h4>
            </div>
            <div class="card-body">
                <label for="custom_system_prompt" class="form-label fw-semibold">Hướng dẫn bổ sung cho AI (Tùy chọn)</label>
                <textarea id="custom_system_prompt" name="custom_system_prompt" rows="4" class="form-control @error('custom_system_prompt') is-invalid @enderror" placeholder="Ví dụ:
- Hotline hỗ trợ Kỹ thuật: 0988.xxx.xxx
- Giờ làm việc: 8h00 - 21h00 hàng ngày
- Mã giảm giá hiện tại: INDOCHINE2026 giảm 20% cho học viên mới.">{{ old('custom_system_prompt', $config['custom_system_prompt']) }}</textarea>
                @error('custom_system_prompt')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Nội dung này sẽ được gộp vào lời nhắc hệ thống (System Prompt) giúp AI trả lời chính xác thông tin riêng của trung tâm/công ty bạn.
                </div>
            </div>
        </div>

        <!-- CARD 4: CÂU HỎI GỢI Ý NHANH -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="ri-lightbulb-flash-line me-2 text-success"></i> 4. Câu hỏi gợi ý nhanh (Quick Suggestion Chips)</h4>
            </div>
            <div class="card-body">
                <label for="quick_chips_text" class="form-label fw-semibold">Danh sách từ khóa / câu hỏi nhanh</label>
                <textarea id="quick_chips_text" name="quick_chips_text" rows="5" class="form-control @error('quick_chips_text') is-invalid @enderror" placeholder="🎯 Khóa học nổi bật
💰 Học phí & Thanh toán
🚀 Lộ trình người mới
📞 Liên hệ Admin">{{ old('quick_chips_text', $quickChipsText) }}</textarea>
                @error('quick_chips_text')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    Mỗi từ khóa/câu hỏi trên <strong>một dòng riêng biệt</strong>. Các nút bấm này sẽ xuất hiện trên khung chat giúp học viên hỏi nhanh bằng 1 cú nhấp chuột.
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <button type="submit" class="btn btn-primary btn-lg px-4"><i class="ri-save-line me-1"></i> Lưu toàn bộ cấu hình AI ChatBot</button>
        </div>
    </form>
@endsection
