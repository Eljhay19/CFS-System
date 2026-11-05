@php
    $needsAction = $feedback->hasCustomerConcern() && $feedback->status !== 'Resolved';
    $selectedStatus = old('status', $feedback->status);
@endphp
<section class="feedback-admin-action" aria-labelledby="admin-action-heading">
    <div class="feedback-action-heading {{ $needsAction ? 'needs-action' : '' }}">
        <x-icon :name="$needsAction ? 'triangle-alert' : 'clipboard-list'" />
        <div><h2 id="admin-action-heading">{{ $needsAction ? 'Admin Action Required' : 'Admin Action' }}</h2><p>{{ $needsAction ? 'Reported concern requires review.' : ($feedback->status === 'Resolved' ? 'This feedback has been resolved.' : 'Review and update this feedback.') }}</p>
            <p class="feedback-severity-note feedback-severity-{{ strtolower($feedback->severityLevel()) }}">{{ $feedback->severityLevel() }} concern{{ $feedback->severityLevel() === 'High' ? ' — prioritize review and document the real action taken.' : ($feedback->severityLevel() === 'Medium' ? ' — review and follow up as appropriate.' : ' — standard feedback handling.') }}</p>
            @if ($feedback->hasFollowUp())<p class="feedback-customer-update-note">When you save a status change, CFS emails a response matched to this concern level. You can add a customer-facing message; private owner notes are never emailed.</p>@endif
        </div>
    </div>
    <form action="{{ route('feedback.action', $feedback) }}" method="POST" data-admin-action-form data-detail-form>
        @csrf
        <div class="feedback-action-field feedback-status-field">
            <label for="status">Admin Status</label>
            <span class="feedback-control-shell"><x-icon name="clipboard-list" /><select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                @foreach (['Submitted', 'Needs Action', 'In Progress', 'Resolved'] as $status)
                    <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }} {{ $status === 'Submitted' && $feedback->hasCustomerConcern() ? 'disabled' : '' }}>{{ $status }}</option>
                @endforeach
            </select><x-icon name="chevron-down" class="feedback-select-arrow" /></span>
            @error('status')<small class="feedback-field-error" id="status-error">{{ $message }}</small>@enderror
        </div>
        <div class="feedback-action-field">
            <label for="action_taken">Action Taken / Owner Notes<span data-resolution-required {{ $selectedStatus === 'Resolved' ? '' : 'hidden' }}> *</span></label>
            <span class="feedback-control-shell feedback-notes-shell"><x-icon name="message-square" /><textarea id="action_taken" name="action_taken" maxlength="2000" placeholder="Describe the action taken..." {{ $selectedStatus === 'Resolved' ? 'required' : '' }} @error('action_taken') aria-invalid="true" aria-describedby="action-error" @enderror>{{ old('action_taken', $feedback->action_taken) }}</textarea></span>
            @error('action_taken')<small class="feedback-field-error" id="action-error">{{ $message }}</small>@enderror
        </div>
        @if ($feedback->hasFollowUp())
            <div class="feedback-action-field">
                <label for="customer_message">Message to Customer<span data-customer-message-required {{ $selectedStatus === 'Resolved' ? '' : 'hidden' }}> *</span><span class="feedback-optional-label" data-customer-message-optional {{ $selectedStatus === 'Resolved' ? 'hidden' : '' }}> (optional)</span></label>
                <span class="feedback-control-shell feedback-notes-shell"><x-icon name="mail" /><textarea id="customer_message" name="customer_message" maxlength="2000" placeholder="Write an update for the customer..." {{ $selectedStatus === 'Resolved' ? 'required' : '' }} @error('customer_message') aria-invalid="true" aria-describedby="customer-message-error" @enderror>{{ old('customer_message') }}</textarea></span>
                <small class="feedback-field-hint">The CFS system emails this message to the customer's consented address. Include the outcome before resolving follow-up.</small>
                @error('customer_message')<small class="feedback-field-error" id="customer-message-error">{{ $message }}</small>@enderror
            </div>
        @endif
        <div class="feedback-action-footer"><button class="button compact-button feedback-action-save" type="submit" data-primary-submit>Save Admin Action</button></div>
    </form>
</section>
