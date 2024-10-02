<?php

namespace GhostZero\Kvdb\Http\Middleware;

use Closure;
use GhostZero\Kvdb\Models\AccessToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasAccessToken
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $bucketId = $request->route('bucket');
        $accessToken = AccessToken::query()->whereKey($request->bearerToken())->first();

        if ($accessToken === null || $accessToken->bucket_id !== $bucketId || !$accessToken->can($ability)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
