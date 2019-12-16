<?php

namespace App\Http\Middleware;

use Closure;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $app_token = str_replace('Bearer ', '', $request->header('Authorization'));

        if (!in_array($app_token, config('app_tokens.tokens'))) {
            return response("FUCK YOU!", 500);
        }

        return $next($request);
    }
}
