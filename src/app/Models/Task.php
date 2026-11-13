<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

// goal_id は入力から受け取らず、ゴールのリレーション経由で入れる
#[Fillable(['title', 'planned_start', 'planned_end', 'estimated_minutes', 'status', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // 完了日は状態に合わせる。どこから保存しても「完了なのに完了日がない」状態を作らないよう、モデルで揃える
        static::saving(function (Task $task) {
            if ($task->status !== TaskStatus::Done) {
                $task->completed_at = null;
            } elseif ($task->completed_at === null) {
                $task->completed_at = Date::today();
            }
        });
    }

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
            'estimated_minutes' => 'integer',
            'status' => TaskStatus::class,
            'completed_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<WorkLog, $this>
     */
    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }
}
