{{-- 分を「1時間30分」の形で表示する --}}
@props(['value'])
@php($hours = intdiv($value, 60))
@php($minutes = $value % 60)
<span {{ $attributes }}>{{ $hours > 0 ? $hours.'時間' : '' }}{{ $minutes > 0 || $hours === 0 ? $minutes.'分' : '' }}</span>
