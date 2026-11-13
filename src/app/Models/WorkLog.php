<?php

namespace App\Models;

use Database\Factories\WorkLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// task_id は入力から受け取らず、タスクのリレーション経由で入れる
#[Fillable(['worked_on', 'minutes', 'note'])]
class WorkLog extends Model
{
    /** @use HasFactory<WorkLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
