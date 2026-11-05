<h1 id="recovery-title" tabindex="-1">Verify Your Email</h1>
<p class="muted">Enter the 6-digit code sent to your email if an account matches.</p>
<p class="recovery-email">{{ $maskedEmail }}</p>
@if ($localMailMode)
    <p class="recovery-notice" role="status">Local mode: email delivery to your inbox is off.</p>
@elseif (session('status'))
    <p class="alert success" role="status" aria-live="polite" data-flash-toast>{{ session('status') }}</p>
@endif
<form action="{{ route('password.verify') }}" method="POST" data-password-form data-pending-label="Verifying…" class="recovery-verify-form">
    @csrf
    <div class="recovery-code-field">
        <label id="verification-code-label">Verification Code</label>
        <div class="recovery-code-entry" role="group" aria-labelledby="verification-code-label">
            @for ($digit = 1; $digit <= 6; $digit++)
                <input class="recovery-code-digit" type="text" inputmode="numeric" pattern="[0-9]" autocomplete="{{ $digit === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $digit }} of 6" aria-describedby="verification-code-help" data-otp-digit>
            @endfor
            <input id="verification-code" name="code" type="hidden">
        </div>
    </div>
    <p id="verification-code-help" class="recovery-code-help">Check your inbox or spam folder.</p>
    <div class="login-actions">
        <a class="button secondary" href="{{ route('password.request') }}">Back</a>
        <button class="button" type="submit" data-primary-submit>Verify Code</button>
    </div>
</form>
<form action="{{ route('password.resend') }}" method="POST" data-password-form data-pending-label="Sending…" data-resend-at="{{ $resendAt }}" data-server-time="{{ now()->timestamp }}" class="recovery-resend-form">
    @csrf
    <span class="muted">Didn't receive a code?</span>
    <button class="recovery-text-button" type="submit" data-resend>Resend Code</button>
    <span class="recovery-cooldown" role="status" aria-live="off"></span>
</form>
