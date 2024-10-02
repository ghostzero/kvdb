<?php

namespace GhostZero\Kvdb\Http\Controllers;

use GhostZero\Kvdb\Models\Bucket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

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

        $bucket->accessTokens()->forceCreate([
            'token' => Str::uuid(),
            'abilities' => ['read', 'write'],
        ]);

        Artisan::call('app:create-database', ['uuid' => $bucket->getKey()]);

        return $bucket->loadMissing('accessTokens');
    }
}
