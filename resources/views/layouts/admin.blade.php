@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/feedback-filters.css') }}">
@endpush

@section('content')
    <div class="admin-workspace">
        <aside class="admin-sidebar" aria-label="Admin navigation">
            <button class="admin-menu-toggle" type="button" aria-controls="admin-navigation" aria-expanded="false" data-admin-menu-toggle><x-icon name="menu" /><span>Admin Menu</span><x-icon name="chevron-down" /></button>
            <a class="admin-side-brand" href="{{ route('dashboard') }}">
                @include('partials.logo')
                <span>CFS</span>
            </a>

            <nav class="admin-side-nav" id="admin-navigation">
                <a class="{{ request()->routeIs('dashboard', 'dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <svg class="admin-side-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"></path><path d="M5 10.5V20h5v-6h4v6h5v-9.5"></path></svg>
                    Dashboard
                </a>
                <a class="{{ request()->routeIs('business.*') ? 'active' : '' }}" href="{{ route('business.edit') }}">
                    <svg class="admin-side-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.6-4 4.3-6 8-6s6.4 2 8 6"></path></svg>
                    Business Profile
                </a>
                <a class="{{ request()->routeIs('feedback.index') || request()->routeIs('feedback.detail') ? 'active' : '' }}" href="{{ route('feedback.index') }}">
                    <svg class="admin-side-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                    Feedback
                </a>
                <a class="{{ request()->routeIs('questions.*') ? 'active' : '' }}" href="{{ route('questions.index') }}">
                    <svg class="admin-side-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v16H6z"></path><path d="M9 8h6M9 12h6M9 16h3"></path></svg>
                    Manage Questions
                </a>
                <a class="{{ request()->routeIs('settings.*', 'messages.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <svg class="admin-side-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.04.04a2 2 0 1 1-2.83 2.83l-.04-.04A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.06A1.7 1.7 0 0 0 8 19.4a1.7 1.7 0 0 0-1.88.34l-.04.04a2 2 0 1 1-2.83-2.83l.04-.04A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.06A1.7 1.7 0 0 0 4.6 8a1.7 1.7 0 0 0-.34-1.88l-.04-.04a2 2 0 1 1 2.83-2.83l.04.04A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.06A1.7 1.7 0 0 0 16 4.6a1.7 1.7 0 0 0 1.88-.34l.04-.04a2 2 0 1 1 2.83 2.83l-.04.04A1.7 1.7 0 0 0 19.4 9c.19.34.5.6.86.74.24.09.5.14.74.14H21a2 2 0 1 1 0 4h-.06A1.7 1.7 0 0 0 19.4 15Z"></path></svg>
                    Settings
                </a>
                <form class="logout-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit">
                        <svg class="admin-side-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M21 3v18"></path></svg>
                        Logout
                    </button>
                </form>
            </nav>
        </aside>

        <section class="admin-main">
            @yield('admin_content')
        </section>
    </div>
    <script src="{{ asset('js/feedback-filters.js') }}" defer></script>
@endsection
