<?php

namespace GhostZero\Kvdb\Http\Controllers;

use GhostZero\Kvdb\Console\Commands\MigrateCommand;
use GhostZero\Kvdb\Models\Bucket;
use GhostZero\Kvdb\Support\Database;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BucketController extends Controller
{
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'email' => ['required', 'email'],
            'description' => ['nullable', 'string'],
        ]);

        /** @var Bucket $bucket */
        $bucket = Bucket::query()->forceCreate($attributes);

        touch(Database::getRealPath($bucket->getKey()));

        Artisan::call(MigrateCommand::class, ['--database' => $bucket->getKey()]);

        return $bucket->loadMissing('accessTokens');
    }

    public function index()
    {
        return Bucket::with('accessTokens')->get();
    }
}
