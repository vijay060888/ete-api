<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class VerifyRole
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request    $request
     * @param \Closure   $next
     * @param int|string $role
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        $roles = explode('|', $role);
        for ($i = 0; $i < count($roles); $i++) {
            if ($this->auth->check() && $this->auth->user()->hasRole($roles[$i])) {
                return $next($request);
            }
        }

        return response()->json(['status' => 'error', 'message' => "You don't have access to this url"], 403);

    }
}
