<?php

namespace Tests\Unit;

use App\Services\AttendanceHourCalculator;
use PHPUnit\Framework\TestCase;

class AttendanceHourCalculatorTest extends TestCase
{
    private function calculate(?string $in, ?string $out, array $overrides = []): array
    {
        return (new AttendanceHourCalculator)->calculate((object) ['checkin' => $in, 'checkout' => $out], (object) array_merge(['is_working_day' => true, 'start_time' => '08:30:00', 'end_time' => '17:30:00', 'break_start' => '12:30:00', 'break_end' => '13:30:00'], $overrides));
    }

    public function test_regular_hours_exclude_early_arrival_late_departure_and_lunch(): void
    {
        $r = $this->calculate('08:27:00', '17:37:00');
        $this->assertEquals(8, $r['regular_hours']);
        $this->assertEquals(8.17, $r['total_hours']);
        $this->assertSame(3, $r['early_arrival']);
        $this->assertSame(7, $r['late_departure']);
        $this->assertSame(0, $r['late_arrival']);
    }

    public function test_partial_break_and_late_early_minutes(): void
    {
        $r = $this->calculate('09:00:00', '13:00:00');
        $this->assertEquals(3.5, $r['regular_hours']);
        $this->assertSame(30, $r['late_arrival']);
        $this->assertSame(270, $r['early_departure']);
        $this->assertEquals(0, $this->calculate('12:45:00', '13:00:00')['regular_hours']);
    }

    public function test_missing_punches_and_overnight_are_not_zero_work(): void
    {
        $r = $this->calculate('08:23:00', null);
        $this->assertNull($r['regular_hours']);
        $this->assertSame(7, $r['early_arrival']);
        $this->assertNull($r['late_departure']);
        $this->assertNull($this->calculate('22:00:00', '06:00:00')['regular_hours']);
    }

    public function test_half_day_and_nonworking_day(): void
    {
        $this->assertEquals(4, $this->calculate('08:06:00', '17:35:00', ['end_time' => '12:30:00', 'break_start' => null, 'break_end' => null])['regular_hours']);
        $r = $this->calculate('08:00:00', '12:00:00', ['is_working_day' => false, 'start_time' => null, 'end_time' => null, 'break_start' => null, 'break_end' => null]);
        $this->assertEquals(0, $r['regular_hours']);
        $this->assertEquals(4, $r['total_hours']);
        $this->assertNull($r['early_arrival']);
    }
}
