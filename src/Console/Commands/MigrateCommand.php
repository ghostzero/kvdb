<?php

namespace GhostZero\Kvdb\Console\Commands;

use GhostZero\Kvdb\Models\Bucket;
use GhostZero\Kvdb\Support\Database;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kvdb:migrate {--database= : The database connection to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the kvdb database migrations';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $database = $this->option('database') ?? Str::orderedUuid();

        if (!$database || !Database::isValidUuid($database)) {
            $this->error('Invalid database connection');
            return;
        }

        $bucket = $this->firstOrCreateBucket($database);

        $this->info("Migrating bucket '{$bucket->getKey()}'");

        config(["database.connections.{$bucket->getKey()}" => Database::makeConfig($bucket->getKey())]);

        $this->call('migrate', [
            '--database' => $bucket->getKey(),
            '--realpath' => true,
            '--path' => Database::getMigrationPath(),
        ]);
    }

    private function firstOrCreateBucket(string $database)
    {
        if ($bucket = Bucket::query()->whereKey($database)->first()) {
            return $bucket;
        }

        if (!$this->confirm("Bucket '$database' not found. Do you want to create it?", true)) {
            throw new RuntimeException('Bucket not found');
        }

        return Bucket::query()->forceCreate([
            'id' => $database,
        ]);
    }
}
