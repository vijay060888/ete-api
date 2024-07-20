<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;
class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard('party-api')->check() && Auth::guard('party-api')->user() instanceof \App\Models\User) {
            return $next($request);
        }

        return response()->json(['status' => 'error', 'message' => "Only Party Loggedin can access this URL"], 403);

    }
}
