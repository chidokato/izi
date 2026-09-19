<?php

namespace App\Services;

use App\Models\Post;
use App\Services\ChatbotConfigService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotService
{
    /**
     * Generate an AI response to the user's message using RAG context from existing courses.
     *
     * @param  string  $userMessage
     * @param  array  $chatHistory
     * @return array{reply: string, suggested_courses: array}
     */
    public function generateReply(string $userMessage, array $chatHistory = []): array
    {
        $courses = $this->getActiveCourses();
        $suggestedCourses = $this->findRelevantCourses($userMessage, $courses);

        $config = ChatbotConfigService::all();
        $provider = $config['ai_provider'] ?? 'smart_local';

        $geminiKey = !empty($config['gemini_api_key']) ? $config['gemini_api_key'] : config('services.gemini.api_key');
        $openaiKey = !empty($config['openai_api_key']) ? $config['openai_api_key'] : config('services.openai.api_key');

        if ($provider === 'gemini' && !empty($geminiKey)) {
            try {
                $reply = $this->callGeminiApi($userMessage, $courses, $geminiKey, $config['gemini_model'] ?? 'gemini-1.5-flash');
                return [
                    'reply' => $reply,
                    'suggested_courses' => $suggestedCourses,
                ];
            } catch (\Throwable $e) {
                Log::error('Gemini API Error: ' . $e->getMessage());
            }
        } elseif ($provider === 'openai' && !empty($openaiKey)) {
            try {
                $reply = $this->callOpenAiApi($userMessage, $courses, $openaiKey, $config['openai_model'] ?? 'gpt-4o-mini');
                return [
                    'reply' => $reply,
                    'suggested_courses' => $suggestedCourses,
                ];
            } catch (\Throwable $e) {
                Log::error('OpenAI API Error: ' . $e->getMessage());
            }
        } else {
            // Fallback: check configured keys if provider wasn't explicitly smart_local
            if (!empty($geminiKey) && $provider !== 'smart_local') {
                try {
                    $reply = $this->callGeminiApi($userMessage, $courses, $geminiKey, $config['gemini_model'] ?? 'gemini-1.5-flash');
                    return [
                        'reply' => $reply,
                        'suggested_courses' => $suggestedCourses,
                    ];
                } catch (\Throwable $e) {}
            }
            if (!empty($openaiKey) && $provider !== 'smart_local') {
                try {
                    $reply = $this->callOpenAiApi($userMessage, $courses, $openaiKey, $config['openai_model'] ?? 'gpt-4o-mini');
                    return [
                        'reply' => $reply,
                        'suggested_courses' => $suggestedCourses,
                    ];
                } catch (\Throwable $e) {}
            }
        }

        // Smart Local Fallback Mode (Hoạt động tốt ngay cả khi chưa nhập API key)
        $reply = $this->generateSmartLocalReply($userMessage, $courses, $suggestedCourses);

        return [
            'reply' => $reply,
            'suggested_courses' => $suggestedCourses,
        ];
    }

    /**
     * Fetch active courses from the database.
     */
    protected function getActiveCourses()
    {
        try {
            $courses = Post::query()
                ->where('type', Post::TYPE_COURSE)
                ->where('is_active', true)
                ->with(['category', 'seller'])
                ->latest('published_at')
                ->take(15)
                ->get();

            if ($courses->count() > 0) {
                return $courses;
            }
        } catch (\Throwable $e) {
            Log::warning('Could not fetch courses from database: ' . $e->getMessage());
        }

        // Return rich default demo courses so ChatBot ALWAYS has RAG data!
        return collect([
            new Post([
                'title' => 'Design Thinking Researching for Better UX',
                'slug' => 'design-thinking-researching-for-better-ux',
                'summary' => 'Khóa học Design Thinking và tư duy nghiên cứu trải nghiệm người dùng UX/UI hiện đại.',
                'price' => 500000,
            ]),
            new Post([
                'title' => 'Lập trình Web với React & Next.js Thực chiến',
                'slug' => 'lap-trinh-web-voi-react-nextjs-thuc-chien',
                'summary' => 'Học từ cơ bản đến nâng cao: Hooks, State management, SSR và xây dựng dự án e-commerce thực tế.',
                'price' => 800000,
            ]),
            new Post([
                'title' => 'Fullstack Laravel & Vue.js cho người mới bắt đầu',
                'slug' => 'fullstack-laravel-vuejs-cho-nguoi-moi-bat-dau',
                'summary' => 'Lộ trình trọn gói phát triển web hiện đại với Laravel Backend, Eloquent ORM và Vue.js 3 Frontend.',
                'price' => 1200000,
            ]),
        ]);
    }

    /**
     * Build the RAG system prompt in Vietnamese.
     */
    protected function buildSystemPrompt($courses): string
    {
        $courseContext = "";
        foreach ($courses as $course) {
            $priceText = $course->price ? number_format($course->price, 0, ',', '.') . ' VNĐ' : 'Miễn phí';
            $categoryName = $course->category->name ?? 'Tổng hợp';
            $url = url('courses/' . $course->slug);
            $courseContext .= "- **{$course->title}** (Danh mục: {$categoryName}) | Học phí: {$priceText} | Link: {$url}\n  Mô tả: " . Str::limit(strip_tags($course->summary ?? $course->content), 120) . "\n\n";
        }

        $botName = ChatbotConfigService::get('bot_name', 'Indochine AI Assistant');
        $customPrompt = trim((string) ChatbotConfigService::get('custom_system_prompt', ''));
        $extraSection = $customPrompt !== '' ? "\n\n--- THÔNG TIN CHỈ DẪN BỔ SUNG TỪ ADMIN ---\n" . $customPrompt : '';

        return <<<EOT
Bạn là "{$botName}" - Trợ lý học tập và tư vấn khóa học trực tuyến thông minh, nhiệt tình và chuyên nghiệp của hệ thống Indochine.

Nhiệm vụ của bạn:
1. Trả lời bằng Tiếng Việt thân thiện, rõ ràng, giúp học viên giải đáp thắc mắc và tìm lộ trình học phù hợp nhất.
2. Khi học viên hỏi tìm khóa học, học phí, lộ trình học, hãy sử dụng chính xác thông tin từ Danh sách khóa học Indochine dưới đây để tư vấn.
3. Nếu không tìm thấy khóa học trong danh sách, hãy nói thật là hệ thống đang cập nhật và mời bạn để lại thông tin liên hệ.
4. Trình bày văn bản đẹp mắt, có sử dụng emoji và bullet point.

--- DANH SÁCH KHÓA HỌC HIỆN CÓ TẠI INDOCHINE ---
{$extraSection}
{$courseContext}
EOT;
    }

    /**
     * Call Google Gemini API.
     */
    protected function callGeminiApi(string $userMessage, $courses, string $apiKey, ?string $modelName = null): string
    {
        $model = !empty($modelName) ? $modelName : config('services.gemini.model', 'gemini-1.5-flash');
        $systemPrompt = $this->buildSystemPrompt($courses);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(20)->post($url, [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMessage]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 800,
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text) {
                return $text;
            }
        }

        throw new \Exception('Failed to generate content from Gemini API: ' . $response->body());
    }

    /**
     * Call OpenAI API.
     */
    protected function callOpenAiApi(string $userMessage, $courses, string $apiKey, ?string $modelName = null): string
    {
        $model = !empty($modelName) ? $modelName : config('services.openai.model', 'gpt-4o-mini');
        $systemPrompt = $this->buildSystemPrompt($courses);

        $response = Http::withToken($apiKey)
            ->timeout(20)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? null;
            if ($text) {
                return $text;
            }
        }

        throw new \Exception('Failed to generate content from OpenAI API: ' . $response->body());
    }

    /**
     * Find relevant courses based on keyword matching in user query.
     */
    protected function findRelevantCourses(string $userMessage, $courses): array
    {
        $query = mb_strtolower(trim($userMessage));
        $matches = [];

        foreach ($courses as $course) {
            $title = mb_strtolower($course->title ?? '');
            $summary = mb_strtolower($course->summary ?? '');
            $category = mb_strtolower($course->category->name ?? '');

            $score = 0;
            // Keywords
            $keywords = explode(' ', $query);
            foreach ($keywords as $kw) {
                if (mb_strlen($kw) < 2) {
                    continue;
                }
                if (str_contains($title, $kw)) {
                    $score += 3;
                }
                if (str_contains($category, $kw)) {
                    $score += 2;
                }
                if (str_contains($summary, $kw)) {
                    $score += 1;
                }
            }

            if ($score > 0 || str_contains($query, 'hot') || str_contains($query, 'nổi bật') || str_contains($query, 'tất cả') || str_contains($query, 'gợi ý')) {
                $matches[] = [
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'url' => url('courses/' . $course->slug),
                    'price_text' => $course->price ? number_format($course->price, 0, ',', '.') . ' VNĐ' : 'Miễn phí',
                    'category_name' => $course->category->name ?? 'Khóa học',
                    'score' => $score,
                ];
            }
        }

        // Sort by score descending
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        if (empty($matches) && count($courses) > 0) {
            foreach ($courses->take(3) as $course) {
                $matches[] = [
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'url' => url('courses/' . $course->slug),
                    'price_text' => $course->price ? number_format($course->price, 0, ',', '.') . ' VNĐ' : 'Miễn phí',
                    'category_name' => $course->category->name ?? 'Khóa học',
                    'score' => 1,
                ];
            }
        }

        return array_slice($matches, 0, 3);
    }

    /**
     * Generate an intelligent local reply when no external AI API keys are configured.
     */
    protected function generateSmartLocalReply(string $userMessage, $courses, array $suggestedCourses): string
    {
        $lowerMsg = mb_strtolower(trim($userMessage));

        // 1. Chào hỏi
        if (str_contains($lowerMsg, 'chào') || str_contains($lowerMsg, 'hi') || str_contains($lowerMsg, 'hello') || str_contains($lowerMsg, 'ai')) {
            return "👋 **Xin chào bạn!** Mình là **Indochine AI Assistant**.\n\n"
                . "Mình có thể hỗ trợ bạn tìm hiểu về lộ trình học, thông tin các khóa học lập trình & thiết kế, cũng như giải đáp thắc mắc về học phí và thanh toán.\n\n"
                . "💡 **Bạn đang quan tâm đến lĩnh vực nào (Web, React, UI/UX...) để mình gợi ý khóa học phù hợp nhất nhé?**";
        }

        // 2. Hỏi học phí & thanh toán
        if (str_contains($lowerMsg, 'giá') || str_contains($lowerMsg, 'học phí') || str_contains($lowerMsg, 'tiền') || str_contains($lowerMsg, 'thanh toán') || str_contains($lowerMsg, 'bao nhiêu') || str_contains($lowerMsg, 'chuyển khoản')) {
            $reply = "💰 **Thông tin Học phí & Thanh toán tại Indochine:**\n\n"
                . "- Các khóa học chuyên sâu tại Indochine có mức học phí cực kỳ hợp lý, dao động từ **500.000 VNĐ - 1.500.000 VNĐ** tùy vào thời lượng và nội dung chuyên sâu.\n"
                . "- Bạn có thể thanh toán trực tiếp qua **Chuyển khoản ngân hàng**, **Momo** hoặc **Thẻ ATM/Visa** ngay khi chọn mua khóa học.\n\n";

            if (count($suggestedCourses) > 0) {
                $reply .= "🎯 **Một số khóa học nổi bật và học phí:**\n";
                foreach ($suggestedCourses as $sc) {
                    $reply .= "- [**{$sc['title']}**]({$sc['url']}) - **{$sc['price_text']}**\n";
                }
            }
            return $reply;
        }

        // 3. Hỏi lộ trình cho người mới
        if (str_contains($lowerMsg, 'mới') || str_contains($lowerMsg, 'bắt đầu') || str_contains($lowerMsg, 'lộ trình') || str_contains($lowerMsg, 'chưa biết') || str_contains($lowerMsg, 'từ đầu')) {
            $reply = "🚀 **Lộ trình học dành cho người mới bắt đầu tại Indochine:**\n\n"
                . "1️⃣ **Bước 1 (Nền tảng):** Nắm vững tư duy logic, cấu trúc giao diện và nguyên lý thiết kế.\n"
                . "2️⃣ **Bước 2 (Chuyên sâu):** Xây dựng các ứng dụng thực tế với Framework hiện đại (như React, Laravel, UI/UX Design Thinking).\n"
                . "3️⃣ **Bước 3 (Thực chiến & Dự án):** Làm đồ án thực tế hoàn chỉnh để bổ sung vào CV/Portfolio.\n\n";

            if (count($suggestedCourses) > 0) {
                $reply .= "🌟 **Khóa học phù hợp nhất để bạn bắt đầu ngay hôm nay:**\n";
                foreach ($suggestedCourses as $sc) {
                    $reply .= "- [**{$sc['title']}**]({$sc['url']}) (*{$sc['category_name']}*) - **{$sc['price_text']}**\n";
                }
            }
            return $reply;
        }

        // 4. Hỏi liên hệ / tư vấn trực tiếp
        if (str_contains($lowerMsg, 'liên hệ') || str_contains($lowerMsg, 'admin') || str_contains($lowerMsg, 'tư vấn viên') || str_contains($lowerMsg, 'hotline') || str_contains($lowerMsg, 'zalo')) {
            return "📞 **Thông tin hỗ trợ trực tiếp từ đội ngũ Indochine:**\n\n"
                . "- **Hotline tư vấn (24/7):** `0988.xxx.xxx`\n"
                . "- **Email hỗ trợ:** `indochine@gmail.com`\n"
                . "- **Giờ làm việc:** 8h00 - 21h00 các ngày trong tuần\n\n"
                . "💬 Bạn cũng có thể để lại số điện thoại hoặc thắc mắc tại đây, đội ngũ CSKH của Indochine sẽ liên hệ bạn ngay nhé!";
        }

        // 5. Gợi ý khóa học theo từ khóa hoặc câu hỏi chung
        $reply = "🎓 **Indochine AI tư vấn khóa học cho bạn:**\n\n";

        if (count($suggestedCourses) > 0) {
            $reply .= "Dựa trên quan tâm của bạn, đây là các khóa học chất lượng cao đang được nhiều học viên lựa chọn tại Indochine:\n\n";
            foreach ($suggestedCourses as $sc) {
                $reply .= "✅ [**{$sc['title']}**]({$sc['url']})\n"
                    . "   - **Danh mục:** {$sc['category_name']}\n"
                    . "   - **Học phí:** {$sc['price_text']}\n"
                    . "   - 🔗 [Xem chi tiết khóa học]({$sc['url']})\n\n";
            }
            $reply .= "💡 *Bạn muốn tìm hiểu kỹ hơn về lộ trình của khóa nào, hãy cho mình biết nhé!*";
        } else {
            $reply .= "Hiện tại hệ thống đang cập nhật thêm các lộ trình học mới. Bạn có thể xem ngay [**Danh sách toàn bộ khóa học Indochine**](" . url('/') . ") hoặc nhắn câu hỏi cụ thể hơn để mình tư vấn nhé!";
        }

        return $reply;
    }
}
