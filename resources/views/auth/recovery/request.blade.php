<h1 id="recovery-title" tabindex="-1" autofocus>Forgot Password?</h1>
<p class="muted">Enter your admin email to receive a verification code.</p>
<form class="recovery-request-form" action="{{ route('password.email') }}" method="POST" data-password-form data-pending-label="Sending…">
    @csrf
    <div class="recovery-fields">
        <div class="field">
            <label for="email">Email Address</label>
            <div class="input-shell">
                <x-icon name="mail" class="input-icon" />
                <input id="email" name="email" type="email" maxlength="255" value="{{ $formEmail }}" placeholder="Enter your email" autocomplete="email" required>
            </div>
        </div>
    </div>
    <div class="login-actions">
        <a class="button secondary" href="{{ route('login') }}">Back to Login</a>
        <button class="button" type="submit">Send Code</button>
    </div>
</form>
