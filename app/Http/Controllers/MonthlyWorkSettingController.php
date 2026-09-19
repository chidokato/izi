<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MonthlyWorkSetting;

class MonthlyWorkSettingController extends Controller
{
    public function index()
    {
        $settings = MonthlyWorkSetting::orderBy('month', 'desc')->get();
        return view('backend.monthly_settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'standard_days' => 'required|numeric|min:0|max:31',
            'public_holidays' => 'required|integer|min:0|max:31',
            'notes' => 'nullable|string'
        ]);

        MonthlyWorkSetting::updateOrCreate(
            ['month' => $request->month],
            [
                'standard_days' => $request->standard_days,
                'public_holidays' => $request->public_holidays,
                'notes' => $request->notes
            ]
        );

        return redirect()->route('backend.monthly-settings.index')->with('success', 'Đã lưu cấu hình ngày công tháng ' . $request->month);
    }
}
