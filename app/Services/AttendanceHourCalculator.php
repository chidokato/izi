<?php

namespace App\Services;

class AttendanceHourCalculator
{
    public function calculate(object $entry, ?object $rule): array
    {
        $result = ['regular_hours' => null, 'total_hours' => null, 'cong' => null, 'early_arrival' => null, 'late_arrival' => null, 'early_departure' => null, 'late_departure' => null, 'note' => null];
        if (! $rule) {
            return $result;
        }
        $seconds = fn ($time) => (int) substr($time, 0, 2) * 3600 + (int) substr($time, 3, 2) * 60 + (int) substr($time, 6, 2);
        $in = $entry->checkin === null ? null : $seconds($entry->checkin);
        $out = $entry->checkout === null ? null : $seconds($entry->checkout);
        if ($in !== null && $out !== null && $out < $in) {
            $result['note'] = 'Cần xác minh ca qua đêm';

            return $result;
        }
        $working = (bool) $rule->is_working_day;
        if ($working && (! $rule->start_time || ! $rule->end_time)) {
            return $result;
        }
        $start = $working ? $seconds($rule->start_time) : null;
        $end = $working ? $seconds($rule->end_time) : null;
        if ($working && $end <= $start) {
            return $result;
        }
        $missedMorning = false;
        $missedAfternoon = false;
        $breakStart = $working && $rule->break_start !== null ? $seconds($rule->break_start) : null;
        $breakEnd = $working && $rule->break_end !== null ? $seconds($rule->break_end) : null;

        if ($working) {
            if ($breakStart !== null && $breakEnd !== null) {
                if ($in !== null && $in >= $breakStart) {
                    $missedMorning = true;
                }
                if ($out !== null && $out <= $breakEnd) {
                    $missedAfternoon = true;
                }
            }

            if ($in !== null) {
                $result['early_arrival'] = (int) floor(max(0, $start - $in) / 60);
                if ($missedMorning) {
                    $result['late_arrival'] = (int) floor(max(0, $in - $breakEnd) / 60);
                } else {
                    $result['late_arrival'] = (int) floor(max(0, $in - $start) / 60);
                }
            }
            if ($out !== null) {
                $result['late_departure'] = (int) floor(max(0, $out - $end) / 60);
                $result['early_departure'] = (int) floor(max(0, $end - $out) / 60);
            }
        }
        if ($in === null || $out === null) {
            $result['regular_hours'] = null;
            $result['cong'] = $working ? 0 : null;
            return $result;
        }
        $overlap = fn ($a, $b, $c, $d) => max(0, min($b, $d) - max($a, $c));
        $breakSeconds = $breakStart !== null && $breakEnd !== null ? $overlap($in, $out, $breakStart, $breakEnd) : 0;
        $result['total_hours'] = round(max(0, $out - $in - $breakSeconds) / 3600, 2);

        if (! $working) {
            $result['regular_hours'] = 0;
            $result['cong'] = null;
            return $result;
        }

        // Tính tổng thời gian làm việc trong giờ làm tính theo lịch làm việc
        $workOverlap = $overlap($in, $out, $start, $end);
        $scheduleBreakOverlap = ($breakStart !== null && $breakEnd !== null) ? $overlap($in, $out, $breakStart, $breakEnd) : 0;
        $workedSeconds = max(0, $workOverlap - $scheduleBreakOverlap);
        $workedHours = round($workedSeconds / 3600, 2);

        $isSaturday = isset($rule->day_of_week) && $rule->day_of_week == 6;
        $baseHours = $isSaturday ? 4.0 : 8.0;
        if (isset($rule->required_minutes) && $rule->required_minutes > 0) {
            $baseHours = min($baseHours, round($rule->required_minutes / 60, 2));
        }

        $regularHours = min($baseHours, $workedHours);
        $result['regular_hours'] = $regularHours;

        // Quy tắc tính công mới:
        // - Thứ 7: tổng thời gian hơn 2h tính nửa công (0.5), còn lại 0 công
        // - Ngày thường: >= 6 tiếng tính 1 công, < 2 tiếng tính 0 công, từ 2h đến < 6h tính 0.5 công
        if ($isSaturday) {
            $result['cong'] = ($regularHours > 2) ? 0.5 : 0;
        } else {
            if ($regularHours >= 6) {
                $result['cong'] = 1;
            } elseif ($regularHours >= 2) {
                $result['cong'] = 0.5;
            } else {
                $result['cong'] = 0;
            }
        }

        return $result;
    }
}
