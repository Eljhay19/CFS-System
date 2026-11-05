<section class="settings-section" aria-labelledby="account-heading">
    <h2 id="account-heading"><x-icon name="user-round" />Account &amp; Security</h2>
    <form action="{{ route('settings.account') }}" method="POST" aria-label="Account settings" data-editable-form>
        @csrf
        <div class="settings-fields">
            <x-settings-field name="name" label="Admin Name" :value="auth()->user()->name" autocomplete="name" required />
            <x-settings-field name="email" label="Account Email (Password Recovery)" type="email" :value="auth()->user()->email" autocomplete="username" required />
            <details class="settings-password-options">
                <summary>Change Password</summary>
                <x-settings-field class="current-password-field" name="current_password" label="Current Password (required to change password)" type="password" autocomplete="current-password" />
                <x-settings-field name="password" label="New Password" type="password" autocomplete="new-password" />
                <x-settings-field name="password_confirmation" label="Confirm New Password" type="password" autocomplete="new-password" />
            </details>
        </div>
        <div class="settings-actions" data-edit-actions><button class="button compact-button settings-secondary" type="button" data-edit-start>Edit</button></div>
        <div class="settings-actions" data-save-actions hidden><button class="button compact-button settings-secondary" type="button" data-edit-cancel>Cancel</button><button class="button compact-button" type="submit">Save Account</button></div>
    </form>
    @if (auth()->user()->pending_email)
        <div class="settings-field">
            <p class="muted">A code was sent to {{ auth()->user()->pending_email }}. Your current account email stays active until you verify the new address.</p>
            <form action="{{ route('settings.account-email.verify') }}" method="POST" aria-label="Verify new account email">
                @csrf
                <div class="settings-input"><x-icon name="lock" /><input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" aria-label="Account email verification code" required></div>
                @error('account_email_code')<small class="business-error">{{ $message }}</small>@enderror
                <div class="settings-actions"><button class="button compact-button" type="submit">Verify Account Email</button></div>
            </form>
        </div>
    @endif
</section>
