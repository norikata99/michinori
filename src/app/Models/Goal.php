<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Support\Progress;
use Carbon\CarbonInterface;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

// user_id は入力から受け取らず、ログイン中のユーザーのリレーション経由で入れる
#[Fillable(['title', 'planned_start', 'planned_end', 'achieved_at', 'status'])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_end' => 'date',
            'achieved_at' => 'date',
            'status' => GoalStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * 配下のタスクの作業ログ
     *
     * @return HasManyThrough<WorkLog, Task, $this>
     */
    public function workLogs(): HasManyThrough
    {
        return $this->hasManyThrough(WorkLog::class, Task::class);
    }

    /**
     * ゴール全体の予定と実績のズレ。見積もり・実績は配下のタスクの合計。
     * 日程のズレは、締切（planned_end）と達成日（achieved_at）の差
     */
    public function progress(): Progress
    {
        return Progress::fromAggregates(
            estimatedMinutes: (int) $this->tasks()->sum('estimated_minutes'),
            actualMinutes: $this->workLogs()->sum('work_logs.minutes'),
            plannedEnd: $this->planned_end,
            finishedOn: $this->achieved_at,
            actualStart: $this->workLogs()->min('work_logs.worked_on'),
        );
    }

    /**
     * 計画上の完了日。配下のタスクの終了予定日のうち、最も遅い日。タスクがなければ null
     */
    public function plannedFinish(): ?CarbonInterface
    {
        // withPlannedFinish() で読み込んでいればそれを使う。タスクが0件だと結果は null になるため、値ではなく「読み込んだかどうか」で判断する
        $value = array_key_exists('tasks_max_planned_end', $this->attributes)
            ? $this->tasks_max_planned_end
            : $this->tasks()->max('planned_end');

        // MAX の結果は DB によって文字列で返るため、日付に直す
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    /**
     * 締切を過ぎる日数。計画上の完了日 − 締切。間に合う場合（同じ日を含む）や、タスクがない場合は 0
     */
    public function daysPastDeadline(): int
    {
        $finish = $this->plannedFinish();

        if ($finish === null) {
            return 0;
        }

        return max(0, (int) $this->planned_end->copy()->startOfDay()->diffInDays($finish, absolute: false));
    }

    /**
     * 締切超過の警告を出すか。進行中のゴールで、計画上の完了日が締切より後のとき（docs/design.md 4章）。
     * 達成・中止したゴールは、計画を直す意味がないので警告しない
     */
    public function isPastDeadline(): bool
    {
        return $this->status === GoalStatus::InProgress && $this->daysPastDeadline() > 0;
    }

    /**
     * 一覧で N+1 にならないよう、計画上の完了日をまとめて集計する
     *
     * @param  Builder<Goal>  $query
     */
    #[Scope]
    protected function withPlannedFinish(Builder $query): void
    {
        $query->withMax('tasks', 'planned_end');
    }
}
