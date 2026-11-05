@extends('layouts.admin')
@section('title', 'Settings')
@push('styles')<link rel="stylesheet" href="{{ asset('css/settings.css') }}?v={{ filemtime(public_path('css/settings.css')) }}">@endpush
@section('admin_content')
<section class="settings-panel">
    <header class="settings-heading"><h1>Settings</h1><p>Manage your account, notifications, and survey.</p></header>
    <div class="settings-grid">
        @include('settings.partials.account')
        @if ($business)
            @include('settings.partials.notifications')
            @include('settings.partials.access')
            @include('settings.partials.share')
        @else
            <p><a class="panel-link" href="{{ route('business.edit') }}">Complete Business Profile</a></p>
        @endif
    </div>
</section>
@if ($business)
    <form id="settings-test-email" action="{{ route('settings.test-email') }}" method="POST">@csrf</form>
    <dialog class="settings-preview" aria-labelledby="survey-preview-heading" data-survey-preview>
        <button class="terms-close" type="button" data-preview-close aria-label="Close survey preview">&times;</button>
        <h2 id="survey-preview-heading">Survey Preview</h2><p data-preview-message></p>
        <a class="button compact-button" href="{{ route('survey.public', $business->survey_slug) }}" data-preview-open>Open Survey</a>
    </dialog>
@endif
<script src="{{ asset('js/vendor/qrcode.js') }}" defer></script>
<script src="{{ asset('js/settings.js') }}?v={{ filemtime(public_path('js/settings.js')) }}" defer></script>
@endsection
