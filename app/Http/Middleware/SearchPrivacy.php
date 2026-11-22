<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SearchPrivacy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (! $request->is('/') || ! $request->isMethod('GET')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
