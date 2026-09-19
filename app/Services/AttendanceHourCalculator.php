<?php

namespace App\Services;

class AttendanceHourCalculator
{
    public function calculate(object $entry, ?object $rule): array
    {
        $result = ['regular_hours' => null, 'total_hours' => null, 'early_arrival' => null, 'late_arrival' => null, 'early_departure' => null, 'late_departure' => null, 'note' => null];
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
                if ($in !== null && $in >= $breakEnd) {
                    $missedMorning = true;
                }
                if ($out !== null && $out <= $breakStart) {
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
                if ($missedAfternoon) {
                    $result['early_departure'] = (int) floor(max(0, $breakStart - $out) / 60);
                } else {
                    $result['early_departure'] = (int) floor(max(0, $end - $out) / 60);
                }
            }
        }
        if ($in === null || $out === null) {
            if ($working) {
                $result['regular_hours'] = 0;
            }
            return $result;
        }
        $overlap = fn ($a, $b, $c, $d) => max(0, min($b, $d) - max($a, $c));
        $breakStart = $working && $rule->break_start !== null ? $seconds($rule->break_start) : null;
        $breakEnd = $working && $rule->break_end !== null ? $seconds($rule->break_end) : null;
        $breakSeconds = $breakStart !== null && $breakEnd !== null ? $overlap($in, $out, $breakStart, $breakEnd) : 0;
        $result['total_hours'] = round(max(0, $out - $in - $breakSeconds) / 3600, 2);
        
        $baseHours = $working && isset($rule->required_minutes) && $rule->required_minutes > 0 ? round($rule->required_minutes / 60, 2) : ($working ? 8 : 0);
        $lostMins = ($result['late_arrival'] ?? 0) + ($result['early_departure'] ?? 0);
        
        $penaltyHours = 0;
        
        if ($missedMorning) {
            $penaltyHours += $baseHours / 2;
        }
        if ($missedAfternoon) {
            $penaltyHours += $baseHours / 2;
        }

        if (isset($rule->day_of_week) && $rule->day_of_week == 6) {
            // Thứ 7: trên 120p = 0, còn không là 4 (mặc định)
            if ($lostMins > 120) {
                $penaltyHours = $baseHours; 
            }
        } else {
            if ($lostMins >= 250) { // muộn 250phuts mất 1 ngày công
                $penaltyHours = 8;
            } elseif ($lostMins > 100) { // đi muộn về sớm >100 phút mất nửa công
                $penaltyHours += 4;
            }
        }

        // Apply penalty, ensuring we don't drop below 0
        $result['regular_hours'] = max(0, $baseHours - $penaltyHours);

        return $result;
    }
}
