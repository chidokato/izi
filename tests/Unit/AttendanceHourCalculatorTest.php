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

    public function test_new_cong_calculation_rules(): void
    {
        // 1. Ngày thường: >= 6 tiếng = 1 công
        $r1 = $this->calculate('08:30:00', '17:30:00'); // 8h
        $this->assertEquals(8, $r1['regular_hours']);
        $this->assertEquals(1, $r1['cong']);

        $r2 = $this->calculate('08:30:00', '15:30:00'); // 6h (7h trừ 1h nghỉ)
        $this->assertEquals(6, $r2['regular_hours']);
        $this->assertEquals(1, $r2['cong']);

        // 2. Ngày thường: >= 2 tiếng và < 6 tiếng = 0.5 công
        $r3 = $this->calculate('08:30:00', '14:30:00'); // 5h
        $this->assertEquals(5, $r3['regular_hours']);
        $this->assertEquals(0.5, $r3['cong']);

        $r4 = $this->calculate('08:30:00', '12:30:00'); // 4h
        $this->assertEquals(4, $r4['regular_hours']);
        $this->assertEquals(0.5, $r4['cong']);

        $r5 = $this->calculate('08:30:00', '10:30:00'); // 2h
        $this->assertEquals(2, $r5['regular_hours']);
        $this->assertEquals(0.5, $r5['cong']);

        // 3. Ngày thường: < 2 tiếng = 0 công
        $r6 = $this->calculate('08:30:00', '10:29:00'); // 1.98h < 2h
        $this->assertEquals(0, $r6['cong']);

        $r7 = $this->calculate('08:30:00', '09:30:00'); // 1h < 2h
        $this->assertEquals(1, $r7['regular_hours']);
        $this->assertEquals(0, $r7['cong']);

        // 4. Thứ 7: tiêu chuẩn 4 tiếng. Hơn 2h = 0.5 công, còn lại 0 công
        $satRule = ['day_of_week' => 6, 'start_time' => '08:30:00', 'end_time' => '12:30:00', 'break_start' => null, 'break_end' => null, 'required_minutes' => 240];
        
        $s1 = $this->calculate('08:30:00', '12:30:00', $satRule); // 4h > 2h
        $this->assertEquals(4, $s1['regular_hours']);
        $this->assertEquals(0.5, $s1['cong']);

        $s2 = $this->calculate('08:30:00', '11:00:00', $satRule); // 2.5h > 2h
        $this->assertEquals(2.5, $s2['regular_hours']);
        $this->assertEquals(0.5, $s2['cong']);

        $s3 = $this->calculate('08:30:00', '10:30:00', $satRule); // 2.0h (không hơn 2h)
        $this->assertEquals(2, $s3['regular_hours']);
        $this->assertEquals(0, $s3['cong']);

        $s4 = $this->calculate('08:30:00', '10:00:00', $satRule); // 1.5h <= 2h
        $this->assertEquals(1.5, $s4['regular_hours']);
        $this->assertEquals(0, $s4['cong']);
    }
}
