<h1>New CFS Support Message</h1>
<p><strong>Received:</strong> {{ $contactMessage->created_at->format('M d, Y h:i A') }}</p>
<p><strong>Name:</strong> {{ $contactMessage->name ?: 'Not provided' }}</p>
<p><strong>Reply to:</strong> {{ $contactMessage->email }}</p>
<p style="white-space: pre-wrap;">{{ $contactMessage->message }}</p>
