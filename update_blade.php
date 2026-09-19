<?php
$file = 'resources/views/frontend/course-details.blade.php';
$content = file_get_contents($file);

// Replace "Bạn sẽ học được gì"
$what_to_learn_pattern = '/<h4 class="it-details-title">Bạn sẽ học được gì<\/h4>\s*<div class="it-details-list-box mt-5 mb-60">.*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s';
$what_to_learn_replacement = <<<'EOD'
<h4 class="it-details-title">Bạn sẽ học được gì</h4>
<div class="it-details-list-box mt-5 mb-60 ck-content-list style-2-col">
    @if (!empty($post->what_to_learn))
        {!! $post->what_to_learn !!}
    @else
        <ul>
            <li>Hiểu sâu lý thuyết căn bản và tư duy áp dụng thực tế</li>
            <li>Quy trình làm việc chuẩn nghiệp vụ chuyên nghiệp</li>
            <li>Làm chủ các công cụ chuyên sâu và kỹ năng thực hành</li>
            <li>Đạt chứng chỉ hoàn thành khóa học và thăng tiến nghề nghiệp</li>
            <li>Tự tin triển khai các dự án hoàn chỉnh từ A-Z</li>
        </ul>
    @endif
</div>
EOD;
$content = preg_replace($what_to_learn_pattern, $what_to_learn_replacement, $content);

// Replace "Khóa học này bao gồm"
$course_includes_pattern = '/<h4 class="it-details-title">Khóa học này bao gồm:<\/h4>\s*<div class="it-details-list-box mt-5 mb-60">.*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s';
$course_includes_replacement = <<<'EOD'
<h4 class="it-details-title">Khóa học này bao gồm:</h4>
<div class="it-details-list-box mt-5 mb-60 ck-content-list style-2-col">
    @if (!empty($post->course_includes))
        {!! $post->course_includes !!}
    @else
        <ul>
            <li>Truy cập trọn đời trên máy tính và thiết bị di động</li>
            <li>Tài liệu hướng dẫn trực quan</li>
            <li>Tài liệu, bài tập và mã nguồn mẫu tải về</li>
            <li>Hỗ trợ học viên trên mọi thiết bị</li>
            <li>Cấp chứng chỉ tốt nghiệp khi hoàn thành</li>
        </ul>
    @endif
</div>
EOD;
$content = preg_replace($course_includes_pattern, $course_includes_replacement, $content);

// Replace "Yêu cầu khóa học"
$course_requirements_pattern = '/<h4 class="it-details-title">Yêu cầu khóa học<\/h4>\s*<div class="it-details-list mb-60">.*?<\/ul>\s*<\/div>/s';
$course_requirements_replacement = <<<'EOD'
<h4 class="it-details-title">Yêu cầu khóa học</h4>
<div class="it-details-list mb-60 ck-content-list">
    @if (!empty($post->course_requirements))
        {!! $post->course_requirements !!}
    @else
        <ul>
            <li>Máy tính hoặc laptop có kết nối Internet để học tập</li>
            <li>Không yêu cầu kinh nghiệm trước đó, phù hợp cả cho người mới bắt đầu</li>
            <li>Tinh thần ham học hỏi và sẵn sàng thực hành qua từng bài tập</li>
        </ul>
    @endif
</div>
EOD;
$content = preg_replace($course_requirements_pattern, $course_requirements_replacement, $content);

file_put_contents($file, $content);
echo "Blade updated successfully.";
