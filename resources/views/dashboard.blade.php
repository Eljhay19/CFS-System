@extends('layouts.admin')

@section('title', 'Feedback Dashboard')
@section('body_class', 'admin-screen admin-dashboard-screen')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@endpush

@php
    $chartItems = $ratingTrend->values();
    $chartWidth = 360;
    $chartHeight = 164;
    $plotLeft = 34;
    $plotTop = 12;
    $plotWidth = 298;
    $plotHeight = 112;
    $chartPoints = '';
    $chartDots = [];

    foreach ($chartItems as $index => $point) {
        $x = $plotLeft + ($chartItems->count() > 1 ? ($index * ($plotWidth / ($chartItems->count() - 1))) : 0);
        $y = $plotTop + ($plotHeight - (((float) $point['average'] / 10) * $plotHeight));
        $chartPoints .= round($x, 1) . ',' . round($y, 1) . ' ';
        $chartDots[] = ['x' => round($x, 1), 'y' => round($y, 1)];
    }

    $responseRate = $totalFeedback ? round(($resolvedFeedback / $totalFeedback) * 100) : 0;
    $activeDashboardSection = $activeDashboardSection ?? null;
    $activeFeedbackFilter = $activeFeedbackFilter ?? 'all';
    $scopeFilters = \Illuminate\Support\Arr::except($filters, ['filter']);
    $resultFilters = $filters;
    if ($activeDashboardSection === 'today') $resultFilters['filter'] = 'today';
    if ($activeDashboardSection === 'unread') $resultFilters['unread'] = 1;
    $topRatingBucket = $totalFeedback ? $ratingBuckets->sortDesc()->keys()->first() : 0;
    $feedbackFilterLabels = [
        'all' => 'All feedback',
        'resolved' => 'Resolved feedback',
        'needs' => 'Needs action',
        'today' => 'Submitted today',
    ];
@endphp

@section('admin_content')
    <section class="dashboard-panel {{ $activeDashboardSection ? 'dashboard-panel-detail' : 'dashboard-panel-overview' }}" data-live-url="{{ route('dashboard.stats', $filters) }}" data-live-revision="{{ $liveRevision }}">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>CFS Feedback Dashboard</h1>
                <p>Monitor customer feedback, ratings and notifications in real time.</p>
            </div>
            <div class="dashboard-tools">
                <details class="dashboard-export-menu" data-export-menu>
                    <summary class="button dashboard-export compact-button"><x-icon name="download" />Export Results<x-icon name="chevron-down" /></summary>
                    <div class="dashboard-export-options">
                        <strong>Filtered results</strong>
                        <a aria-label="Export Filtered Results" href="{{ route('dashboard.export', $resultFilters + ['format' => 'csv']) }}">Excel (CSV)</a>
                        <a href="{{ route('dashboard.export', $resultFilters + ['format' => 'pdf']) }}">Filtered Results (PDF)</a>
                        <a href="{{ route('dashboard.export', $resultFilters + ['format' => 'jpg']) }}">Filtered Results (JPG)</a>
                        <strong>All records</strong>
                        <a aria-label="Export All Records" href="{{ route('dashboard.export', ['all' => 1, 'format' => 'csv']) }}">Excel (CSV)</a>
                        <a href="{{ route('dashboard.export', ['all' => 1, 'format' => 'pdf']) }}">All Records (PDF)</a>
                        <a href="{{ route('dashboard.export', ['all' => 1, 'format' => 'jpg']) }}">All Records (JPG)</a>
                    </div>
                </details>
            </div>
        </div>

        @include('partials.feedback-filters', ['mobileExportLinks' => [
            ['url' => route('dashboard.export', $resultFilters + ['format' => 'csv']), 'label' => 'Filtered · Excel (CSV)'],
            ['url' => route('dashboard.export', $resultFilters + ['format' => 'pdf']), 'label' => 'Filtered · PDF'],
            ['url' => route('dashboard.export', $resultFilters + ['format' => 'jpg']), 'label' => 'Filtered · Image (JPG)'],
            ['url' => route('dashboard.export', ['all' => 1, 'format' => 'csv']), 'label' => 'All records · Excel (CSV)'],
            ['url' => route('dashboard.export', ['all' => 1, 'format' => 'pdf']), 'label' => 'All records · PDF'],
            ['url' => route('dashboard.export', ['all' => 1, 'format' => 'jpg']), 'label' => 'All records · Image (JPG)'],
        ]])
        <div id="feedback-notice" class="alert success" style="display: none;"></div>

        <div class="dashboard-card-grid">
            <a class="dashboard-card dashboard-card-link {{ $activeDashboardSection === 'total' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'total'] + $scopeFilters) }}" @if ($activeDashboardSection === 'total') aria-current="page" @endif>
                <svg class="dashboard-icon stat-feedback" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                <div>
                    <span>Total Feedback</span>
                    <strong id="total-feedback">{{ $totalFeedback }}</strong>
                    <div class="dashboard-change">Live total from all feedback</div>
                </div>
            </a>
            <a class="dashboard-card dashboard-card-link {{ $activeDashboardSection === 'average' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'average'] + $scopeFilters) }}" @if ($activeDashboardSection === 'average') aria-current="page" @endif>
                <svg class="dashboard-icon stat-rating" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"></path></svg>
                <div>
                    <span>Average Rating</span>
                    <strong><span id="average-rating">{{ number_format($averageRating, 1) }}</span><span class="metric-denominator"> / 10</span></strong>
                    <div class="dashboard-change">Based on rating answers</div>
                </div>
            </a>
            <a class="dashboard-card dashboard-card-link {{ $activeDashboardSection === 'today' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'today'] + $scopeFilters) }}" @if ($activeDashboardSection === 'today') aria-current="page" @endif>
                <svg class="dashboard-icon stat-today" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v16H6z"></path><path d="M9 8h6M9 12h6M9 16h3"></path></svg>
                <div>
                    <span>Today's Feedback</span>
                    <strong id="today-feedback">{{ $todayFeedback }}</strong>
                    <div class="dashboard-change">Submitted today</div>
                </div>
            </a>
            <a class="dashboard-card dashboard-card-link {{ $activeDashboardSection === 'unread' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'unread'] + $scopeFilters) }}" @if ($activeDashboardSection === 'unread') aria-current="page" @endif>
                <svg class="dashboard-icon stat-unread" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
                <div>
                    <span>Unread Notifications</span>
                    <strong id="unread-notifications">{{ $unreadNotifications }}</strong>
                    <div class="dashboard-change warn">Needs admin review</div>
                </div>
            </a>
        </div>

        @if ($activeDashboardSection)
            <section class="metric-detail">
                @if ($activeDashboardSection === 'total')
                    <div class="metric-detail-head">
                        <div>
                            <h2>Feedback Overview</h2>
                            <p>Key feedback metrics and insights from customer submissions.</p>
                        </div>
                        <a class="panel-link" href="{{ route('feedback.index', $resultFilters) }}">Open Full List</a>
                    </div>

                    <div class="feedback-kpi-grid dashboard-feedback-kpis">
                        <a class="feedback-kpi feedback-kpi-link {{ $activeFeedbackFilter === 'all' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'total','filter'=>'all'] + $scopeFilters) }}">
                            <svg class="feedback-kpi-icon feedback-blue" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                            <span>Total Records</span>
                            <strong>{{ $totalFeedback }}</strong>
                            <small>Current records</small>
                            <x-feedback-spark :points="$feedbackSparks['all']" />
                        </a>
                        <a class="feedback-kpi feedback-kpi-link {{ $activeFeedbackFilter === 'resolved' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'total','filter'=>'resolved'] + $scopeFilters) }}">
                            <svg class="feedback-kpi-icon feedback-green" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"></path><circle cx="12" cy="12" r="9"></circle></svg>
                            <span>Resolved</span>
                            <strong>{{ $resolvedFeedback }}</strong>
                            <small>{{ $totalFeedback ? round(($resolvedFeedback / $totalFeedback) * 100) : 0 }}% of total</small>
                            <x-feedback-spark :points="$feedbackSparks['resolved']" color="green" />
                        </a>
                        <a class="feedback-kpi feedback-kpi-link {{ $activeFeedbackFilter === 'needs' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'total','filter'=>'needs'] + $scopeFilters) }}">
                            <svg class="feedback-kpi-icon feedback-orange" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v6"></path><path d="M12 17h.01"></path></svg>
                            <span>Needs Action</span>
                            <strong>{{ $needsActionFeedback }}</strong>
                            <small>{{ $totalFeedback ? round(($needsActionFeedback / $totalFeedback) * 100, 1) : 0 }}% of total</small>
                            <x-feedback-spark :points="$feedbackSparks['needs']" color="orange" />
                        </a>
                        <a class="feedback-kpi feedback-kpi-link {{ $activeFeedbackFilter === 'today' ? 'active' : '' }}" href="{{ route('dashboard.section', ['section'=>'total','filter'=>'today'] + $scopeFilters) }}">
                            <svg class="feedback-kpi-icon feedback-green" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v16H6z"></path><path d="M9 8h6M9 12h6M9 16h3"></path></svg>
                            <span>Submitted Today</span>
                            <strong>{{ $todayFeedback }}</strong>
                            <small>Live today count</small>
                            <x-feedback-spark :points="$feedbackSparks['today']" color="green" />
                        </a>
                    </div>

                    <div class="dashboard-feedback-charts">
                        <article class="feedback-chart-card feedback-chart-wide">
                            <div class="feedback-card-title">
                                <h2 title="Average 1-10 score divided by 2 and rounded to the nearest star">Rating Distribution</h2>
                                <span>{{ $feedbackFilterLabels[$activeFeedbackFilter] }} · {{ $feedbackDetailTotal }} records</span>
                            </div>
                            <div class="feedback-rating-bars">
                                @foreach ([5, 4, 3, 2, 1] as $stars)
                                    @php
                                        $count = $feedbackDetailRatingBuckets[$stars] ?? 0;
                                        $percent = $feedbackDetailTotal ? round(($count / $feedbackDetailTotal) * 100, 1) : 0;
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
                                <h2>Customer Satisfaction</h2>
                                <span>Average score</span>
                            </div>
                            <div class="feedback-gauge" style="--score-deg: {{ round(min(100, max(0, ($feedbackDetailAverageRating / 10) * 100)) * 1.8, 1) }}deg;">
                                <div class="feedback-gauge-arc"></div>
                                <strong>{{ number_format($feedbackDetailAverageRating, 1) }}</strong>
                                <span>out of 10</span>
                                <small>{{ $feedbackFilterLabels[$activeFeedbackFilter] }}</small>
                            </div>
                        </article>
                    </div>

                    <div class="metric-card-section">
                        <div class="feedback-table-head dashboard-feedback-table-head">
                            <div>
                                <h2>Recent Feedback</h2>
                                <p>{{ $feedbackFilterLabels[$activeFeedbackFilter] }} shown below.</p>
                            </div>
                            <a class="panel-link" href="{{ route('feedback.index', $resultFilters) }}">Open Full List</a>
                        </div>
                        <div class="table-wrap">
                            <table class="recent-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Status</th>
                                        <th>Date & Time</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($feedbackDetailItems->take(10) as $item)
                                        <tr>
                                            <td>{{ $item->displayCode() }}</td>
                                            <td>
                                                <span class="stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        {!! $i <= round(($item->ratingAverage() ?: 0) / 2) ? '&#9733;' : '&#9734;' !!}
                                                    @endfor
                                                </span>
                                            </td>
                                            <td>"{{ $item->firstComment() ?: 'No comment provided.' }}"</td>
                                            <td><span class="metric-status status-{{ \Illuminate\Support\Str::slug($item->status) }}">{{ $item->status }}</span></td>
                                            <td>{{ optional($item->submitted_at)->format('M d, Y h:i A') }}</td>
                                            <td><a class="row-action" href="{{ route('feedback.detail', $item) }}">›</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="muted">No feedback for this filter yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif ($activeDashboardSection === 'average')
                    <div class="metric-detail-head">
                        <div>
                            <h2>Average Rating</h2>
                            <p>Rating summary, distribution, and recent rating trend.</p>
                        </div>
                        <a class="panel-link" href="{{ route('feedback.index', $resultFilters) }}">Open Feedback Ratings</a>
                    </div>
                    <div class="metric-summary-strip">
                        <div class="metric-chip"><span>Average Rating</span><strong>{{ number_format($averageRating, 1) }} <span class="stars">&#9733;</span></strong></div>
                        <div class="metric-chip"><span>Rating Feedback</span><strong>{{ $totalFeedback }}</strong></div>
                        <div class="metric-chip"><span>Top Star Bucket</span><strong>{{ $topRatingBucket ?: 0 }} <span class="stars">&#9733;</span></strong></div>
                        <div class="metric-chip"><span>Resolution Rate</span><strong>{{ $responseRate }}%</strong></div>
                    </div>
                    <div class="metric-card-section metric-two-col">
                        <div>
                            <div class="rating-bars">
                                @foreach ([5, 4, 3, 2, 1] as $stars)
                                    @php
                                        $count = $ratingBuckets[$stars] ?? 0;
                                        $percent = $totalFeedback ? round(($count / $totalFeedback) * 100, 1) : 0;
                                    @endphp
                                    <div class="rating-row-line">
                                        <span>{{ $stars }} <span class="stars">&#9733;</span></span>
                                        <span class="rating-track"><span class="rating-fill" style="width: {{ $percent }}%;"></span></span>
                                        <span>{{ $count }} ({{ $percent }}%)</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <span class="rating-divider" aria-hidden="true"></span>
                        <div>
                            <div class="trend-head">
                                <strong>Rating Trend</strong>
                                <span class="muted">{{ ucfirst($filters['period'] ?? 'daily') }} / 6 periods</span>
                            </div>
                            <svg class="mini-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Rating trend chart">
                                @for ($line = 0; $line <= 5; $line++)
                                    @php
                                        $y = $plotTop + ($line * ($plotHeight / 5));
                                        $label = 10 - ($line * 2);
                                    @endphp
                                    <line x1="{{ $plotLeft }}" y1="{{ $y }}" x2="{{ $plotLeft + $plotWidth }}" y2="{{ $y }}" stroke="#e5ebf3" stroke-width="1"></line>
                                    <text x="4" y="{{ $y + 4 }}">{{ $label }}</text>
                                @endfor
                                @foreach ($chartItems as $index => $point)
                                    @php $x = $chartDots[$index]['x']; @endphp
                                    <line x1="{{ $x }}" y1="{{ $plotTop }}" x2="{{ $x }}" y2="{{ $plotTop + $plotHeight }}" stroke="#eef2f7" stroke-width="1"></line>
                                    <text x="{{ $x - 16 }}" y="{{ $plotTop + $plotHeight + 24 }}">{{ $point['label'] }}</text>
                                @endforeach
                                <polyline points="{{ trim($chartPoints) }}" fill="none" stroke="#045eb7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @foreach ($chartDots as $dot)
                                    <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="4" fill="#07183a"></circle>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                @elseif ($activeDashboardSection === 'today')
                    <div class="metric-detail-head">
                        <div>
                            <h2>Today's Feedback</h2>
                            <p>Feedback submitted today, {{ now()->format('M d, Y') }}.</p>
                        </div>
                        <a class="panel-link" href="{{ route('feedback.index', $resultFilters) }}">Open Feedback List</a>
                    </div>
                    <div class="metric-summary-strip">
                        <div class="metric-chip"><span>Submitted Today</span><strong>{{ $todayFeedback }}</strong></div>
                        <div class="metric-chip"><span>Total Feedback</span><strong>{{ $totalFeedback }}</strong></div>
                        <div class="metric-chip"><span>Average Rating</span><strong>{{ number_format($averageRating, 1) }}</strong></div>
                        <div class="metric-chip"><span>Unread</span><strong>{{ $unreadNotifications }}</strong></div>
                    </div>
                    <div class="metric-card-section">
                        <div class="table-wrap">
                            <table class="recent-table">
                                <thead>
                                    <tr>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($todayFeedbackItems as $item)
                                        <tr>
                                            <td>
                                                <span class="stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        {!! $i <= round(($item->ratingAverage() ?: 0) / 2) ? '&#9733;' : '&#9734;' !!}
                                                    @endfor
                                                </span>
                                            </td>
                                            <td>"{{ $item->firstComment() ?: 'No comment provided.' }}"</td>
                                            <td><span class="metric-status">{{ $item->status }}</span></td>
                                            <td>{{ optional($item->submitted_at)->format('h:i A') }}</td>
                                            <td><a class="row-action" href="{{ route('feedback.detail', $item) }}">›</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="muted">No feedback submitted today.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif (in_array($activeDashboardSection, ['unread', 'inbox'], true))
                    <div class="metric-detail-head">
                        <div>
                            <h2>{{ $activeDashboardSection === 'unread' ? 'Unread Notifications' : 'All Notifications' }}</h2>
                            <p>{{ $activeDashboardSection === 'unread' ? 'Notifications that still need admin review.' : 'Feedback and contact-message updates, newest first.' }}</p>
                        </div>
                        @if ($activeDashboardSection === 'unread')
                            <a class="panel-link" href="{{ route('feedback.index', $resultFilters) }}">Open Unread List</a>
                        @else
                            <a class="panel-link" href="{{ route('dashboard.section', ['section'=>'unread'] + $scopeFilters) }}">View Unread</a>
                        @endif
                    </div>
                    <div class="metric-summary-strip">
                        <div class="metric-chip"><span>Unread Count</span><strong>{{ $unreadNotifications }}</strong></div>
                        <div class="metric-chip"><span>Needs Action</span><strong>{{ $needsActionFeedback }}</strong></div>
                        <div class="metric-chip"><span>Total Feedback</span><strong>{{ $totalFeedback }}</strong></div>
                        <div class="metric-chip"><span>Average Rating</span><strong>{{ number_format($averageRating, 1) }}</strong></div>
                    </div>
                    <div class="metric-card-section">
                        <div class="metric-list">
                            @forelse ($activeDashboardSection === 'unread' ? $unreadNotificationItems : $notificationItems as $notification)
                                <a class="metric-list-row notification-inbox-row {{ $notification->is_read ? '' : 'unread' }}" id="notification-{{ $notification->id }}" href="{{ $notification->url() }}">
                                    <span>
                                        <strong>{{ $notification->title }}</strong>
                                        <p>{{ $notification->compactDetail() }}</p>
                                        <small>{{ optional($notification->created_at)->diffForHumans() }}</small>
                                    </span>
                                </a>
                            @empty
                                <p class="metric-empty">No {{ $activeDashboardSection === 'unread' ? 'unread ' : '' }}notifications right now.</p>
                            @endforelse
                        </div>
                        @if ($notificationItems && $notificationItems->hasPages())
                            <nav class="notification-inbox-pages" aria-label="Notification pages">
                                @if ($notificationItems->previousPageUrl())<a class="panel-link" href="{{ $notificationItems->previousPageUrl() }}">Previous</a>@endif
                                <span>Page {{ $notificationItems->currentPage() }} of {{ $notificationItems->lastPage() }}</span>
                                @if ($notificationItems->nextPageUrl())<a class="panel-link" href="{{ $notificationItems->nextPageUrl() }}">Next</a>@endif
                            </nav>
                        @endif
                    </div>
                @endif
            </section>
        @else
            @include('dashboard.overview')
        @endif
    </section>

@endsection
