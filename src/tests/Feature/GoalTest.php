<?php

namespace Tests\Feature;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'title' => '基本情報技術者試験に合格する',
            'planned_start' => '2026-11-01',
            'planned_end' => '2027-01-31',
            'status' => GoalStatus::InProgress->value,
            'achieved_at' => '',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('goals.index'))->assertRedirect(route('login'));
        $this->post(route('goals.store'), $this->validInput())->assertRedirect(route('login'));
        $this->assertDatabaseCount('goals', 0);
    }

    public function test_index_shows_only_own_goals(): void
    {
        $user = User::factory()->create();
        Goal::factory()->for($user)->create(['title' => '自分のゴール']);
        Goal::factory()->create(['title' => '他人のゴール']);

        $this->actingAs($user)
            ->get(route('goals.index'))
            ->assertOk()
            ->assertSee('自分のゴール')
            ->assertDontSee('他人のゴール');
    }

    public function test_create_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('goals.create'))
            ->assertOk();
    }

    public function test_user_can_create_goal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('goals.store'), $this->validInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goals.index'));

        $goal = $user->goals()->sole();
        $this->assertSame('基本情報技術者試験に合格する', $goal->title);
        $this->assertSame('2027-01-31', $goal->planned_end->toDateString());
        $this->assertSame(GoalStatus::InProgress, $goal->status);
        $this->assertNull($goal->achieved_at);
    }

    public function test_user_id_in_input_is_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('goals.store'), $this->validInput(['user_id' => $other->id]))
            ->assertRedirect(route('goals.index'));

        $this->assertSame(1, $user->goals()->count());
        $this->assertSame(0, $other->goals()->count());
    }

    public function test_user_can_update_own_goal(): void
    {
        Date::setTestNow('2027-02-01');
        $goal = Goal::factory()->create();

        $this->actingAs($goal->user)
            ->put(route('goals.update', $goal), $this->validInput([
                'title' => '変更後',
                'status' => GoalStatus::Achieved->value,
                'achieved_at' => '2027-01-20',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('goals.index'));

        $goal->refresh();
        $this->assertSame('変更後', $goal->title);
        $this->assertSame(GoalStatus::Achieved, $goal->status);
        $this->assertSame('2027-01-20', $goal->achieved_at->toDateString());
    }

    public function test_user_can_delete_own_goal(): void
    {
        $goal = Goal::factory()->create();

        $this->actingAs($goal->user)
            ->delete(route('goals.destroy', $goal))
            ->assertRedirect(route('goals.index'));

        $this->assertModelMissing($goal);
    }

    public function test_user_cannot_access_other_users_goal(): void
    {
        $goal = Goal::factory()->create(['title' => '他人のゴール']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('goals.edit', $goal))->assertForbidden();
        $this->actingAs($intruder)->put(route('goals.update', $goal), $this->validInput(['title' => '書き換え']))->assertForbidden();
        $this->actingAs($intruder)->delete(route('goals.destroy', $goal))->assertForbidden();

        $this->assertModelExists($goal);
        $this->assertSame('他人のゴール', $goal->fresh()->title);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), [])
            ->assertSessionHasErrors(['title', 'planned_start', 'planned_end', 'status']);

        $this->assertDatabaseCount('goals', 0);
    }

    public function test_planned_end_must_not_be_before_planned_start(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), $this->validInput(['planned_start' => '2027-01-31', 'planned_end' => '2027-01-30']))
            ->assertSessionHasErrors('planned_end');
    }

    public function test_status_must_be_known_value(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), $this->validInput(['status' => 'unknown']))
            ->assertSessionHasErrors('status');
    }

    public function test_achieved_goal_requires_achieved_at(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), $this->validInput(['status' => GoalStatus::Achieved->value]))
            ->assertSessionHasErrors('achieved_at');
    }

    public function test_achieved_at_is_rejected_unless_achieved(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), $this->validInput(['achieved_at' => '2026-11-10']))
            ->assertSessionHasErrors('achieved_at');
    }

    public function test_achieved_at_cannot_be_in_the_future(): void
    {
        Date::setTestNow('2026-11-11');

        $this->actingAs(User::factory()->create())
            ->post(route('goals.store'), $this->validInput([
                'status' => GoalStatus::Achieved->value,
                'achieved_at' => '2026-11-12',
            ]))
            ->assertSessionHasErrors('achieved_at');
    }

    public function test_goals_are_deleted_with_user(): void
    {
        $goal = Goal::factory()->create();

        $goal->user->delete();

        $this->assertModelMissing($goal);
    }
}
