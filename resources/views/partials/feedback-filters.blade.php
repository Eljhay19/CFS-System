@php $dateRange = $filters['range'] ?? (!empty($filters['from']) || !empty($filters['to']) ? 'custom' : 'all'); @endphp
<form class="workflow-filter" method="GET" action="{{ url()->current() }}" id="feedback-filters" data-date-ranges='@json(app(\App\Services\FeedbackFilters::class)->dateRanges())'>
    @foreach (['filter','search','unread'] as $key)@if (!empty($filters[$key]))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
    @if (!empty($mobileExportLinks))
        <div class="filter-mobile-toolbar">
            <details class="filter-mobile-export">
                <summary class="button compact-button">Export <x-icon name="chevron-down" /></summary>
                <div class="filter-mobile-export-options">
                    @foreach ($mobileExportLinks as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </details>
            <div class="filter-actions">
                <button class="button filter-apply compact-button" type="submit">Apply</button>
                <a class="filter-clear compact-button" href="{{ url()->current() }}">Clear</a>
            </div>
        </div>
    @endif
    <label>Date range<span class="filter-preset-shell"><x-icon name="calendar-days" /><select name="range" data-date-preset>@foreach (['all'=>'All dates','today'=>'Today','last7'=>'Last 7 days','last30'=>'Last 30 days','month'=>'This month','custom'=>'Custom range'] as $value=>$label)<option value="{{ $value }}" {{ $dateRange === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></span></label>
    <label>From<input id="feedback-filter-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" data-date-from></label>
    <label>To<input id="feedback-filter-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" min="{{ $filters['from'] ?? '' }}" data-date-to></label>
    <label>Status<select name="status"><option value="">All statuses</option>@foreach (['Submitted','Needs Action','In Progress','Resolved'] as $option)<option {{ ($filters['status'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></label>
    <label>Period<select name="period">@foreach (['daily','weekly','monthly'] as $option)<option value="{{ $option }}" {{ ($filters['period'] ?? 'daily') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>@endforeach</select></label>
    <div class="filter-actions filter-actions-desktop">
        <button class="button filter-apply compact-button" type="submit">Apply</button>
        <a class="filter-clear compact-button" href="{{ url()->current() }}">Clear</a>
    </div>
</form>
