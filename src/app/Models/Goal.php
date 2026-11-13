<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Support\Progress;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
}
