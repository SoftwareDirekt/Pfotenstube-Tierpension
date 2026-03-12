<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyHomepageToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.pfotenstube.api_token');

        if (!$expectedToken || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid API Token.',
            ], 401);
        }

        return $next($request);
    }
}
