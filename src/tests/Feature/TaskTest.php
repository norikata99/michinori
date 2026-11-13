<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'title' => '過去問を1回分解く',
            'planned_start' => '2026-11-10',
            'planned_end' => '2026-11-12',
            'estimated_minutes' => 150,
            'status' => TaskStatus::Todo->value,
            'completed_at' => '',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $goal = Goal::factory()->create();

        $this->get(route('goals.tasks.create', $goal))->assertRedirect(route('login'));
        $this->post(route('goals.tasks.store', $goal), $this->validInput())->assertRedirect(route('login'));
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_goal_page_shows_its_tasks(): void
    {
        $goal = Goal::factory()->create();
        Task::factory()->for($goal)->create(['title' => 'このゴールのタスク', 'estimated_minutes' => 90]);
        Task::factory()->create(['title' => '別のゴールのタスク']);

        $this->actingAs($goal->user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            ->assertSee('このゴールのタスク')
            ->assertSee('1時間30分')
            ->assertDontSee('別のゴールのタスク');
    }

    public function test_user_can_create_task_under_own_goal(): void
    {
        $goal = Goal::factory()->create();

        $this->actingAs($goal->user)->get(route('goals.tasks.create', $goal))->assertOk();

        $this->actingAs($goal->user)
            ->post(route('goals.tasks.store', $goal), $this->validInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goals.show', $goal));

        $task = $goal->tasks()->sole();
        $this->assertSame('過去問を1回分解く', $task->title);
        $this->assertSame(150, $task->estimated_minutes);
        $this->assertSame(TaskStatus::Todo, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_task_may_end_after_goal_deadline(): void
    {
        // 締切超過の警告（#11）で気づけるよう、ゴールの期間外も受け付ける
        $goal = Goal::factory()->create(['planned_start' => '2026-11-01', 'planned_end' => '2026-11-30']);

        $this->actingAs($goal->user)
            ->post(route('goals.tasks.store', $goal), $this->validInput(['planned_end' => '2026-12-15']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $goal->tasks()->count());
    }

    public function test_completing_task_records_today(): void
    {
        Date::setTestNow('2026-11-13 10:00:00');
        $task = Task::factory()->create(['status' => TaskStatus::Doing]);

        $this->actingAs($task->goal->user)
            ->put(route('tasks.update', $task), $this->validInput(['status' => TaskStatus::Done->value]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goals.show', $task->goal));

        $task->refresh();
        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertSame('2026-11-13', $task->completed_at->toDateString());
    }

    public function test_completed_date_can_be_entered(): void
    {
        Date::setTestNow('2026-11-13');
        $task = Task::factory()->create();

        $this->actingAs($task->goal->user)
            ->put(route('tasks.update', $task), $this->validInput([
                'status' => TaskStatus::Done->value,
                'completed_at' => '2026-11-11',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-11-11', $task->fresh()->completed_at->toDateString());
    }

    public function test_reopening_task_clears_completed_date(): void
    {
        $task = Task::factory()->done()->create();

        $this->actingAs($task->goal->user)
            ->put(route('tasks.update', $task), $this->validInput(['status' => TaskStatus::Doing->value]))
            ->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame(TaskStatus::Doing, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_completed_date_is_kept_when_editing_done_task(): void
    {
        Date::setTestNow('2026-11-13');
        $task = Task::factory()->done()->create(['completed_at' => '2026-11-05']);

        $this->actingAs($task->goal->user)
            ->put(route('tasks.update', $task), $this->validInput([
                'title' => 'タイトルだけ変更',
                'status' => TaskStatus::Done->value,
                'completed_at' => '2026-11-05',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-11-05', $task->fresh()->completed_at->toDateString());
    }

    public function test_user_can_delete_own_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->goal->user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('goals.show', $task->goal));

        $this->assertModelMissing($task);
    }

    public function test_user_cannot_access_other_users_goal_page(): void
    {
        $goal = Goal::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('goals.show', $goal))
            ->assertForbidden();
    }

    public function test_user_cannot_create_task_under_other_users_goal(): void
    {
        $goal = Goal::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('goals.tasks.create', $goal))->assertForbidden();
        $this->actingAs($intruder)->post(route('goals.tasks.store', $goal), $this->validInput())->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_user_cannot_change_other_users_task(): void
    {
        $task = Task::factory()->create(['title' => '他人のタスク']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('tasks.edit', $task))->assertForbidden();
        $this->actingAs($intruder)->put(route('tasks.update', $task), $this->validInput(['title' => '書き換え']))->assertForbidden();
        $this->actingAs($intruder)->delete(route('tasks.destroy', $task))->assertForbidden();

        $this->assertModelExists($task);
        $this->assertSame('他人のタスク', $task->fresh()->title);
    }

    public function test_goal_id_in_input_is_ignored(): void
    {
        $goal = Goal::factory()->create();
        $otherGoal = Goal::factory()->create();

        $this->actingAs($goal->user)
            ->post(route('goals.tasks.store', $goal), $this->validInput(['goal_id' => $otherGoal->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $goal->tasks()->count());
        $this->assertSame(0, $otherGoal->tasks()->count());
    }

    public function test_required_fields_are_validated(): void
    {
        $goal = Goal::factory()->create();

        $this->actingAs($goal->user)
            ->post(route('goals.tasks.store', $goal), [])
            ->assertSessionHasErrors(['title', 'planned_start', 'planned_end', 'estimated_minutes', 'status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_invalid_values_are_rejected(): void
    {
        Date::setTestNow('2026-11-13');
        $goal = Goal::factory()->create();
        $user = $goal->user;
        $store = route('goals.tasks.store', $goal);

        $this->actingAs($user)->post($store, $this->validInput(['planned_start' => '2026-11-12', 'planned_end' => '2026-11-11']))
            ->assertSessionHasErrors('planned_end');
        $this->actingAs($user)->post($store, $this->validInput(['estimated_minutes' => 0]))
            ->assertSessionHasErrors('estimated_minutes');
        $this->actingAs($user)->post($store, $this->validInput(['estimated_minutes' => 1.5]))
            ->assertSessionHasErrors('estimated_minutes');
        $this->actingAs($user)->post($store, $this->validInput(['status' => 'unknown']))
            ->assertSessionHasErrors('status');
        $this->actingAs($user)->post($store, $this->validInput(['completed_at' => '2026-11-12']))
            ->assertSessionHasErrors('completed_at');
        $this->actingAs($user)->post($store, $this->validInput(['status' => TaskStatus::Done->value, 'completed_at' => '2026-11-14']))
            ->assertSessionHasErrors('completed_at');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_tasks_are_deleted_with_goal(): void
    {
        $task = Task::factory()->create();

        $task->goal->delete();

        $this->assertModelMissing($task);
    }

    public function test_database_rejects_unknown_status(): void
    {
        // CHECK 制約は MySQL のときだけ付けている（docs/adr/0004）
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('CHECK 制約は MySQL でだけ確認できる');
        }

        $goal = Goal::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('tasks')->insert([
            'goal_id' => $goal->id,
            'title' => '不正な状態',
            'planned_start' => '2026-11-01',
            'planned_end' => '2026-11-02',
            'estimated_minutes' => 30,
            'status' => 'unknown',
        ]);
    }
}
