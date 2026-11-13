{{-- 日程のズレ（終わった日 − 予定の終了日）を表示する。正なら遅れ。終わっていなければ null --}}
@props(['value'])
@if ($value === null)
    <span {{ $attributes->merge(['class' => 'text-gray-500']) }}>—</span>
@elseif ($value > 0)
    <span {{ $attributes->merge(['class' => 'text-red-700']) }}>{{ $value }}日遅れ</span>
@elseif ($value < 0)
    <span {{ $attributes->merge(['class' => 'text-green-700']) }}>{{ -$value }}日前倒し</span>
@else
    <span {{ $attributes->merge(['class' => 'text-gray-700']) }}>予定どおり</span>
@endif
