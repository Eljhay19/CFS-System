@extends('layouts.admin')
@section('title', 'Contact Message')
@push('styles')<link rel="stylesheet" href="{{ asset('css/messages.css') }}">@endpush
@section('admin_content')
<section class="messages-panel">
    <header><h1>Contact Message</h1><a class="panel-link" href="{{ route('messages.index') }}">All Messages</a></header>
    <dl class="message-metadata"><div><dt>From</dt><dd>{{ $message->name ?: 'Survey visitor' }}</dd></div><div><dt>Email</dt><dd><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd></div><div><dt>Received</dt><dd>{{ $message->created_at->format('M d, Y h:i A') }}</dd></div><div><dt>Email Status</dt><dd>{{ ucfirst($message->email_status) }}</dd></div></dl>
    <p class="message-body">{{ $message->message }}</p>
    <section class="contact-replies" aria-labelledby="contact-replies-heading">
        <h2 id="contact-replies-heading">Reply to Customer</h2>
        <p class="muted">Your reply will be emailed to {{ $message->email }}. It will also be saved here with its delivery status.</p>
        @if (session('status'))<p class="contact-reply-status" role="status">{{ session('status') }}</p>@endif
        @error('reply')<p class="contact-reply-error" role="alert">{{ $message }}</p>@enderror
        <form action="{{ route('messages.reply', $message) }}" method="POST" class="contact-reply-form">
            @csrf
            <label for="reply-body">Message to customer</label>
            <textarea id="reply-body" name="body" rows="5" maxlength="5000" required>{{ old('body') }}</textarea>
            @error('body')<small class="contact-reply-error">{{ $message }}</small>@enderror
            <button class="button compact-button" type="submit">Send Email Reply</button>
        </form>
        <div class="contact-reply-history">
            <h3>Reply History</h3>
            @forelse ($message->replies as $reply)
                <article class="contact-reply-entry">
                    <header><strong>{{ $reply->user->name ?? 'Admin' }}</strong><span>{{ $reply->created_at->format('M d, Y h:i A') }}</span></header>
                    <p>{{ $reply->body }}</p>
                    <small>Email status: {{ ucfirst($reply->email_status) }}{{ $reply->email_sent_at ? ' · ' . $reply->email_sent_at->format('M d, Y h:i A') : '' }}</small>
                    @if ($reply->email_error)<small class="contact-reply-error">{{ $reply->email_error }}</small>@endif
                    @if (in_array($reply->email_status, ['failed', 'pending'], true))
                        <form action="{{ route('messages.reply.retry', [$message, $reply]) }}" method="POST" class="contact-reply-retry">@csrf<button class="button compact-button settings-secondary" type="submit">Retry Email</button></form>
                    @endif
                </article>
            @empty
                <p class="muted">No replies sent yet.</p>
            @endforelse
        </div>
    </section>
    <a class="survey-back-link" href="{{ route('dashboard') }}">Back to Dashboard</a>
</section>
@endsection
