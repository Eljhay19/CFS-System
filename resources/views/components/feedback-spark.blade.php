@props(['points', 'color' => 'blue'])
@php $maximum = max(1, $points->max('count')); @endphp
<i class="feedback-kpi-spark {{ $color }}" role="img" aria-label="Recent feedback counts: {{ $points->pluck('count')->implode(', ') }}">
    @foreach ($points as $point)
        <b style="height: {{ round($point['count'] / $maximum * 100) }}%;" title="{{ $point['label'] }}: {{ $point['count'] }}"></b>
    @endforeach
</i>
