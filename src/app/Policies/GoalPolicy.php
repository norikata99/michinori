<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

// ゴールは作成したユーザー本人だけが見たり変更したりできる
class GoalPolicy
{
    public function view(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function update(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }
}
