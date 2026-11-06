@extends('layouts.admin')

@section('title', 'Business Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/business-profile.css') }}">
@endpush

@php
    $currentType = old('business_type', optional($business)->business_type);
    $typeOptions = ['Food & Beverage', 'Restaurant', 'Cafe', 'Retail', 'Services', 'Other'];
@endphp

@section('admin_content')
    <section class="business-profile-panel">
        <div class="business-profile-head">
            <h1>Business Information</h1>
            <p>Update your business details. This information will be used in your feedback form and system records.</p>
        </div>

        <form class="business-profile-form" action="{{ route('business.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <fieldset class="business-profile-fields" {{ $editing ? '' : 'disabled' }} aria-label="Business information">
            <div class="business-profile-grid">
                <div class="business-logo-field">
                    <label>Business Profile</label>
                    <div class="business-logo-upload" data-logo-drop>
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png" data-logo-input>
                        <span class="business-logo-preview" data-logo-preview>
                            @if (optional($business)->logo_data || optional($business)->logo)
                                <img src="{{ optional($business)->logo_data ? route('business.logo', ['v' => optional($business)->updated_at?->timestamp]) : asset($business->logo) }}" alt="Business profile image">
                            @else
                                <span class="business-logo-empty">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V7"></path><path d="m7 12 5-5 5 5"></path><path d="M20 16.5a4.5 4.5 0 0 0-4.3-4.49A6 6 0 1 0 6.2 17H8"></path></svg>
                                    <strong>Drop profile image here</strong>
                                    <em>JPG or PNG · Up to 2 MB</em>
                                </span>
                            @endif
                        </span>
                    </div>
                    <button class="button business-save-button compact-button business-logo-choose" type="button" data-logo-choose>
                        Choose image
                    </button>
                    <p class="business-logo-note">Supports JPG, PNG (Max 2MB)</p>
                    @error('logo')
                        <small class="business-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="business-fields-grid">
                    <div class="business-field">
                        <label for="business_name">Business Name</label>
                        <div class="business-input-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16"></path><path d="M5 10l1-5h12l1 5"></path><path d="M6 10v9h12v-9"></path><path d="M9 19v-5h6v5"></path></svg>
                            <input id="business_name" name="business_name" value="{{ old('business_name', optional($business)->business_name) }}" required>
                        </div>
                        @error('business_name')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="business-field">
                        <label for="owner_name">Owner Name</label>
                        <div class="business-input-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.6-4 4.3-6 8-6s6.4 2 8 6"></path></svg>
                            <input id="owner_name" name="owner_name" value="{{ old('owner_name', optional($business)->owner_name) }}" required>
                        </div>
                        @error('owner_name')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="business-field">
                        <label for="business_type">Business Type</label>
                        <div class="business-input-shell business-select-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6" height="6"></rect><rect x="14" y="4" width="6" height="6"></rect><rect x="4" y="14" width="6" height="6"></rect><rect x="14" y="14" width="6" height="6"></rect></svg>
                            <select id="business_type" name="business_type" required>
                                @foreach ($typeOptions as $option)
                                    <option value="{{ $option }}" {{ $currentType === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                                @if ($currentType && ! in_array($currentType, $typeOptions, true))
                                    <option value="{{ $currentType }}" selected>{{ $currentType }}</option>
                                @endif
                            </select>
                            <svg class="business-caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                        </div>
                        @error('business_type')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="business-field">
                        <label for="contact_number">Contact Number</label>
                        <div class="business-input-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.1 5.18 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L9 10.7a16 16 0 0 0 4.3 4.3l1.25-1.27a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92Z"></path></svg>
                            <input id="contact_number" name="contact_number" value="{{ old('contact_number', optional($business)->contact_number) }}" required>
                        </div>
                        @error('contact_number')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="business-field">
                        <label for="email">Email Address</label>
                        <div class="business-input-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"></path><path d="m4 7 8 6 8-6"></path></svg>
                            <input id="email" name="email" type="email" value="{{ old('email', optional($business)->email) }}" required>
                        </div>
                        @error('email')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="business-field">
                        <label for="address">Address</label>
                        <div class="business-input-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                            <input id="address" name="address" value="{{ old('address', optional($business)->address) }}" required>
                        </div>
                        @error('address')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="business-field business-description-field">
                        <label for="description">Short Description</label>
                        <div class="business-input-shell business-textarea-shell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h9l3 3v13H6z"></path><path d="M14 4v4h4"></path><path d="M9 13h6M9 17h6"></path></svg>
                            <textarea id="description" name="description">{{ old('description', optional($business)->description) }}</textarea>
                        </div>
                        @error('description')
                            <small class="business-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
            </fieldset>
            @if ($editing)
                <div class="business-profile-actions">
                    <a class="button secondary compact-button business-cancel-button" href="{{ route('business.edit', ['edit' => 0]) }}">Cancel</a>
                    <button class="button business-save-button compact-button" type="submit">Save Information</button>
                </div>
            @else
                <div class="business-profile-actions">
                    <a class="button secondary compact-button business-cancel-button" href="{{ route('dashboard') }}">Cancel</a>
                    <a class="button business-save-button compact-button" href="{{ route('business.edit', ['edit'=>1]) }}">Edit Information</a>
                </div>
            @endif
        </form>
    </section>

    <script src="{{ asset('js/business-profile.js') }}" defer></script>
@endsection
