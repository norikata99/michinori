<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkLog;

// 作業ログは、タスクの親のゴールを作成したユーザー本人だけが変更できる
class WorkLogPolicy
{
    public function update(User $user, WorkLog $workLog): bool
    {
        return $user->id === $workLog->task->goal->user_id;
    }

    public function delete(User $user, WorkLog $workLog): bool
    {
        return $user->id === $workLog->task->goal->user_id;
    }
}
