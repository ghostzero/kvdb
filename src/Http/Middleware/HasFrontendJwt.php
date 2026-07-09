<?php

namespace GhostZero\Kvdb\Http\Middleware;

use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use GhostZero\Kvdb\Models\Bucket;
use GhostZero\Kvdb\Support\PathPatternMatcher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Authenticates a bucket request using a frontend-issued user JWT instead of
 * a backend accessToken, and authorizes it against the bucket's declarative
 * `frontend_rules` — a fixed set of `{user_id}`/`*` key path patterns rather
 * than an evaluated expression, so there is no rule syntax that can be
 * bypassed by crafting the expression itself.
 */
class HasFrontendJwt
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return $this->unauthorized('Missing bearer token');
        }

        $bucket = Bucket::query()->find((string)$request->route('bucket'));

        if ($bucket === null) {
            return $this->unauthorized('Unauthorized');
        }

        $jwtConfig = (array)($bucket->jwt_config ?? []);
        $secret = $jwtConfig['secret'] ?? config('kvdb.jwt.secret');
        // The algorithm is always taken from configuration, never from the
        // token header, so a forged header cannot downgrade verification
        // (the classic "alg confusion" / "alg: none" bypass).
        $algo = $jwtConfig['algo'] ?? config('kvdb.jwt.algo', 'HS256');

        if (!$secret) {
            return $this->unauthorized('Frontend JWT auth is not configured for this bucket');
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, $algo));
        } catch (ExpiredException|SignatureInvalidException|UnexpectedValueException) {
            return $this->unauthorized('Invalid or expired token');
        }

        $userId = $decoded->sub ?? null;

        if (!is_string($userId) || $userId === '') {
            return $this->unauthorized('Token is missing a subject claim');
        }

        $path = (string)($request->route('path') ?? '');
        $requestedKey = $path === '' ? [] : explode('/', $path);

        $rules = (array)($bucket->frontend_rules ?? []);

        if (!$this->isAllowed($rules, $ability, $requestedKey, $userId)) {
            return $this->forbidden('This key path is not allowed for this user');
        }

        $request->attributes->set('kvdb_jwt_user_id', $userId);

        return $next($request);
    }

    /**
     * @param array $rules The bucket's configured frontend rules.
     * @param string $ability The ability required for this route, e.g. `read` or `write`.
     * @param array $requestedKey The requested key path segments.
     * @param string $userId The authenticated user's identifier (JWT `sub`).
     */
    private function isAllowed(array $rules, string $ability, array $requestedKey, string $userId): bool
    {
        foreach ($rules as $rule) {
            $pattern = $rule['pattern'] ?? null;
            $abilities = $rule['abilities'] ?? [];

            if (!is_array($pattern) || !is_array($abilities) || !in_array($ability, $abilities, true)) {
                continue;
            }

            if (PathPatternMatcher::matches($pattern, $requestedKey, $userId)) {
                return true;
            }
        }

        return false;
    }

    private function unauthorized(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }

    private function forbidden(string $message): Response
    {
        return response()->json(['message' => $message], 403);
    }
}
