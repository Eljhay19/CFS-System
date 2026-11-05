<section class="feedback-history" aria-labelledby="action-history-heading">
    <h2 id="action-history-heading"><x-icon name="history" />Action History</h2>
    <ol class="action-history">
        @forelse ($feedback->actions->sortByDesc('id') as $action)
            <li>
                @php
                    $hasNewerSuccessfulCustomerEmail = $feedback->actions->contains(fn ($laterAction) =>
                        $laterAction->id > $action->id && $laterAction->customer_email_status === 'sent'
                    );
                    $hasNewerDuplicateNote = filled($action->note) && $feedback->actions->contains(fn ($laterAction) =>
                        $laterAction->id > $action->id && $laterAction->note === $action->note
                    );
                    $historyLabel = $action->previous_status === $action->status
                        ? 'Admin note recorded — ' . $action->status
                        : match ($action->status) {
                            'Needs Action' => 'Flagged for review — Needs Action',
                            'In Progress' => 'Review started — In Progress',
                            'Resolved' => 'Concern resolved — Resolved',
                            default => 'Status changed to ' . $action->status,
                        };
                @endphp
                <div><strong>{{ $historyLabel }}</strong><time datetime="{{ $action->created_at->toIso8601String() }}">{{ $action->created_at->format('M d, Y h:i A') }}</time></div>
                @if ($action->note && ! $hasNewerDuplicateNote)<p>{{ $action->note }}</p>@endif
                @if ($action->customer_message)
                    @php
                        $customerMessageLabel = match ($action->customer_email_status) {
                            'sent' => 'Message sent to customer:',
                            'failed' => 'Message not delivered:',
                            'queued', 'sending' => 'Message queued for customer:',
                            'not_requested' => 'Customer message (not emailed):',
                            default => 'Customer message:',
                        };
                    @endphp
                    <p class="action-customer-message"><strong>{{ $customerMessageLabel }}</strong> {{ $action->customer_message }}</p>
                @endif
                <small>{{ optional($action->user)->name ?: 'Admin' }}</small>
                @if ($action->customer_email_status)
                    <small class="customer-email-status customer-email-status-{{ $action->customer_email_status }}">Customer email: {{ $action->customer_email_status === 'not_requested' ? 'not requested' : $action->customer_email_status }}</small>
                    @if ($action->customer_email_error)<small>{{ $action->customer_email_error }}</small>@endif
                    @if ($action->customer_email_status === 'failed' && $feedback->hasFollowUp() && ! $hasNewerSuccessfulCustomerEmail)
                        <form method="POST" action="{{ route('feedback.retry-customer-email', [$feedback, $action]) }}" data-detail-form>
                            @csrf
                            <button class="button secondary compact-button feedback-customer-retry" type="submit"><x-icon name="mail" />Retry Customer Email</button>
                        </form>
                    @endif
                @endif
                @if ($action->admin_email_status)
                    <small class="customer-email-status customer-email-status-{{ $action->admin_email_status }}">Admin email: {{ $action->admin_email_status }}</small>
                    @if ($action->admin_email_error)<small>{{ $action->admin_email_error }}</small>@endif
                @endif
            </li>
        @empty
            <li class="feedback-history-empty">No admin actions recorded yet.</li>
        @endforelse
    </ol>
</section>
