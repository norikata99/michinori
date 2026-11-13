{{-- 締切超過の警告。進行中のゴールで、配下のタスクの最も遅い終了予定日が締切より後のときだけ出す --}}
@props(['goal'])
@if ($goal->isPastDeadline())
    <div {{ $attributes->merge(['class' => 'p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-md']) }} role="alert">
        <p class="font-semibold">このままでは締切に間に合いません</p>
        <p class="mt-1">
            締切 {{ $goal->planned_end->toDateString() }} に対して、タスクの終了予定日が最も遅いのは
            {{ $goal->plannedFinish()->toDateString() }}（{{ $goal->daysPastDeadline() }}日超過）です。
            タスクの予定か、ゴールの締切を見直してください。
        </p>
    </div>
@endif
