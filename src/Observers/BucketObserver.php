<?php

namespace GhostZero\Kvdb\Observers;

use GhostZero\Kvdb\Models\Bucket;

class BucketObserver
{
    public function created(Bucket $bucket): void
    {
        $bucket->accessTokens()->forceCreate([
            'abilities' => ['read', 'write'],
        ]);
    }
}
