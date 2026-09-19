<?php

namespace App\Http\Controllers;

use App\Services\ChatbotConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminChatbotController extends Controller
{
    /**
     * Show the admin chatbot configuration form.
     */
    public function edit(): View
    {
        $config = ChatbotConfigService::all();

        // Convert quick chips array to text (each chip on a new line)
        $quickChipsText = implode("\n", $config['quick_chips'] ?? []);

        return view('backend.chatbot.edit', compact('config', 'quickChipsText'));
    }

    /**
     * Update the chatbot configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_name' => ['required', 'string', 'max:255'],
            'bot_subtitle' => ['required', 'string', 'max:255'],
            'welcome_message' => ['required', 'string', 'max:1000'],
            'ai_provider' => ['required', 'string', 'in:smart_local,gemini,openai'],
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'gemini_model' => ['nullable', 'string', 'max:100'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'openai_model' => ['nullable', 'string', 'max:100'],
            'custom_system_prompt' => ['nullable', 'string', 'max:3000'],
            'quick_chips_text' => ['nullable', 'string', 'max:1000'],
        ]);

        // Convert quick_chips_text into array
        $quickChips = [];
        if (!empty($validated['quick_chips_text'])) {
            $lines = explode("\n", str_replace(',', "\n", $validated['quick_chips_text']));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $quickChips[] = $trimmed;
                }
            }
        }

        if (empty($quickChips)) {
            $quickChips = ChatbotConfigService::defaults()['quick_chips'];
        }

        $saveData = [
            'is_active' => $request->boolean('is_active'),
            'bot_name' => $validated['bot_name'],
            'bot_subtitle' => $validated['bot_subtitle'],
            'welcome_message' => $validated['welcome_message'],
            'ai_provider' => $validated['ai_provider'],
            'gemini_api_key' => $validated['gemini_api_key'] ?? '',
            'gemini_model' => $validated['gemini_model'] ?: 'gemini-1.5-flash',
            'openai_api_key' => $validated['openai_api_key'] ?? '',
            'openai_model' => $validated['openai_model'] ?: 'gpt-4o-mini',
            'custom_system_prompt' => $validated['custom_system_prompt'] ?? '',
            'quick_chips' => array_values(array_unique($quickChips)),
        ];

        ChatbotConfigService::save($saveData);

        return redirect()
            ->route('backend.chatbot.edit')
            ->with('success', 'Cập nhật cấu hình AI ChatBot thành công!');
    }
}
