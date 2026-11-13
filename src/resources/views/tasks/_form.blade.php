{{-- タスクの作成・編集で共通の入力欄。$task と $statuses を受け取る --}}
<div>
    <x-input-label for="title" value="タイトル" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $task->title)" required autofocus maxlength="255" />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="planned_start" value="開始予定日" />
        <x-text-input id="planned_start" name="planned_start" type="date" class="mt-1 block w-full" :value="old('planned_start', $task->planned_start?->toDateString())" required />
        <x-input-error class="mt-2" :messages="$errors->get('planned_start')" />
    </div>

    <div>
        <x-input-label for="planned_end" value="終了予定日" />
        <x-text-input id="planned_end" name="planned_end" type="date" class="mt-1 block w-full" :value="old('planned_end', $task->planned_end?->toDateString())" required />
        <x-input-error class="mt-2" :messages="$errors->get('planned_end')" />
    </div>
</div>

<div>
    <x-input-label for="estimated_minutes" value="見積もり（分）" />
    <x-text-input id="estimated_minutes" name="estimated_minutes" type="number" min="1" max="60000" step="1" class="mt-1 block w-full sm:w-48" :value="old('estimated_minutes', $task->estimated_minutes)" required />
    <x-input-error class="mt-2" :messages="$errors->get('estimated_minutes')" />
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="status" value="状態" />
        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $task->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="completed_at" value="完了日（「完了」のとき。空なら今日）" />
        <x-text-input id="completed_at" name="completed_at" type="date" class="mt-1 block w-full" :value="old('completed_at', $task->completed_at?->toDateString())" />
        <x-input-error class="mt-2" :messages="$errors->get('completed_at')" />
    </div>
</div>
