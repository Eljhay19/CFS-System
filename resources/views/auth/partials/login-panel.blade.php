<section class="panel login-panel auth-card" data-admin-login-panel>
    <div class="login-grid">
        @include('auth.partials.splash')
        <div class="login-form">
            <h1>Welcome Back!</h1>
            <p class="muted">Please enter your credentials to continue.</p>
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="{{ $idPrefix ?? '' }}login">Email / Username</label>
                    <div class="input-shell">
                        <svg class="input-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="8" r="4"></circle>
                        </svg>
                        <input id="{{ $idPrefix ?? '' }}login" name="login" value="{{ old('login') }}" placeholder="Enter your email or username" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="field">
                    <label for="{{ $idPrefix ?? '' }}password_display">Password</label>
                    <div class="input-shell">
                        <svg class="input-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                        </svg>
                        <input id="{{ $idPrefix ?? '' }}password_display" class="password-mask-input" type="text" placeholder="Enter your password" autocomplete="off" required>
                        <input id="{{ $idPrefix ?? '' }}password" name="password" type="hidden" autocomplete="current-password">
                        <button id="{{ $idPrefix ?? '' }}password_toggle" class="icon-button" type="button" aria-label="Show password">
                            <svg class="input-icon eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="input-icon eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.5 5.5A10.4 10.4 0 0 1 12 5c5.2 0 8.5 4.3 9.5 7-.4 1.1-1.2 2.4-2.4 3.6"></path>
                                <path d="M6.3 6.9C4.4 8.2 3.2 10.3 2.5 12c1 2.7 4.3 7 9.5 7 1.4 0 2.7-.3 3.8-.9"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="login-options">
                    <a class="forgot-link" href="{{ route('password.request') }}">Forgot Password?</a>
                </div>
                <div class="login-actions">
                    <a class="button secondary" href="{{ route('home') }}">Cancel</a>
                    <button class="button" type="submit">Login</button>
                </div>
            </form>
        </div>
    </div>
</section>
