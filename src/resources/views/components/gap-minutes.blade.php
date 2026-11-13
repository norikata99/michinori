{{-- 工数のズレ（実績 − 見積もり）を表示する。正なら超過 --}}
@props(['value'])
@if ($value > 0)
    <span {{ $attributes->merge(['class' => 'text-red-700']) }}>+<x-minutes :value="$value" /> 超過</span>
@elseif ($value < 0)
    <span {{ $attributes->merge(['class' => 'text-green-700']) }}>−<x-minutes :value="-$value" /> 少ない</span>
@else
    <span {{ $attributes->merge(['class' => 'text-gray-700']) }}>±0</span>
@endif
