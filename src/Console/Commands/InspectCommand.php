<?php

namespace GhostZero\Kvdb\Console\Commands;

use GhostZero\Kvdb\Support\Database;
use Illuminate\Console\Command;

class InspectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kvdb:inspect {uuid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect a database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $connection = Database::select($this->argument('uuid'));
        $size = filesize($connection->getDatabaseName());

        // Convert bytes to human-readable format
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024; $i++) $size /= 1024;
        $size = round($size, 2) . $units[$i];

        $this->info(sprintf('Database path: %s', $connection->getDatabaseName()));
        $this->info(sprintf('Database size: %s', $size));
        $this->info(sprintf('Leases: %s', $connection->table('kvdb_leases')->count()));
        $this->info(sprintf('Documents: %s', $connection->table('kvdb_store')->count()));
    }
}
