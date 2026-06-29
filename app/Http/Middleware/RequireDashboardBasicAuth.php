<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDashboardBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') !== 'production') {
            return $next($request);
        }

        $username = config('dashboard.username');
        $password = config('dashboard.password');

        if (! $username || ! $password) {
            abort(403, 'Dashboard credentials are not configured.');
        }

        if (
            hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword())
        ) {
            return $next($request);
        }

        return response('Authentication required.', 401, [
            'WWW-Authenticate' => sprintf('Basic realm="%s"', config('dashboard.realm')),
        ]);
    }
}
