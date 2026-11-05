<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UiFlashMessages
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->routeIs('dashboard.stats', 'dashboard.notifications', 'dashboard.notifications.read-all')) {
            // Background requests must not consume messages intended for the next page.
            $request->session()->reflash();
        } elseif ($response->getStatusCode() === 200 && strpos((string) $response->headers->get('Content-Type'), 'text/html') !== false) {
            // HTML has rendered these values; do not let later polling replay them.
            $request->session()->forget(['status', 'errors', '_old_input']);
        }
        return $response;
    }
}
