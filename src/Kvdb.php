<?php

namespace GhostZero\Kvdb;

class Kvdb
{
    public static bool $ignoreRoutes = false;

    public static function ignoreRoutes(): void
    {
        self::$ignoreRoutes = true;
    }
}