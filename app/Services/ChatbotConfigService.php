<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ChatbotConfigService
{
    protected static string $fileName = 'chatbot_config.json';

    /**
     * Get default configuration values.
     */
    public static function defaults(): array
    {
        return [
            'is_active' => true,
            'bot_name' => 'Indochine AI Assistant',
            'bot_subtitle' => 'Trợ lý Khóa học & Lộ trình 24/7',
            'welcome_message' => '👋 Chào bạn! Mình là AI hỗ trợ của Indochine. Mình có thể giúp gì cho lộ trình học của bạn?',
            'ai_provider' => 'smart_local', // smart_local, gemini, openai
            'gemini_api_key' => '',
            'gemini_model' => 'gemini-1.5-flash',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'custom_system_prompt' => '',
            'quick_chips' => [
                '🎯 Khóa học nổi bật',
                '💰 Học phí & Thanh toán',
                '🚀 Lộ trình người mới',
                '📞 Liên hệ Admin',
            ],
        ];
    }

    /**
     * Get the full path to the configuration file.
     */
    public static function path(): string
    {
        return storage_path('app/' . self::$fileName);
    }

    /**
     * Get all configuration values merged with defaults.
     */
    public static function all(): array
    {
        $defaults = self::defaults();
        $path = self::path();

        if (!File::exists($path)) {
            return $defaults;
        }

        try {
            $content = File::get($path);
            $decoded = json_decode($content, true);

            if (is_array($decoded)) {
                return array_merge($defaults, $decoded);
            }
        } catch (\Throwable $e) {
            // Ignore corrupted json and return defaults
        }

        return $defaults;
    }

    /**
     * Get a specific configuration value by key.
     */
    public static function get(string $key, $default = null)
    {
        $all = self::all();

        return $all[$key] ?? $default ?? (self::defaults()[$key] ?? null);
    }

    /**
     * Save configuration array to storage file.
     */
    public static function save(array $data): bool
    {
        $current = self::all();
        $merged = array_merge($current, $data);

        // Ensure quick_chips is an array
        if (isset($data['quick_chips']) && is_string($data['quick_chips'])) {
            $chips = array_filter(array_map('trim', explode("\n", str_replace(',', "\n", $data['quick_chips']))));
            $merged['quick_chips'] = array_values($chips);
        }

        $path = self::path();

        // Ensure directory exists
        $dir = dirname($path);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return File::put($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
}
