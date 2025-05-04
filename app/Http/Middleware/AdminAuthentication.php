<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class AdminAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated as an admin
        if (!$request->session()->has('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}