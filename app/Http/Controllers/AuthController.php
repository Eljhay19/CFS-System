<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        if (Auth::attempt([$field => $data['login'], 'password' => $data['password']])) {
            $request->session()->regenerate();
            $this->persistLegacyBusinessLogo($request->user());

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['login' => 'Invalid username/email or password.'])
            ->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function persistLegacyBusinessLogo($user): void
    {
        $business = $user->business;
        if (! $business || $business->logo_data || ! $business->logo) return;

        $relativePath = $business->logo;
        if (! preg_match('#\Auploads/logos/[A-Za-z0-9._-]+\.(?:jpg|jpeg|png)\z#iD', $relativePath)) return;

        $path = public_path($relativePath);
        if (! is_file($path) || ! is_readable($path)) return;

        try {
            $mime = mime_content_type($path);
            if (! in_array($mime, ['image/jpeg', 'image/png'], true)) return;

            $business->forceFill([
                'logo_data' => base64_encode(file_get_contents($path)),
                'logo_mime' => $mime,
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('Could not preserve the existing business profile image.', ['user_id' => $user->id]);
        }
    }
}
