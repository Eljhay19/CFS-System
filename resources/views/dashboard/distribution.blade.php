@php
    $ratedCount = $dashboardDistribution->sum('count');
    $colors = ['#dce1e8', '#b2d8ff', '#83bfff', '#1371e8', '#f58200'];
    $offset = 0;
@endphp
<section class="dashboard-box dashboard-chart-panel" data-dashboard-distribution>
    <div class="panel-title"><h2><x-icon name="chart-no-axes-column" />Rating Distribution</h2></div>
    <div class="dashboard-distribution">
        <svg class="dashboard-donut" viewBox="0 0 160 160" role="img" aria-label="Rating distribution, {{ $ratedCount }} rated responses">
            <circle cx="80" cy="80" r="62" fill="none" stroke="#edf1f6" stroke-width="28" />
            <g data-chart-ring>
                <g transform="rotate(-90 80 80)">
                    @foreach ($dashboardDistribution as $index => $bucket)
                        @php $portion = $ratedCount ? $bucket['count'] / $ratedCount * 100 : 0; @endphp
                        @if ($portion > 0)
                            <circle cx="80" cy="80" r="62" pathLength="100" fill="none" stroke="{{ $colors[$index] }}" stroke-width="28" stroke-dasharray="{{ $portion }} {{ 100 - $portion }}" stroke-dashoffset="{{ -$offset }}" data-rating-bucket="{{ $index }}" data-count="{{ $bucket['count'] }}"><title>{{ $bucket['label'] }}: {{ $bucket['count'] }} ({{ $bucket['percent'] }}%)</title></circle>
                        @endif
                        @php $offset += $portion; @endphp
                    @endforeach
                </g>
            </g>
            <text x="80" y="73" text-anchor="middle" class="donut-label">Ratings</text>
            <text x="80" y="101" text-anchor="middle" class="donut-count" data-rated-count>{{ $ratedCount }}</text>
        </svg>
        <ul class="dashboard-distribution-legend" aria-label="Average score ranges">
            @foreach ($dashboardDistribution as $index => $bucket)
                <li title="Average score above {{ $index * 2 }} and up to {{ ($index + 1) * 2 }}">
                    <span class="distribution-swatch" style="background: {{ $colors[$index] }};"></span>
                    <span>{{ $index * 2 + 1 }} &ndash; {{ $index * 2 + 2 }}</span>
                    <span>{{ $bucket['count'] }} ({{ $bucket['percent'] }}%)</span>
                </li>
            @endforeach
        </ul>
    </div>
    @if (!$ratedCount)<p class="dashboard-chart-empty">No rating answers for these filters.</p>@endif
</section>
