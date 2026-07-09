<?php

namespace GhostZero\Kvdb\Support;

/**
 * Statically matches a requested KV key path against a declarative pattern.
 *
 * Supported segment types:
 * - a literal string, matched exactly
 * - `*`, matched against any single non-empty segment
 * - `{user_id}`, matched against the JWT subject only (strict, case-sensitive)
 *
 * Deliberately not an expression language: every pattern is a fixed-length
 * array of segments, so there is no operator precedence, no boolean logic,
 * and no way to reference values outside of the caller's own `$userId`.
 */
final class PathPatternMatcher
{
    /**
     * @param array $pattern The configured pattern, e.g. ['todos', '{user_id}', '*'].
     * @param array $key The requested key path, e.g. ['todos', '123', 'task_1'].
     * @param string $userId The authenticated user's identifier (JWT `sub`).
     */
    public static function matches(array $pattern, array $key, string $userId): bool
    {
        // Reject anything but a plain, contiguous, integer-indexed list of
        // segments up front — a mismatched shape must never fall through to
        // segment-by-segment comparison.
        if (!self::isFlatStringList($pattern) || !self::isFlatStringList($key)) {
            return false;
        }

        if (count($pattern) !== count($key)) {
            return false;
        }

        if ($userId === '') {
            return false;
        }

        foreach ($pattern as $index => $segment) {
            $actual = $key[$index];

            // Empty segments only occur via a doubled slash (`a//b`) or a
            // leading/trailing slash and must never be treated as a valid
            // match for `*` or a literal, since that would let a requested
            // path resolve to a shorter effective shape than the pattern
            // implies. Segments are expected to already be split on `/`
            // (see HasFrontendJwt), but a defensive re-check here means a
            // future caller can never smuggle an extra path segment inside
            // what a `*`/`{user_id}` wildcard treats as a single one.
            if ($actual === '' || str_contains($actual, '/')) {
                return false;
            }

            if ($segment === '*') {
                continue;
            }

            if ($segment === '{user_id}') {
                if ($actual !== $userId) {
                    return false;
                }
                continue;
            }

            if ($segment !== $actual) {
                return false;
            }
        }

        return true;
    }

    /**
     * True if every element of the array is a string and the array is a
     * plain 0-indexed list (no gaps, no string keys).
     */
    private static function isFlatStringList(array $value): bool
    {
        $expectedIndex = 0;

        foreach ($value as $index => $item) {
            if ($index !== $expectedIndex || !is_string($item)) {
                return false;
            }
            $expectedIndex++;
        }

        return true;
    }
}
