<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Crypt;

class AuthenticateAdmin
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
        $cookie = $_COOKIE['admin_login'] ?? false;
        if ($cookie && Crypt::decrypt($cookie) === '7eBh8":4vy%g?v5d]AmBZmu4P~k^p9^k') {
            return $next($request);
        }

        return redirect('/admin/login');
    }
}
