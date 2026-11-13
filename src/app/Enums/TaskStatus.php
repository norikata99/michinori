<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case Doing = 'doing';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => '未着手',
            self::Doing => '作業中',
            self::Done => '完了',
        };
    }
}
