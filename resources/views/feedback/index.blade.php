@extends('layouts.admin')

@section('title', 'Feedback')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@endpush

@php
    $overview = $feedbackOverview;
    $trendItems = $overview['dailyTrend']->values();
    $trendWidth = 260;
    $trendHeight = 94;
    $trendLeft = 24;
    $trendTop = 10;
    $trendPlotWidth = 210;
    $trendPlotHeight = 58;
    $trendMax = max(1, (int) $trendItems->max('count'));
    $trendPoints = '';
    $trendDots = [];

    foreach ($trendItems as $index => $point) {
        $x = $trendLeft + ($trendItems->count() > 1 ? ($index * ($trendPlotWidth / ($trendItems->count() - 1))) : 0);
        $y = $trendTop + ($trendPlotHeight - (((int) $point['count'] / $trendMax) * $trendPlotHeight));
        $trendPoints .= round($x, 1) . ',' . round($y, 1) . ' ';
        $trendDots[] = ['x' => round($x, 1), 'y' => round($y, 1)];
    }

    $statusGradient = $overview['statusSegments']->filter(function ($segment) {
        return $segment['count'] > 0;
    })->map(function ($segment) {
        return $segment['color'] . ' ' . $segment['from'] . 'deg ' . $segment['to'] . 'deg';
    })->implode(', ');
    $statusGradient = $statusGradient ?: '#e9eef5 0deg 360deg';
    $satisfactionPercent = min(100, max(0, ($overview['averageRating'] / 10) * 100));
@endphp

@section('admin_content')
    <section class="feedback-dashboard" data-live-url="{{ route('dashboard.stats', $filters) }}" data-live-revision="{{ $liveRevision }}">
        <div class="feedback-dashboard-head">
            <div>
                <p class="muted">Submitted Responses</p>
                <h1>Feedback</h1>
                <p class="feedback-dashboard-copy">Compact charts and customer feedback insights.</p>
            </div>
            <div class="feedback-head-actions">
                <details class="dashboard-export-menu" data-export-menu>
                    <summary class="button dashboard-export compact-button"><x-icon name="download" />Export Results<x-icon name="chevron-down" /></summary>
                    <div class="dashboard-export-options">
                        <strong>Filtered results</strong>
                        <a aria-label="Export Filtered Results" href="{{ route('dashboard.export', $filters + ['format' => 'csv']) }}">Excel (CSV)</a>
                        <a href="{{ route('dashboard.export', $filters + ['format' => 'pdf']) }}">Filtered Results (PDF)</a>
                        <a href="{{ route('dashboard.export', $filters + ['format' => 'jpg']) }}">Filtered Results (JPG)</a>
                        <strong>All records</strong>
                        <a aria-label="Export All Records" href="{{ route('dashboard.export', ['all' => 1, 'format' => 'csv']) }}">Excel (CSV)</a>
                        <a href="{{ route('dashboard.export', ['all' => 1, 'format' => 'pdf']) }}">All Records (PDF)</a>
                        <a href="{{ route('dashboard.export', ['all' => 1, 'format' => 'jpg']) }}">All Records (JPG)</a>
                    </div>
                </details>
                @if (! empty($search) || ! empty($status) || ! empty($unread))
                    <a class="panel-link" href="{{ route('feedback.index') }}">Clear filters</a>
                @endif
            </div>
        </div>

        @if (! empty($search))
            <p class="muted">Search results for "{{ $search }}".</p>
        @endif
        @if (! empty($status))
            <p class="muted">Showing {{ $status }} feedback.</p>
        @endif
        @if (! empty($unread))
            <p class="muted">Showing unread notification feedback.</p>
        @endif

        @include('partials.feedback-filters')
        <div class="feedback-kpi-grid">
            <button class="feedback-kpi feedback-kpi-link" type="button" data-feedback-popup="total" aria-haspopup="dialog" aria-controls="feedback-popup-total">
                <svg class="feedback-kpi-icon feedback-blue" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                <span>Total Records</span>
                <strong>{{ $overview['total'] }}</strong>
                <small>Current records</small>
            </button>
            <button class="feedback-kpi feedback-kpi-link" type="button" data-feedback-popup="average" aria-haspopup="dialog" aria-controls="feedback-popup-average">
                <svg class="feedback-kpi-icon feedback-gold" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"></path></svg>
                <span>Average Rating</span>
                <strong>{{ number_format($overview['averageRating'], 1) }}</strong>
                <small>Out of 10</small>
            </button>
            <button class="feedback-kpi feedback-kpi-link" type="button" data-feedback-popup="resolved" aria-haspopup="dialog" aria-controls="feedback-popup-resolved">
                <svg class="feedback-kpi-icon feedback-green" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path><circle cx="12" cy="12" r="9"></circle></svg>
                <span>Resolved</span>
                <strong>{{ $overview['resolved'] }}</strong>
                <small>{{ $overview['total'] ? round(($overview['resolved'] / $overview['total']) * 100) : 0 }}% of total</small>
            </button>
            <button class="feedback-kpi feedback-kpi-link" type="button" data-feedback-popup="needs_action" aria-haspopup="dialog" aria-controls="feedback-popup-needs_action">
                <svg class="feedback-kpi-icon feedback-orange" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v6"></path><path d="M12 17h.01"></path></svg>
                <span>Needs Action</span>
                <strong>{{ $overview['needsAction'] }}</strong>
                <small>{{ $overview['total'] ? round(($overview['needsAction'] / $overview['total']) * 100) : 0 }}% of total</small>
            </button>
        </div>

        @foreach ([
            ['key' => 'total', 'title' => 'Total Records', 'subtitle' => 'All feedback in the current filters.', 'empty' => 'No feedback records found.'],
            ['key' => 'average', 'title' => 'Average Rating', 'subtitle' => 'Feedback records with rating answers.', 'empty' => 'No rated feedback records found.'],
            ['key' => 'resolved', 'title' => 'Resolved Feedback', 'subtitle' => 'Feedback marked resolved in the current filters.', 'empty' => 'No resolved feedback yet.'],
            ['key' => 'needs_action', 'title' => 'Feedback Needing Action', 'subtitle' => 'Customer concerns that need admin attention.', 'empty' => 'No feedback currently needs action.'],
        ] as $popup)
            <dialog class="feedback-record-dialog" id="feedback-popup-{{ $popup['key'] }}" aria-labelledby="feedback-popup-title-{{ $popup['key'] }}">
                <header class="feedback-record-dialog-head">
                    <div><h2 id="feedback-popup-title-{{ $popup['key'] }}">{{ $popup['title'] }}</h2><p>{{ $popup['subtitle'] }}</p></div>
                    <button type="button" class="feedback-record-dialog-close" data-feedback-popup-close aria-label="Close list">&times;</button>
                </header>
                <div class="feedback-record-dialog-list" tabindex="0" aria-label="{{ $popup['title'] }} list">
                    @forelse ($overview['metricLists'][$popup['key']] as $record)
                        <a class="feedback-record-dialog-item" href="{{ route('feedback.detail', $record) }}">
                            <span class="feedback-record-dialog-item-head"><strong>{{ $record->displayCode() }}</strong><span>{{ $record->status }}</span></span>
                            <span class="feedback-record-dialog-item-meta">{{ $record->customerLabel() }} <span aria-hidden="true">·</span> Rating {{ $record->ratingAverage() === null ? 'N/A' : number_format($record->ratingAverage(), 1) . '/10' }}</span>
                            <span class="feedback-record-dialog-comment">{{ $record->firstComment() ?: 'No written comment.' }}</span>
                            <span class="feedback-record-dialog-item-foot"><time datetime="{{ optional($record->submitted_at)->toIso8601String() }}">{{ optional($record->submitted_at)->format('M d, Y · h:i A') }}</time><span>View details →</span></span>
                        </a>
                    @empty
                        <p class="feedback-record-dialog-empty">{{ $popup['empty'] }}</p>
                    @endforelse
                </div>
            </dialog>
        @endforeach

        <div class="feedback-chart-grid">
            <article class="feedback-chart-card feedback-chart-wide">
                <div class="feedback-card-title">
                    <h2>Rating Distribution</h2>
                    <span>1-10 scores converted to 5 stars</span>
                </div>
                <div class="feedback-rating-bars">
                    @foreach ([5, 4, 3, 2, 1] as $stars)
                        @php
                            $count = $overview['ratingBuckets'][$stars] ?? 0;
                            $percent = $overview['total'] ? round(($count / $overview['total']) * 100, 1) : 0;
                        @endphp
                        <div class="feedback-rating-row">
                            <span>{{ $stars }} <span class="stars">&#9733;</span></span>
                            <span class="feedback-rating-track"><span style="width: {{ $percent }}%;"></span></span>
                            <span>{{ $count }} ({{ $percent }}%)</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="feedback-chart-card">
                <div class="feedback-card-title">
                    <h2>Status Mix</h2>
                    <span>Current workflow</span>
                </div>
                <div class="feedback-donut-wrap">
                    <div class="feedback-donut" style="background: conic-gradient({{ $statusGradient }});">
                        <span>{{ $overview['total'] }}</span>
                    </div>
                    <div class="feedback-legend">
                        @foreach ($overview['statusSegments'] as $segment)
                            <span><i style="background: {{ $segment['color'] }}"></i>{{ $segment['label'] }}: {{ $segment['count'] }}</span>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="feedback-chart-card">
                <div class="feedback-card-title">
                    <h2>Satisfaction</h2>
                    <span>Average score</span>
                </div>
                <div class="feedback-gauge" style="--score-deg: {{ round($satisfactionPercent * 1.8, 1) }}deg;">
                    <div class="feedback-gauge-arc"></div>
                    <strong>{{ number_format($overview['averageRating'], 1) }}</strong>
                    <span>out of 10</span>
                </div>
            </article>

            <article class="feedback-chart-card feedback-chart-line">
                <div class="feedback-card-title">
                    <h2>{{ ucfirst($filters['period'] ?? 'daily') }} Feedback</h2>
                    <span>Last 6 {{ ['daily'=>'days', 'weekly'=>'weeks', 'monthly'=>'months'][$filters['period'] ?? 'daily'] }}</span>
                </div>
                <svg class="feedback-mini-line" viewBox="0 0 {{ $trendWidth }} {{ $trendHeight }}" role="img" aria-label="Daily feedback trend">
                    <line x1="{{ $trendLeft }}" y1="{{ $trendTop + $trendPlotHeight }}" x2="{{ $trendLeft + $trendPlotWidth }}" y2="{{ $trendTop + $trendPlotHeight }}" stroke="#e7edf5" stroke-width="1"></line>
                    @foreach ($trendItems as $index => $point)
                        @php $x = $trendDots[$index]['x']; @endphp
                        <line x1="{{ $x }}" y1="{{ $trendTop }}" x2="{{ $x }}" y2="{{ $trendTop + $trendPlotHeight }}" stroke="#eef2f7" stroke-width="1"></line>
                        <text x="{{ $x - 13 }}" y="{{ $trendTop + $trendPlotHeight + 18 }}">{{ $point['label'] }}</text>
                    @endforeach
                    <polyline points="{{ trim($trendPoints) }}" fill="none" stroke="#045eb7" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                    @foreach ($trendDots as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3.2" fill="#07183a"></circle>
                    @endforeach
                </svg>
            </article>
        </div>

        <section class="feedback-recent-section" aria-labelledby="recent-feedback-heading">
            <div class="feedback-table-head">
                <div>
                    <h2 id="recent-feedback-heading">Recent Feedback</h2>
                    <p>Latest submitted customer feedback.</p>
                </div>
                <a class="feedback-text-action feedback-full-list-link" href="{{ route('feedback.index') }}">View Full List <span aria-hidden="true">→</span></a>
            </div>

            <div class="table-wrap feedback-table-wrap" role="region" aria-label="Recent feedback list" tabindex="0">
                <table class="recent-table feedback-recent-table">
                <colgroup>
                    <col class="feedback-col-id">
                    <col class="feedback-col-customer">
                    <col class="feedback-col-rating">
                    <col class="feedback-col-comment">
                    <col class="feedback-col-status">
                    <col class="feedback-col-datetime">
                    <col class="feedback-col-action">
                </colgroup>
                <thead>
                    <tr>
                        <th>Feedback ID</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedbackItems as $item)
                        <tr>
                            <td class="feedback-id-cell">{{ $item->displayCode() }}</td>
                            <td class="feedback-customer-cell">{{ $item->customerLabel() }}</td>
                            <td>
                                <span class="stars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        {!! $i <= round(($item->ratingAverage() ?: 0) / 2) ? '&#9733;' : '&#9734;' !!}
                                    @endfor
                                </span>
                                {{ $item->ratingAverage() ?: 'N/A' }}/10
                            </td>
                            <td><span class="feedback-comment-preview" title="{{ $item->firstComment() ?: 'No comment provided.' }}">{{ $item->firstComment() ?: 'No comment provided.' }}</span></td>
                            <td>
                                <span class="feedback-status-text">{{ $item->status }}</span>
                            </td>
                            <td><span class="feedback-date-time"><span>{{ optional($item->submitted_at)->format('M d, Y') }}</span><time datetime="{{ optional($item->submitted_at)->toIso8601String() }}">{{ optional($item->submitted_at)->format('h:i A') }}</time></span></td>
                            <td><a class="feedback-text-action" href="{{ route('feedback.detail', $item) }}"><x-icon name="eye" /><span>View</span></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">No submitted feedback yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>

            <div class="feedback-recent-pagination">{{ $feedbackItems->links() }}</div>
        </section>
    </section>
    <script src="{{ asset('js/feedback-metric-popups.js') }}?v={{ filemtime(public_path('js/feedback-metric-popups.js')) }}" defer></script>
@endsection
