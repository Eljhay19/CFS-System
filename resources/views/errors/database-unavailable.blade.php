<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CFS - Temporarily Unavailable</title>
    <link rel="stylesheet" href="{{ asset('css/database-status.css') }}">
</head>
<body>
    <header><a href="{{ route('home') }}" aria-label="CFS Home">@include('partials.logo')<strong>CFS</strong></a></header>
    <main>
        <section role="status">
            <h1>Temporarily Unavailable</h1>
            <p>This service is temporarily unavailable. Please try again shortly.</p>
            <a class="retry-button" href="{{ $retryUrl }}">Try Again</a>
        </section>
    </main>
    <footer>Your Feedback. A Better Experience.</footer>
</body>
</html>
