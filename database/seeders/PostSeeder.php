<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $newsCategory1 = Category::where('type', Category::TYPE_NEWS)->where('slug', 'tin-tuc-thi-truong')->first()
            ?? Category::where('type', Category::TYPE_NEWS)->first();

        $newsCategory2 = Category::where('type', Category::TYPE_NEWS)->where('slug', 'cam-nang-hoc-tap')->first()
            ?? Category::where('type', Category::TYPE_NEWS)->first();

        $courseCategory1 = Category::where('type', Category::TYPE_COURSE)->where('slug', 'ielts-intensive-6-5')->first()
            ?? Category::where('type', Category::TYPE_COURSE)->first();

        $courseCategory2 = Category::where('type', Category::TYPE_COURSE)->where('slug', 'english-for-workplace')->first()
            ?? Category::where('type', Category::TYPE_COURSE)->first();

        $newsPosts = [
            [
                'title' => 'Bí quyết đạt 8.0 IELTS Academic chỉ trong 6 tháng học tập cường độ cao',
                'slug' => 'bi-quyet-dat-8-0-ielts-academic-chi-trong-6-thang-hoc-tap-cuong-do-cao',
                'category_id' => $newsCategory1?->id,
                'summary' => 'Học tập đúng phương pháp kết hợp với tài liệu chuẩn quốc tế là chìa khóa giúp học viên bứt phá điểm số IELTS trong thời gian ngắn.',
                'content' => '<p>Chia sẻ từ các học viên đạt 8.0+ IELTS tại trung tâm: Việc duy trì kỷ律 học tập mỗi ngày 2-3 tiếng và chú trọng kỹ năng Speaking - Writing đã giúp các bạn cải thiện rõ rệt.</p><p>Học viên cần lập bảng theo dõi sự tiến bộ và thường xuyên thi thử để làm quen với áp lực phòng thi.</p>',
                'seo_title' => 'Bí quyết đạt 8.0 IELTS Academic nhanh chóng 2026',
                'seo_description' => 'Cập nhật lộ trình và bí quyết luyện thi IELTS Academic đạt 8.0 từ chuyên gia.',
            ],
            [
                'title' => '5 lỗi ngữ pháp phổ biến nhất khi viết email công việc bằng tiếng Anh',
                'slug' => '5-loi-ngu-phap-pho-bien-nhat-khi-viet-email-cong-viec-bang-tieng-anh',
                'category_id' => $newsCategory2?->id,
                'summary' => 'Viết email chuyên nghiệp bằng tiếng Anh giúp ghi điểm tuyệt đối trong mắt đối tác và nhà tuyển dụng quốc tế.',
                'content' => '<p>Trong giao tiếp công việc, tính rõ ràng và lịch sự luôn được ưu tiên hàng đầu. Tránh sử dụng từ viết tắt không chính thức và kiểm tra kỹ thì của động từ trước khi gửi email.</p>',
                'seo_title' => '5 lỗi viết email tiếng Anh công việc hay gặp',
                'seo_description' => 'Hướng dẫn cách khắc phục 5 lỗi ngữ pháp và từ vựng phổ biến trong Business English.',
            ],
        ];

        foreach ($newsPosts as $item) {
            Post::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'type' => Post::TYPE_NEWS,
                    'category_id' => $item['category_id'],
                    'title' => $item['title'],
                    'summary' => $item['summary'],
                    'content' => $item['content'],
                    'seo_title' => $item['seo_title'],
                    'seo_description' => $item['seo_description'],
                    'is_active' => true,
                    'is_featured' => false,
                    'published_at' => now(),
                ]
            );
        }

        $coursePosts = [
            [
                'title' => 'IELTS Academic Mastery 7.5+ Intensive Course',
                'slug' => 'ielts-academic-mastery-7-5-intensive-course',
                'category_id' => $courseCategory1?->id,
                'summary' => 'Comprehensive 12-week intensive course designed to boost your IELTS Academic score to 7.5 and above with expert British Council certified instructors.',
                'content' => '<h3>Course Curriculum</h3><p><strong>Module 1: Advanced Writing Task 1 & 2</strong><br>Learn how to structure complex essays, analyze data charts, and master cohesive devices.</p><p><strong>Module 2: Speaking Fluency & Lexical Resource</strong><br>Live 1-on-1 mock interviews, pronunciation drills, and idiomatic vocabulary.</p><p><strong>Module 3: Reading & Listening Strategies</strong><br>Time management techniques and practice tests under exam conditions.</p>',
                'price' => 4500000,
                'is_featured' => true,
            ],
            [
                'title' => 'Business English & Professional Workplace Communication',
                'slug' => 'business-english-professional-workplace-communication',
                'category_id' => $courseCategory2?->id,
                'summary' => 'Master professional emails, business presentations, international meetings, and negotiation skills in English.',
                'content' => '<h3>Why Take This Course?</h3><p>Designed for professionals working in multinational environments. Learn authentic business terminology, email etiquette, and intercultural communication skills.</p><ul><li>Live interactive presentations</li><li>Case study simulations</li><li>Resume and LinkedIn profile workshop</li></ul>',
                'price' => 3200000,
                'is_featured' => true,
            ],
            [
                'title' => 'General English Foundation (A1 - A2) for Beginners',
                'slug' => 'general-english-foundation-a1-a2-for-beginners',
                'category_id' => $courseCategory1?->id,
                'summary' => 'Build your English from scratch. Practical vocabulary, basic grammar structures, and everyday speaking practice.',
                'content' => '<h3>Course Overview</h3><p>Step-by-step guidance for absolute beginners. Interactive lessons focusing on everyday conversation, listening comprehension, and foundational grammar.</p>',
                'price' => 2500000,
                'is_featured' => false,
            ],
        ];

        foreach ($coursePosts as $item) {
            Post::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'type' => Post::TYPE_COURSE,
                    'category_id' => $item['category_id'],
                    'title' => $item['title'],
                    'summary' => $item['summary'],
                    'content' => $item['content'],
                    'price' => $item['price'],
                    'is_active' => true,
                    'is_featured' => $item['is_featured'],
                    'published_at' => now(),
                ]
            );
        }
    }
}
