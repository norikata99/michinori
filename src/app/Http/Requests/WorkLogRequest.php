<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

// 作業ログの追加と更新で共通の入力チェック。権限の確認はコントローラーで Policy を使う
class WorkLogRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 実績の記録なので、未来の日付は受け付けない
            'worked_on' => ['required', 'date', 'before_or_equal:today'],
            // 1件は1日分まで（24時間）
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'worked_on' => '作業日',
            'minutes' => '作業時間（分）',
            'note' => 'メモ',
        ];
    }
}
