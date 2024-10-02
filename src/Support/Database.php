<?php

namespace GhostZero\Kvdb\Support;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Facades\Storage;

class Database
{
    public static function select(string $database): Connection
    {
        $config = self::makeConfig($database);

        $container = new Container();
        $factory = new ConnectionFactory($container);

        return $factory->make($config);
    }

    public static function getMigrationPath(): string
    {
        if (file_exists($path = database_path('migrations/kvdb'))) {
            return $path;
        }

        return __DIR__ . '/../../migrations/kvdb';
    }

    public static function getRealPath(string $uuid): string
    {
        [$disk, $path] = self::getDiskAndPath($uuid);
        return $disk->path($path);
    }

    public static function getDiskAndPath(string $uuid): array
    {
        $disk = Storage::disk(config('kvdb.storage.disk'));
        $path = sprintf('%s/%s.sqlite', config('kvdb.storage.path'), $uuid);
        return [$disk, $path];
    }

    public static function makeConfig(string $database): array
    {
        return [
            'driver' => 'sqlite',
            'database' => self::getRealPath($database),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];
    }

    public static function isValidUuid(bool|array|string $database)
    {
        return is_string($database) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $database);
    }
}
