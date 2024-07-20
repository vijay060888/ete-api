<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware
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
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json([
                      'status' => 'error',
                      'message' => 'Token is Invalid'
                    ], 401);
            }else if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json([
                      'status' => 'error',
                      'message' => 'Token is Expired'
                    ], 401);
            }else{
                return response()->json([
                      'status' => 'error',
                      'message' => 'HTTP_UNAUTHORIZED'
                    ], 401);
            }
        }
        return $next($request);
    }
}