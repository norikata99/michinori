<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

// タスクは、親のゴールを作成したユーザー本人だけが見たり変更したりできる
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->goal->user_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->goal->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->goal->user_id;
    }
}
