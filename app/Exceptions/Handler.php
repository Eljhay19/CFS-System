<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
        'code',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (QueryException $exception, Request $request) {
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $state = (string) ($exception->errorInfo[0] ?? '');
            if (! in_array($driverCode, [2002, 2006, 2013], true) && strpos($state, '08') !== 0) return null;

            $headers = ['Retry-After'=>'10', 'Cache-Control'=>'no-store'];
            $message = 'This service is temporarily unavailable. Please try again shortly.';
            if ($request->expectsJson()) return response()->json(['message'=>$message], 503, $headers);

            // This view deliberately avoids the database-backed application layout.
            return response()->view('errors.database-unavailable', [
                'retryUrl' => $request->isMethod('GET') ? $request->fullUrl() : route('home'),
            ], 503, $headers);
        });
    }
}
