@extends('layouts.admin')

@section('title', 'Feedback Details')
@section('body_class', 'admin-screen admin-feedback-detail-screen')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/feedback-details.css') }}?v={{ filemtime(public_path('css/feedback-details.css')) }}">
@endpush

@section('admin_content')
    @php
        $emailStatus = $feedback->email_status ?: 'pending';
        $emailLabels = ['sent'=>'Sent', 'logged'=>'Logged (development)', 'failed'=>'Failed', 'disabled'=>'Disabled', 'pending'=>'Pending'];
    @endphp
    <section class="feedback-detail" aria-labelledby="feedback-detail-title">
        <header class="feedback-detail-heading">
            <a class="feedback-back-link" href="{{ route('feedback.index') }}"><x-icon name="arrow-left" />Back to Feedback</a>
            <time datetime="{{ optional($feedback->submitted_at)->toIso8601String() }}">Submitted {{ optional($feedback->submitted_at)->format('M d, Y h:i A') }}</time>
            <h1 id="feedback-detail-title">Feedback Details</h1>
            <dl class="feedback-detail-meta">
                <div><dt>ID:</dt><dd>{{ $feedback->displayCode() }}</dd></div>
                <div><dt>Customer:</dt><dd>{{ $feedback->customerLabel() }}</dd></div>
                <div><dt>Concern:</dt><dd class="detail-concern-{{ strtolower($feedback->concern_level) }}">{{ $feedback->concern_level }}</dd></div>
                <div><dt>Status:</dt><dd class="detail-status-{{ \Illuminate\Support\Str::slug($feedback->status) }}">{{ $feedback->status }}</dd></div>
                <div><dt>Admin email alert:</dt><dd class="detail-email-{{ $emailStatus }}">{{ $emailLabels[$emailStatus] ?? ucfirst($emailStatus) }}</dd></div>
            </dl>
            @if ($emailStatus === 'failed')
                <form method="POST" action="{{ route('feedback.retry-email', $feedback) }}" class="feedback-email-retry" data-detail-form>
                    @csrf
                    <button class="button secondary compact-button" type="submit"><x-icon name="mail" />Retry Email</button>
                    @if ($feedback->email_error)<small>{{ $feedback->email_error }}</small>@endif
                </form>
            @endif
        </header>
        <div class="feedback-detail-grid">
            @include('feedback.partials.admin-answers')
            <div class="feedback-admin-column">
                @include('feedback.partials.admin-action')
                @include('feedback.partials.admin-history')
            </div>
        </div>
        @if ($feedback->consent_at)<p class="feedback-consent">Terms &amp; Privacy accepted {{ $feedback->consent_at->format('M d, Y h:i A') }} / Version {{ $feedback->policy_version }}</p>@endif
    </section>
    <script src="{{ asset('js/feedback-details.js') }}" defer></script>
@endsection
