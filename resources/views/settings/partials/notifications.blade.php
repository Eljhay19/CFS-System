<section class="settings-section" aria-labelledby="notification-heading">
    <div class="settings-section-heading"><h2 id="notification-heading"><x-icon name="bell" />Notifications</h2><a class="panel-link" href="{{ route('messages.index') }}">Messages</a></div>
    <form action="{{ route('settings.update') }}" method="POST" aria-label="Notification settings" data-editable-form>
        @csrf<input type="hidden" name="section" value="notifications">
        @foreach (['dashboard_notifications' => ['Dashboard notifications', 'Show new feedback and messages in the notification center.', 'monitor'], 'feedback_email_alerts' => ['Enable feedback email notifications', 'Email alerts for new customer feedback and complaints.', 'mail'], 'contact_message_alerts' => ['Contact message alerts', 'Show new Contact Us messages in the notification center. Email delivery and saved messages are unaffected.', 'message-square']] as $key => $details)
            <div class="settings-toggle-row"><x-icon :name="$details[2]" /><label for="{{ $key }}"><strong>{{ $details[0] }}</strong><small>{{ $details[1] }}</small></label><input type="hidden" name="{{ $key }}" value="0"><label class="settings-switch"><input id="{{ $key }}" type="checkbox" name="{{ $key }}" value="1" role="switch" aria-label="{{ $details[0] }}" {{ old($key, $business->$key) ? 'checked' : '' }}><span aria-hidden="true"></span></label></div>
        @endforeach
        <div class="settings-field recipient-field">
            <label for="notification_email">Notification Email</label>
            <div class="settings-input"><x-icon name="mail" /><input id="notification_email" name="notification_email" type="email" value="{{ old('notification_email', $business->pending_notification_email ?: ($business->use_account_email_for_feedback ? auth()->user()->email : $business->notification_email)) }}" data-account-email="{{ auth()->user()->email }}" autocomplete="email"></div>
            <small class="muted">To use a different email, edit this field and turn off “Use recovery email for CFS notifications.” CFS sends a verification code to the new address; it becomes active only after verification. Password recovery continues to use the Account Email above.</small>
            <small class="muted">CFS feedback alerts are currently sent to {{ $business->notificationRecipient() ?: 'no active email address' }}.</small>
            @if ($business->notification_email_verified_at)
                <small class="muted">Verified email: {{ $business->notification_email }}</small>
            @endif
            @error('notification_email')<small class="business-error">{{ $message }}</small>@enderror
        </div>
        <div class="settings-toggle-row">
            <x-icon name="user-round" />
            <label for="use_account_email_for_feedback"><strong>Use recovery email for CFS notifications</strong><small>When off, CFS alerts use the separate Notification Email above. This does not turn off email alerts. Recovery codes still go to {{ auth()->user()->email }}.</small></label>
            <input type="hidden" name="use_account_email_for_feedback" value="0">
            <label class="settings-switch"><input id="use_account_email_for_feedback" type="checkbox" name="use_account_email_for_feedback" value="1" role="switch" {{ old('use_account_email_for_feedback', $business->use_account_email_for_feedback) ? 'checked' : '' }}><span aria-hidden="true"></span></label>
        </div>
        <div class="settings-actions">
            <button class="button compact-button settings-secondary" type="submit" form="settings-test-email">Send Test Email</button>
            <button class="button compact-button settings-secondary" type="button" data-edit-start data-edit-actions>Edit</button>
            <span class="settings-save-actions" data-save-actions hidden><button class="button compact-button settings-secondary" type="button" data-edit-cancel>Cancel</button><button class="button compact-button" type="submit" aria-label="Save Notification Settings">Save Settings</button></span>
        </div>
    </form>
    @if ($business->pending_notification_email)
        <div class="settings-field">
            <p class="muted">{{ $business->pending_notification_email }} is waiting for verification. CFS alerts will continue going to the active address above until you verify it.</p>
            <p class="muted">Enter the six-digit code sent to this address to activate it.</p>
            <form action="{{ route('settings.notification-email.verify') }}" method="POST" aria-label="Verify notification email">
                @csrf
                <div class="settings-input"><x-icon name="lock" /><input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" aria-label="Email verification code" required></div>
                @error('notification_code')<small class="business-error">{{ $message }}</small>@enderror
                <div class="settings-actions"><button class="button compact-button" type="submit">Verify Email</button></div>
            </form>
        </div>
    @endif
</section>
