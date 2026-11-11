{{-- ゴールの作成・編集で共通の入力欄。$goal と $statuses を受け取る --}}
<div>
    <x-input-label for="title" value="タイトル" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $goal->title)" required autofocus maxlength="255" />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="planned_start" value="開始予定日" />
        <x-text-input id="planned_start" name="planned_start" type="date" class="mt-1 block w-full" :value="old('planned_start', $goal->planned_start?->toDateString())" required />
        <x-input-error class="mt-2" :messages="$errors->get('planned_start')" />
    </div>

    <div>
        <x-input-label for="planned_end" value="締切" />
        <x-text-input id="planned_end" name="planned_end" type="date" class="mt-1 block w-full" :value="old('planned_end', $goal->planned_end?->toDateString())" required />
        <x-input-error class="mt-2" :messages="$errors->get('planned_end')" />
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="status" value="状態" />
        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $goal->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="achieved_at" value="達成日（状態が「達成」のときだけ入力）" />
        <x-text-input id="achieved_at" name="achieved_at" type="date" class="mt-1 block w-full" :value="old('achieved_at', $goal->achieved_at?->toDateString())" />
        <x-input-error class="mt-2" :messages="$errors->get('achieved_at')" />
    </div>
</div>
