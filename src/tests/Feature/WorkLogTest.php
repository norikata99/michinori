<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class WorkLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Date::setTestNow('2026-11-13 10:00:00');
    }

    /**
     * @return array<string, mixed>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'worked_on' => '2026-11-12',
            'minutes' => 90,
            'note' => '午前中に2問',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.show', $task))->assertRedirect(route('login'));
        $this->post(route('tasks.work-logs.store', $task), $this->validInput())->assertRedirect(route('login'));
        $this->assertDatabaseCount('work_logs', 0);
    }

    public function test_task_page_shows_its_logs_and_total(): void
    {
        $task = Task::factory()->create(['estimated_minutes' => 300]);
        WorkLog::factory()->for($task)->create(['minutes' => 90, 'note' => 'このタスクのログ']);
        WorkLog::factory()->for($task)->create(['minutes' => 45, 'note' => null]);
        WorkLog::factory()->create(['minutes' => 600, 'note' => '別のタスクのログ']);

        $this->actingAs($task->goal->user)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('このタスクのログ')
            ->assertDontSee('別のタスクのログ')
            // 90 + 45 = 135分。別のタスクの 600分 は含まない
            ->assertSeeInOrder(['合計', '2時間15分', '見積もり', '5時間']);
    }

    public function test_task_page_shows_zero_total_without_logs(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->goal->user)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('まだ作業ログがありません。')
            ->assertSeeInOrder(['合計', '0分']);
    }

    public function test_user_can_add_log_to_own_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->goal->user)
            ->post(route('tasks.work-logs.store', $task), $this->validInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.show', $task));

        $log = $task->workLogs()->sole();
        $this->assertSame('2026-11-12', $log->worked_on->toDateString());
        $this->assertSame(90, $log->minutes);
        $this->assertSame('午前中に2問', $log->note);
    }

    public function test_note_is_optional(): void
    {
        $task = Task::factory()->create();

        $this->actingAs($task->goal->user)
            ->post(route('tasks.work-logs.store', $task), $this->validInput(['note' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($task->workLogs()->sole()->note);
    }

    public function test_user_can_update_own_log(): void
    {
        $log = WorkLog::factory()->create();
        $user = $log->task->goal->user;

        $this->actingAs($user)->get(route('work-logs.edit', $log))->assertOk();

        $this->actingAs($user)
            ->put(route('work-logs.update', $log), $this->validInput(['minutes' => 30, 'worked_on' => '2026-11-13']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.show', $log->task));

        $log->refresh();
        $this->assertSame(30, $log->minutes);
        $this->assertSame('2026-11-13', $log->worked_on->toDateString());
    }

    public function test_user_can_delete_own_log(): void
    {
        $log = WorkLog::factory()->create();

        $this->actingAs($log->task->goal->user)
            ->delete(route('work-logs.destroy', $log))
            ->assertRedirect(route('tasks.show', $log->task));

        $this->assertModelMissing($log);
    }

    public function test_user_cannot_see_other_users_task_page(): void
    {
        $task = Task::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('tasks.show', $task))
            ->assertForbidden();
    }

    public function test_user_cannot_add_log_to_other_users_task(): void
    {
        $task = Task::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('tasks.work-logs.store', $task), $this->validInput())
            ->assertForbidden();

        $this->assertDatabaseCount('work_logs', 0);
    }

    public function test_user_cannot_change_other_users_log(): void
    {
        $log = WorkLog::factory()->create(['minutes' => 60]);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('work-logs.edit', $log))->assertForbidden();
        $this->actingAs($intruder)->put(route('work-logs.update', $log), $this->validInput(['minutes' => 999]))->assertForbidden();
        $this->actingAs($intruder)->delete(route('work-logs.destroy', $log))->assertForbidden();

        $this->assertModelExists($log);
        $this->assertSame(60, $log->fresh()->minutes);
    }

    public function test_task_id_in_input_is_ignored(): void
    {
        $task = Task::factory()->create();
        $otherTask = Task::factory()->create();

        $this->actingAs($task->goal->user)
            ->post(route('tasks.work-logs.store', $task), $this->validInput(['task_id' => $otherTask->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $task->workLogs()->count());
        $this->assertSame(0, $otherTask->workLogs()->count());
    }

    public function test_invalid_values_are_rejected(): void
    {
        $task = Task::factory()->create();
        $user = $task->goal->user;
        $store = route('tasks.work-logs.store', $task);

        $this->actingAs($user)->post($store, [])
            ->assertSessionHasErrors(['worked_on', 'minutes']);
        $this->actingAs($user)->post($store, $this->validInput(['worked_on' => '2026-11-14']))
            ->assertSessionHasErrors('worked_on');
        $this->actingAs($user)->post($store, $this->validInput(['minutes' => 0]))
            ->assertSessionHasErrors('minutes');
        $this->actingAs($user)->post($store, $this->validInput(['minutes' => 1441]))
            ->assertSessionHasErrors('minutes');
        $this->actingAs($user)->post($store, $this->validInput(['minutes' => 1.5]))
            ->assertSessionHasErrors('minutes');
        $this->actingAs($user)->post($store, $this->validInput(['note' => str_repeat('あ', 1001)]))
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('work_logs', 0);
    }

    public function test_logs_are_deleted_with_task(): void
    {
        $log = WorkLog::factory()->create();

        $log->task->delete();

        $this->assertModelMissing($log);
    }
}
