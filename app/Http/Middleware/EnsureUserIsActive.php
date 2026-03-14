<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            // Revoke the token if it's a personal access token
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact support.',
                'error_code' => 'ACCOUNT_SUSPENDED',
            ], 403);
        }

        return $next($request);
    }
}
