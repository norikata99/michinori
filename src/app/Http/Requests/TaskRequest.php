<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// タスクの作成と更新で共通の入力チェック。権限の確認はコントローラーで Policy を使う
class TaskRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // ゴールの期間からはみ出してもよい（締切超過の警告で気づけるようにするため）
            'planned_start' => ['required', 'date'],
            'planned_end' => ['required', 'date', 'after_or_equal:planned_start'],
            // 上限は入力ミス対策（1000時間）
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:60000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            // 完了日は「完了」のときだけ入れられる。空なら保存時に今日の日付が入る
            'completed_at' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'prohibited_unless:status,'.TaskStatus::Done->value,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'planned_start' => '開始予定日',
            'planned_end' => '終了予定日',
            'estimated_minutes' => '見積もり（分）',
            'status' => '状態',
            'completed_at' => '完了日',
        ];
    }
}
