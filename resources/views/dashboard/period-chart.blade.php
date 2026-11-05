@php
    $series = $dashboardSeries;
    $count = $series->count();
    $left = 32; $top = 12; $width = 300; $height = 120;
    $step = max(1, (int) ceil(($series->max('count') ?: 1) / 4));
    $maximum = $volume ? $step * 4 : 10;
    $ticks = $volume ? 4 : 5;
    $spacing = $width / max(1, $count);
    $points = []; $segments = []; $segment = [];
    foreach ($series as $index => $point) {
        $x = $left + $spacing * ($index + .5);
        $value = $volume ? $point['count'] : $point['average'];
        $y = $top + $height - ($value === null ? 0 : $value / $maximum * $height);
        $points[] = ['x' => $x, 'y' => $y, 'value' => $value];
        if ($value === null) {
            if ($segment) $segments[] = $segment;
            $segment = [];
        } else $segment[] = round($x, 2) . ',' . round($y, 2);
    }
    if ($segment) $segments[] = $segment;
    $labelIndices = $count <= 5 ? range(0, max(0, $count - 1)) : array_map(fn ($index) => (int) round($index * ($count - 1) / 4), range(0, 4));
@endphp
<section class="dashboard-box dashboard-chart-panel" data-dashboard-chart="{{ $volume ? 'volume' : 'trend' }}" data-period="{{ $filters['period'] ?? 'daily' }}">
    <div class="panel-title"><h2><x-icon :name="$volume ? 'chart-no-axes-column-increasing' : 'trending-up'" />{{ $volume ? 'Feedback Volume' : 'Rating Trend' }}</h2></div>
    <svg class="dashboard-period-chart" viewBox="0 0 350 170" role="img" aria-label="{{ ucfirst($filters['period'] ?? 'daily') }} {{ $volume ? 'feedback counts' : 'average ratings out of 10' }} by submission period">
        @for ($tick = 0; $tick <= $ticks; $tick++)
            @php $y = $top + $height - $tick / $ticks * $height; @endphp
            <line x1="{{ $left }}" y1="{{ $y }}" x2="{{ $left + $width }}" y2="{{ $y }}" class="chart-gridline" />
            <text x="{{ $left - 9 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $tick * $maximum / $ticks }}</text>
        @endfor
        @foreach ($series as $index => $point)
            @php $position = $points[$index]; @endphp
            @if (in_array($index, $labelIndices, true))
                <line x1="{{ $position['x'] }}" y1="{{ $top }}" x2="{{ $position['x'] }}" y2="{{ $top + $height }}" class="chart-gridline" />
                <text x="{{ $position['x'] }}" y="155" text-anchor="middle">{{ $point['label'] }}</text>
            @endif
            @if ($volume)
                @php $barWidth = min(32, $spacing * .5); @endphp
                <rect x="{{ $position['x'] - $barWidth / 2 }}" y="{{ $position['y'] }}" width="{{ $barWidth }}" height="{{ $top + $height - $position['y'] }}" rx="1" class="dashboard-volume-bar" data-chart-bar data-count="{{ $point['count'] }}"><title>{{ $point['description'] }}: {{ $point['count'] }} responses</title></rect>
                @if ($count <= 12)<text x="{{ $position['x'] }}" y="{{ $position['y'] - 6 }}" text-anchor="middle" class="chart-value">{{ $point['count'] }}</text>@endif
            @endif
        @endforeach
        @if (!$volume)
            @foreach ($segments as $segment)
                <polyline points="{{ implode(' ', $segment) }}" class="dashboard-trend-line" data-chart-line />
            @endforeach
            @foreach ($points as $index => $point)
                @if ($point['value'] !== null)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" class="dashboard-trend-dot" data-chart-dot data-average="{{ $point['value'] }}"><title>{{ $series[$index]['description'] }}: {{ $point['value'] }}/10 ({{ $series[$index]['count'] }} responses)</title></circle>
                @endif
            @endforeach
        @endif
        @if (!$count || (!$volume && $series->whereNotNull('average')->isEmpty()))
            <text x="190" y="73" text-anchor="middle" class="chart-empty-label">{{ $volume ? 'No feedback for these filters' : 'No ratings for these filters' }}</text>
        @endif
    </svg>
</section>
