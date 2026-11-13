{{-- 作業ログの追加・編集で共通の入力欄。$workLog を受け取る --}}
<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="worked_on" value="作業日" />
        <x-text-input id="worked_on" name="worked_on" type="date" class="mt-1 block w-full" :value="old('worked_on', $workLog->worked_on?->toDateString())" required />
        <x-input-error class="mt-2" :messages="$errors->get('worked_on')" />
    </div>

    <div>
        <x-input-label for="minutes" value="作業時間（分）" />
        <x-text-input id="minutes" name="minutes" type="number" min="1" max="1440" step="1" class="mt-1 block w-full" :value="old('minutes', $workLog->minutes)" required />
        <x-input-error class="mt-2" :messages="$errors->get('minutes')" />
    </div>
</div>

<div>
    <x-input-label for="note" value="メモ（任意）" />
    <textarea id="note" name="note" rows="2" maxlength="1000" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('note', $workLog->note) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('note')" />
</div>
