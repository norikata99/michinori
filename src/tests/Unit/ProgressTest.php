<?php

namespace Tests\Unit;

use App\Support\Progress;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ProgressTest extends TestCase
{
    private function progress(int $estimated = 120, int $actual = 0, string $plannedEnd = '2026-11-20', ?string $finishedOn = null): Progress
    {
        return new Progress(
            estimatedMinutes: $estimated,
            actualMinutes: $actual,
            plannedEnd: Carbon::parse($plannedEnd),
            finishedOn: $finishedOn === null ? null : Carbon::parse($finishedOn),
            actualStart: null,
        );
    }

    public function test_effort_gap_is_actual_minus_estimated(): void
    {
        $this->assertSame(30, $this->progress(estimated: 120, actual: 150)->effortGapMinutes());
        $this->assertSame(-45, $this->progress(estimated: 120, actual: 75)->effortGapMinutes());
        $this->assertSame(0, $this->progress(estimated: 120, actual: 120)->effortGapMinutes());
        $this->assertSame(-120, $this->progress(estimated: 120, actual: 0)->effortGapMinutes());
    }

    public function test_schedule_gap_is_finished_minus_planned_end(): void
    {
        $this->assertSame(3, $this->progress(plannedEnd: '2026-11-20', finishedOn: '2026-11-23')->scheduleGapDays());
        $this->assertSame(-2, $this->progress(plannedEnd: '2026-11-20', finishedOn: '2026-11-18')->scheduleGapDays());
        $this->assertSame(0, $this->progress(plannedEnd: '2026-11-20', finishedOn: '2026-11-20')->scheduleGapDays());
    }

    public function test_schedule_gap_crosses_month_and_year(): void
    {
        $this->assertSame(2, $this->progress(plannedEnd: '2026-12-31', finishedOn: '2027-01-02')->scheduleGapDays());
    }

    public function test_schedule_gap_is_null_until_finished(): void
    {
        $this->assertNull($this->progress(finishedOn: null)->scheduleGapDays());
    }

    public function test_schedule_gap_ignores_time_of_day(): void
    {
        $progress = new Progress(
            estimatedMinutes: 60,
            actualMinutes: 0,
            plannedEnd: Carbon::parse('2026-11-20 23:00:00'),
            finishedOn: Carbon::parse('2026-11-21 01:00:00'),
            actualStart: null,
        );

        $this->assertSame(1, $progress->scheduleGapDays());
    }

    public function test_from_aggregates_accepts_db_strings_and_nulls(): void
    {
        // SUM・MIN の結果は、DB やドライバーによって文字列で返る。ログが0件なら null
        $withLogs = Progress::fromAggregates(120, '150', Carbon::parse('2026-11-20'), null, '2026-11-05');
        $this->assertSame(150, $withLogs->actualMinutes);
        $this->assertSame('2026-11-05', $withLogs->actualStart->toDateString());

        $withoutLogs = Progress::fromAggregates(120, null, Carbon::parse('2026-11-20'), null, null);
        $this->assertSame(0, $withoutLogs->actualMinutes);
        $this->assertNull($withoutLogs->actualStart);
    }
}
