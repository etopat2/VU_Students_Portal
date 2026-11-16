<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()?->must_change_password
            && ! in_array($request->route()?->getName(), ['settings', 'settings.update', 'logout'], true)
        ) {
            return redirect()->route('settings');
        }

        return $next($request);
    }
}
