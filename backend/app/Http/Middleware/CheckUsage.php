<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUsage
{
    public function handle(Request $request, Closure $next, string $actionType): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->canUse($actionType)) {
            return $next($request);
        }

        return response()->json([
            'error' => 'Monthly limit reached',
            'upgrade' => true,
            'tier' => $user->subscription_tier,
            'message' => "You've reached your {$actionType} limit for this month. Upgrade to Pro for unlimited access.",
        ], 403);
    }
}
