@extends('layouts.admin')
@section('title', 'Contact Messages')
@push('styles')<link rel="stylesheet" href="{{ asset('css/messages.css') }}">@endpush
@section('admin_content')
<section class="messages-panel">
    <header><h1>Contact Messages</h1><a class="panel-link" href="{{ route('settings.index') }}">Settings</a></header>
    @forelse ($messages as $message)
        <a class="message-list-row" href="{{ route('messages.show', $message) }}">
            <div><strong>{{ $message->name ?: 'Survey visitor' }}</strong><span>{{ $message->email }}</span></div>
            <p>{{ \Illuminate\Support\Str::limit($message->message, 160) }}</p><time>{{ $message->created_at->format('M d, Y h:i A') }}</time>
        </a>
    @empty
        <p class="muted">No contact messages yet.</p>
    @endforelse
    @if ($messages->hasPages())<nav class="message-pagination" aria-label="Message pages">@if ($messages->previousPageUrl())<a class="panel-link" href="{{ $messages->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $messages->currentPage() }} of {{ $messages->lastPage() }}</span>@if ($messages->nextPageUrl())<a class="panel-link" href="{{ $messages->nextPageUrl() }}">Next</a>@endif</nav>@endif
</section>
@endsection
