<?php

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// ゴールの作成と更新で共通の入力チェック。権限の確認はコントローラーで Policy を使う
class GoalRequest extends FormRequest
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
            'planned_start' => ['required', 'date'],
            'planned_end' => ['required', 'date', 'after_or_equal:planned_start'],
            'status' => ['required', Rule::enum(GoalStatus::class)],
            // 達成日は「達成」のときだけ入れる。未来の日付は受け付けない
            'achieved_at' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'required_if:status,'.GoalStatus::Achieved->value,
                'prohibited_unless:status,'.GoalStatus::Achieved->value,
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
            'planned_end' => '締切',
            'status' => '状態',
            'achieved_at' => '達成日',
        ];
    }
}
