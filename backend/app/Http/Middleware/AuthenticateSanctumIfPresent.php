<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSanctumIfPresent
{
    /**
     * Authenticate a Sanctum bearer token when supplied, while allowing guests.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null) {
            return $next($request);
        }

        $user = Auth::guard('sanctum')->user();
        abort_if($user === null, Response::HTTP_UNAUTHORIZED, 'Phiên đăng nhập không hợp lệ.');

        Auth::setUser($user);
        $request->setUserResolver(static fn (): mixed => $user);

        return $next($request);
    }
}
