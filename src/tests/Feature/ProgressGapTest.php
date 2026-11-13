<?php

namespace Tests\Feature;

use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

// 予定と実績のズレを、DB の集計から画面まで通して確かめる（docs/adr/0005）
class ProgressGapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Date::setTestNow('2026-11-30 10:00:00');
    }

    private function goalWithTasks(): Goal
    {
        $goal = Goal::factory()->create(['planned_start' => '2026-11-01', 'planned_end' => '2026-11-25']);

        // 完了。見積もり 120分、実績 90 + 60 = 150分（30分超過）。予定 11/10 → 完了 11/12（2日遅れ）
        $done = Task::factory()->for($goal)->create([
            'title' => '完了したタスク',
            'planned_start' => '2026-11-03',
            'planned_end' => '2026-11-10',
            'estimated_minutes' => 120,
            'status' => TaskStatus::Done,
            'completed_at' => '2026-11-12',
        ]);
        WorkLog::factory()->for($done)->create(['worked_on' => '2026-11-05', 'minutes' => 90]);
        WorkLog::factory()->for($done)->create(['worked_on' => '2026-11-12', 'minutes' => 60]);

        // 作業中。見積もり 300分、実績 45分。最初のログは 11/02（開始予定日 11/15 より前）
        $doing = Task::factory()->for($goal)->create([
            'title' => '作業中のタスク',
            'planned_start' => '2026-11-15',
            'planned_end' => '2026-11-20',
            'estimated_minutes' => 300,
            'status' => TaskStatus::Doing,
        ]);
        WorkLog::factory()->for($doing)->create(['worked_on' => '2026-11-02', 'minutes' => 45]);

        // 未着手。ログなし
        Task::factory()->for($goal)->create([
            'title' => '未着手のタスク',
            'estimated_minutes' => 60,
            'status' => TaskStatus::Todo,
        ]);

        // 別のゴールのログは集計に入らない
        WorkLog::factory()->create(['worked_on' => '2026-10-01', 'minutes' => 999]);

        return $goal;
    }

    public function test_task_progress_is_aggregated_from_logs(): void
    {
        $goal = $this->goalWithTasks();
        $done = $goal->tasks()->where('title', '完了したタスク')->sole();
        $todo = $goal->tasks()->where('title', '未着手のタスク')->sole();

        $progress = $done->progress();
        $this->assertSame(150, $progress->actualMinutes);
        $this->assertSame(30, $progress->effortGapMinutes());
        $this->assertSame('2026-11-05', $progress->actualStart->toDateString());
        $this->assertSame(2, $progress->scheduleGapDays());

        $empty = $todo->progress();
        $this->assertSame(0, $empty->actualMinutes);
        $this->assertNull($empty->actualStart);
        $this->assertNull($empty->scheduleGapDays());
    }

    public function test_preloaded_aggregates_match_direct_aggregates(): void
    {
        $goal = $this->goalWithTasks();

        foreach ($goal->tasks()->withProgress()->get() as $task) {
            $this->assertEquals($task->fresh()->progress(), $task->progress(), $task->title);
        }
    }

    public function test_goal_progress_sums_its_tasks(): void
    {
        $goal = $this->goalWithTasks();

        $progress = $goal->progress();
        $this->assertSame(120 + 300 + 60, $progress->estimatedMinutes);
        $this->assertSame(150 + 45, $progress->actualMinutes);
        $this->assertSame(195 - 480, $progress->effortGapMinutes());
        $this->assertSame('2026-11-02', $progress->actualStart->toDateString());
        // まだ達成していない
        $this->assertNull($progress->scheduleGapDays());

        $goal->update(['status' => GoalStatus::Achieved, 'achieved_at' => '2026-11-28']);
        $this->assertSame(3, $goal->progress()->scheduleGapDays());
    }

    public function test_goal_without_tasks_has_zero_progress(): void
    {
        $goal = Goal::factory()->create();

        $progress = $goal->progress();
        $this->assertSame(0, $progress->estimatedMinutes);
        $this->assertSame(0, $progress->actualMinutes);
        $this->assertNull($progress->actualStart);
    }

    public function test_editing_logs_changes_aggregates(): void
    {
        $goal = $this->goalWithTasks();
        $done = $goal->tasks()->where('title', '完了したタスク')->sole();
        $user = $goal->user;
        $firstLog = $done->workLogs()->whereDate('worked_on', '2026-11-05')->sole();

        // ログを直す：90分 → 30分、作業日 11/05 → 11/04
        $this->actingAs($user)->put(route('work-logs.update', $firstLog), [
            'worked_on' => '2026-11-04',
            'minutes' => 30,
            'note' => '',
        ])->assertSessionHasNoErrors();

        $progress = $done->progress();
        $this->assertSame(90, $progress->actualMinutes);
        $this->assertSame(-30, $progress->effortGapMinutes());
        $this->assertSame('2026-11-04', $progress->actualStart->toDateString());
        $this->assertSame(30 + 60 + 45, $goal->progress()->actualMinutes);

        // ログを消す
        $this->actingAs($user)->delete(route('work-logs.destroy', $firstLog));

        $progress = $done->progress();
        $this->assertSame(60, $progress->actualMinutes);
        $this->assertSame('2026-11-12', $progress->actualStart->toDateString());

        // ログを足す
        $this->actingAs($user)->post(route('tasks.work-logs.store', $done), [
            'worked_on' => '2026-11-01',
            'minutes' => 15,
        ]);

        $progress = $done->progress();
        $this->assertSame(75, $progress->actualMinutes);
        $this->assertSame('2026-11-01', $progress->actualStart->toDateString());
    }

    public function test_completing_task_changes_schedule_gap(): void
    {
        $goal = $this->goalWithTasks();
        $doing = $goal->tasks()->where('title', '作業中のタスク')->sole();
        $this->assertNull($doing->progress()->scheduleGapDays());

        $this->actingAs($goal->user)->put(route('tasks.update', $doing), [
            'title' => $doing->title,
            'planned_start' => '2026-11-15',
            'planned_end' => '2026-11-20',
            'estimated_minutes' => 300,
            'status' => TaskStatus::Done->value,
            'completed_at' => '2026-11-18',
        ])->assertSessionHasNoErrors();

        $this->assertSame(-2, $doing->fresh()->progress()->scheduleGapDays());
    }

    public function test_goal_page_shows_gaps(): void
    {
        $goal = $this->goalWithTasks();

        $this->actingAs($goal->user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            // ゴール全体：見積もり 480分、実績 195分、285分少ない、実績開始日 11/02
            ->assertSeeInOrder(['予定と実績', '見積もり', '8時間', '実績', '3時間15分', '工数のズレ', '4時間45分', '少ない', '実績開始日', '2026-11-02'])
            // 完了したタスクの行：実績 2時間30分、30分超過、実績開始日 11/05、2日遅れ
            ->assertSeeInOrder(['完了したタスク', '2時間30分', '30分', '超過', '2026-11-05', '2026-11-12', '2日遅れ']);
    }

    public function test_task_page_shows_gaps(): void
    {
        $goal = $this->goalWithTasks();
        $done = $goal->tasks()->where('title', '完了したタスク')->sole();

        $this->actingAs($goal->user)
            ->get(route('tasks.show', $done))
            ->assertOk()
            ->assertSeeInOrder(['見積もり', '2時間', '実績', '2時間30分', '工数のズレ', '30分', '超過', '実績開始日', '2026-11-05', '日程のズレ', '2日遅れ']);
    }
}
