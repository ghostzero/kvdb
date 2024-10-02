<?php

return [
    /**
     * The path to use for the routes.
     */
    'path' => 'kvdb',

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
];