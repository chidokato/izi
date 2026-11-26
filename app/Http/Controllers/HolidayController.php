<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', '');
        $query = Holiday::orderByDesc('holiday_date');
        if ($year !== '') {
            $query->whereYear('holiday_date', (int) $year);
        }
        $holidays = $query->get();

        $years = Holiday::pluck('holiday_date')
            ->map(fn ($date) => $date ? substr((string) $date, 0, 4) : null)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return view('backend.holidays.index', compact('holidays', 'year', 'years'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:holiday,company_off,working_day',
            'work_value' => 'nullable|numeric|min:0|max:2',
            'note' => 'nullable|string',
        ]);

        $start = CarbonImmutable::parse($validated['start_date']);
        $end = !empty($validated['end_date']) ? CarbonImmutable::parse($validated['end_date']) : $start;
        $workValue = $validated['work_value'] ?? 1;

        $count = 0;
        for ($d = $start; $d->lte($end); $d = $d->addDay()) {
            Holiday::updateOrCreate(
                ['holiday_date' => $d->toDateString()],
                [
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'work_value' => $workValue,
                    'note' => $validated['note'] ?? null,
                ]
            );
            $count++;
        }

        return redirect()->route('backend.holidays.index')
            ->with('success', "Đã lưu cấu hình nghỉ lễ ({$count} ngày).");
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return redirect()->route('backend.holidays.index')
            ->with('success', 'Đã xóa ngày nghỉ lễ.');
    }
}
