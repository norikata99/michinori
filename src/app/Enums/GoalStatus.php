<?php

namespace App\Enums;

enum GoalStatus: string
{
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
    // 試験に落ちて計画を立て直す場合などに使う
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => '進行中',
            self::Achieved => '達成',
            self::Cancelled => '中止',
        };
    }
}
