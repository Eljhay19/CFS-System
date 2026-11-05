@extends('layouts.app')

@section('title', 'CFS - Contact Us')
@section('body_class', 'feedback-screen contact-screen')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contact.css') }}">
@endpush

@section('content')
    <section class="terms-card contact-card" aria-labelledby="contact-title">
        <a class="terms-close" href="{{ route('home') }}" aria-label="Close Contact Us">&times;</a>
        <div class="terms-heading">
            <h1 id="contact-title">Contact Us</h1>
            <p>For questions, concerns, or assistance with the survey.</p>
        </div>
        <form class="contact-form" action="{{ route('contact.store') }}" method="POST" data-contact-form>
            @csrf
            <input type="hidden" name="submission_token" value="{{ $contactToken }}">
            <div class="contact-scroll" tabindex="0" aria-label="Contact details and message form">
                <div class="contact-details">
                    <div>
                        <x-icon name="mail" />
                        <dl>
                            <dt>Email Address</dt>
                            <dd>@if ($supportEmail)<a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@else<span class="contact-unavailable">Not provided</span>@endif</dd>
                        </dl>
                    </div>
                    <div>
                        <x-icon name="phone" />
                        <dl>
                            <dt>Contact Number</dt>
                            <dd>@if ($supportPhone)<a href="tel:{{ preg_replace('/[^+0-9]/', '', $supportPhone) }}">{{ $supportPhone }}</a>@else<span class="contact-unavailable">Not provided</span>@endif</dd>
                        </dl>
                    </div>
                </div>
                <h2>Send a Message</h2>
                @if (session('contact_status'))<p class="contact-status" role="status">{{ session('contact_status') }}</p>@endif
                @error('contact')<p class="business-error" role="alert">{{ $message }}</p>@enderror
                <div class="contact-fields">
                    <div class="contact-field">
                        <label for="contact-name">Name <span>(optional)</span></label>
                        <div class="contact-input"><x-icon name="user-round" /><input id="contact-name" name="name" autocomplete="name" placeholder="Your name" maxlength="100" value="{{ old('name') }}"></div>
                        @error('name')<small class="business-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="contact-field">
                        <label for="contact-email">Email Address <span class="contact-required">*</span></label>
                        <div class="contact-input"><x-icon name="mail" /><input id="contact-email" name="email" type="email" autocomplete="email" placeholder="Your email address" maxlength="255" value="{{ old('email') }}" required></div>
                        @error('email')<small class="business-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="contact-field contact-message-field">
                        <label for="contact-message">Message <span class="contact-required">*</span></label>
                        <div class="contact-input contact-textarea"><x-icon name="message-square" /><textarea id="contact-message" name="message" placeholder="Write your message here..." maxlength="5000" required>{{ old('message') }}</textarea></div>
                        @error('message')<small class="business-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
            <div class="contact-actions">
                <a class="survey-back-link" href="{{ route('home') }}"><x-icon name="chevron-left" />Back to Home</a>
                <button class="button contact-send compact-button" type="submit"><x-icon name="send" /><span data-contact-submit-label>Send Message</span></button>
            </div>
        </form>
    </section>
    <script src="{{ asset('js/contact.js') }}" defer></script>
@endsection
