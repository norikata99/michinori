<?php

namespace Tests\Feature;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// 締切超過の警告：ゴールの締切と、配下のタスクの最も遅い終了予定日を比べる（docs/design.md 4章）
class DeadlineWarningTest extends TestCase
{
    use RefreshDatabase;

    private const WARNING = 'このままでは締切に間に合いません';

    private function goal(array $attributes = []): Goal
    {
        return Goal::factory()->create(array_merge([
            'planned_start' => '2026-11-01',
            'planned_end' => '2026-11-30',
        ], $attributes));
    }

    private function taskEndingOn(Goal $goal, string $plannedEnd): Task
    {
        return Task::factory()->for($goal)->create([
            'planned_start' => '2026-11-01',
            'planned_end' => $plannedEnd,
        ]);
    }

    public function test_goal_without_tasks_is_not_warned(): void
    {
        $goal = $this->goal();

        $this->assertNull($goal->plannedFinish());
        $this->assertSame(0, $goal->daysPastDeadline());
        $this->assertFalse($goal->isPastDeadline());
    }

    public function test_task_ending_on_deadline_is_not_warned(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-11-30');

        $this->assertSame('2026-11-30', $goal->plannedFinish()->toDateString());
        $this->assertSame(0, $goal->daysPastDeadline());
        $this->assertFalse($goal->isPastDeadline());
    }

    public function test_task_ending_one_day_after_deadline_is_warned(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-12-01');

        $this->assertSame(1, $goal->daysPastDeadline());
        $this->assertTrue($goal->isPastDeadline());
    }

    public function test_latest_task_decides(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-11-10');
        $this->taskEndingOn($goal, '2026-12-05');
        $this->taskEndingOn($goal, '2026-11-29');

        $this->assertSame('2026-12-05', $goal->plannedFinish()->toDateString());
        $this->assertSame(5, $goal->daysPastDeadline());
    }

    public function test_other_goals_tasks_are_ignored(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-11-20');
        $this->taskEndingOn($this->goal(), '2027-03-01');

        $this->assertFalse($goal->isPastDeadline());
    }

    public function test_finished_goals_are_not_warned(): void
    {
        $achieved = $this->goal(['status' => GoalStatus::Achieved, 'achieved_at' => '2026-11-30']);
        $this->taskEndingOn($achieved, '2026-12-10');
        $cancelled = $this->goal(['status' => GoalStatus::Cancelled]);
        $this->taskEndingOn($cancelled, '2026-12-10');

        $this->assertSame(10, $achieved->daysPastDeadline());
        $this->assertFalse($achieved->isPastDeadline());
        $this->assertFalse($cancelled->isPastDeadline());
    }

    public function test_warning_follows_task_changes(): void
    {
        $goal = $this->goal();
        $task = $this->taskEndingOn($goal, '2026-12-03');
        $this->assertTrue($goal->isPastDeadline());

        // タスクの予定を締切内に直すと警告は消える
        $this->actingAs($goal->user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'planned_start' => '2026-11-01',
            'planned_end' => '2026-11-28',
            'estimated_minutes' => $task->estimated_minutes,
            'status' => $task->status->value,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($goal->isPastDeadline());
    }

    public function test_preloaded_value_matches_direct_value(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-12-02');
        $empty = $this->goal();

        $loaded = Goal::withPlannedFinish()->whereKey([$goal->id, $empty->id])->get()->keyBy('id');

        $this->assertSame('2026-12-02', $loaded[$goal->id]->plannedFinish()->toDateString());
        $this->assertSame(2, $loaded[$goal->id]->daysPastDeadline());
        $this->assertNull($loaded[$empty->id]->plannedFinish());
    }

    public function test_goal_page_shows_warning(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-12-03');

        $this->actingAs($goal->user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            ->assertSeeInOrder([self::WARNING, '2026-11-30', '2026-12-03', '3日超過']);
    }

    public function test_goal_page_has_no_warning_when_on_time(): void
    {
        $goal = $this->goal();
        $this->taskEndingOn($goal, '2026-11-30');

        $this->actingAs($goal->user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            ->assertDontSee(self::WARNING);
    }

    public function test_index_marks_only_late_goals_without_extra_queries(): void
    {
        $late = $this->goal(['title' => '遅れているゴール']);
        $this->taskEndingOn($late, '2026-12-02');
        $onTime = $this->goal(['user_id' => $late->user_id, 'title' => '間に合うゴール']);
        $this->taskEndingOn($onTime, '2026-11-15');
        $this->goal(['user_id' => $late->user_id, 'title' => 'タスクのないゴール']);

        DB::enableQueryLog();
        $response = $this->actingAs($late->user)->get(route('goals.index'));
        $taskQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], '"tasks"') || str_contains($q['query'], '`tasks`'));

        $response->assertOk()
            ->assertSeeInOrder(['遅れているゴール', '締切超過 2日'])
            ->assertSee('間に合うゴール')
            ->assertSee('タスクのないゴール');
        $this->assertSame(1, substr_count($response->getContent(), '締切超過'));
        // 一覧ではゴールごとにタスクを問い合わせない（withPlannedFinish でまとめて集計する）
        $this->assertCount(1, $taskQueries);
    }
}
