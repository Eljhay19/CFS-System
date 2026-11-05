@extends('layouts.app')
@section('title', ['request' => 'Forgot Password', 'sent' => 'Check Your Email', 'reset' => 'Set New Password'][$state])
@section('body_class', 'login-screen recovery-screen')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth-panels.css') }}?v={{ filemtime(public_path('css/auth-panels.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/password-recovery.css') }}?v={{ filemtime(public_path('css/password-recovery.css')) }}">
@endpush
@section('content')
    <section class="login-stage">
        @include('auth.partials.showcase')
        <section class="panel login-panel auth-card recovery-panel recovery-{{ $state }}" aria-labelledby="recovery-title" data-recovery-panel data-state="{{ $state }}">
        <div class="login-grid">
            @include('auth.partials.splash')
            <div class="login-form recovery-form">
                @if ($errors->any())
                    <div class="alert error" role="alert" aria-live="assertive" data-flash-toast>{{ $errors->first() }}</div>
                @endif
                @include('auth.recovery.' . $state)
            </div>
        </div>
        </section>
    </section>
<script src="{{ asset('js/password-recovery.js') }}?v={{ filemtime(public_path('js/password-recovery.js')) }}" defer></script>
@endsection
