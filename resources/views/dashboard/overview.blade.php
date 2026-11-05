<div class="dashboard-chart-grid">
    @include('dashboard.distribution')
    @include('dashboard.period-chart', ['volume' => false])
    @include('dashboard.period-chart', ['volume' => true])
</div>

<div class="dashboard-bottom-grid">
    <section class="dashboard-box">
        <div class="panel-title">
            <h2><x-icon name="message-square" />Recent Feedback</h2>
            <a class="panel-link" href="{{ route('feedback.index', $scopeFilters) }}">View All</a>
        </div>
        <div class="table-wrap dashboard-recent-feedback-wrap" role="region" aria-label="Recent feedback list" tabindex="0">
            <table class="recent-table">
                <thead><tr><th>ID</th><th>Rating</th><th>Comment</th><th>Date &amp; Time</th><th aria-label="View feedback"></th></tr></thead>
                <tbody id="recent-feedback">
                    @forelse ($recentFeedback as $item)
                        <tr data-feedback-id="{{ $item->id }}">
                            <td>{{ $item->displayCode() }}</td>
                            <td class="dashboard-rating-value">{{ $item->ratingAverage() === null ? 'N/A' : $item->ratingAverage() . '/10' }}</td>
                            <td class="dashboard-comment">"{{ $item->firstComment() ?: 'No comment provided.' }}"</td>
                            <td>{{ optional($item->submitted_at)->format('M d, Y h:i A') }}</td>
                            <td><a class="row-action" href="{{ route('feedback.detail', $item) }}" aria-label="View {{ $item->displayCode() }}"><x-icon name="chevron-right" /></a></td>
                        </tr>
                    @empty
                        <tr data-empty-feedback="true"><td colspan="5" class="muted">No feedback for these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="dashboard-box dashboard-notifications">
        <div class="panel-title">
            <h2><x-icon name="bell" />Notifications</h2>
            <a class="panel-link" href="{{ route('dashboard.section', ['section' => 'inbox'] + $scopeFilters) }}">View All</a>
        </div>
        <div class="notification-feed" id="notification-list">
            @forelse ($notifications as $notification)
                <a class="notification-row {{ $notification->is_read ? '' : 'unread' }}" href="{{ $notification->url() }}">
                    <span class="notification-dot" aria-hidden="true"></span>
                    <span class="notification-copy">
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->feedback ? $notification->feedback->status : \Illuminate\Support\Str::limit($notification->message, 70) }}</p>
                    </span>
                    @if ($notification->feedback)<span class="notification-code">{{ $notification->feedback->displayCode() }}</span>@endif
                    <time class="notification-time" datetime="{{ optional($notification->created_at)->toIso8601String() }}" title="{{ optional($notification->created_at)->format('M d, Y h:i A') }}">{{ optional($notification->created_at)->diffForHumans() }}</time>
                </a>
            @empty
                <p class="muted">No notifications for these filters.</p>
            @endforelse
        </div>
    </section>
</div>
