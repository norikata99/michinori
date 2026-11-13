<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

// タスクは、親のゴールを作成したユーザー本人だけが変更できる
class TaskPolicy
{
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->goal->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->goal->user_id;
    }
}
