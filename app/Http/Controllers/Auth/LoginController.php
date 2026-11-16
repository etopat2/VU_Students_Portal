<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }
    
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = Str::lower($credentials['email']);

        $throttleKey = Str::transliterate(
            Str::lower($credentials['email']).'|'.$request->ip(),
        );

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $message = 'Too many sign-in attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.';
            ToastMagic::error($message);

            return back()
                ->withErrors(['email' => $message])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);
            $message = 'The email or password is incorrect.';
            ToastMagic::error($message);

            return back()
                ->withErrors(['email' => $message])
                ->onlyInput('email');
        }

        if (! $request->user()->hasAnyRole(['admin', 'registrar', 'accountant', 'lecturer', 'student'])) {
            Auth::logout();
            RateLimiter::hit($throttleKey, 60);
            $message = 'This account does not have portal access.';
            ToastMagic::error($message);

            return back()
                ->withErrors(['email' => $message])
                ->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
