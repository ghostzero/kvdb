<?php

return [
    /**
     * The path to use for the routes.
     */
    'path' => 'v1',

    /**
     * The path to use for the frontend (JWT-authenticated) routes. Kept
     * separate from `path` so backend accessToken routes and frontend JWT
     * routes never share a URL, and access to one can be firewalled off
     * without affecting the other.
     */
    'frontend_path' => 'v1/frontend',

    /**
     * The domain to use for the routes.
     */
    'domain' => null,

    /**
     * The storage configuration for the SQLite databases.
     */
    'storage' => [
        /**
         * The disk to use for doing storage operations.
         */
        'disk' => 'local',

        /**
         * The path where the SQLite databases will be stored.
         */
        'path' => 'kvdb',
    ],

    /**
     * Default frontend JWT verification settings, used by the
     * `HasFrontendJwt` middleware for any bucket that does not configure its
     * own `jwt_config`. A bucket-level secret should be preferred for
     * multi-tenant setups; this is a convenience fallback for a single
     * shared issuer (e.g. one platform-wide auth provider).
     */
    'jwt' => [
        'secret' => env('KVDB_JWT_SECRET'),
        'algo' => env('KVDB_JWT_ALGO', 'HS256'),
    ],
];