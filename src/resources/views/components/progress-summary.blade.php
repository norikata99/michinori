{{-- 予定と実績のズレのまとめ。$progress は App\Support\Progress。$finishedLabel は「完了日」「達成日」など --}}
@props(['progress', 'finishedLabel'])
<dl {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-4 sm:grid-cols-5 text-sm']) }}>
    <div>
        <dt class="text-gray-600">見積もり</dt>
        <dd class="mt-1 font-semibold text-gray-900"><x-minutes :value="$progress->estimatedMinutes" /></dd>
    </div>
    <div>
        <dt class="text-gray-600">実績</dt>
        <dd class="mt-1 font-semibold text-gray-900"><x-minutes :value="$progress->actualMinutes" /></dd>
    </div>
    <div>
        <dt class="text-gray-600">工数のズレ</dt>
        <dd class="mt-1 font-semibold"><x-gap-minutes :value="$progress->effortGapMinutes()" /></dd>
    </div>
    <div>
        <dt class="text-gray-600">実績開始日</dt>
        <dd class="mt-1 font-semibold text-gray-900">{{ $progress->actualStart?->toDateString() ?? '—' }}</dd>
    </div>
    <div>
        <dt class="text-gray-600">日程のズレ（{{ $finishedLabel }}）</dt>
        <dd class="mt-1 font-semibold"><x-gap-days :value="$progress->scheduleGapDays()" /></dd>
    </div>
</dl>
