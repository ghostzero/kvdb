<?php

namespace GhostZero\Kvdb\Observers;

use GhostZero\Kvdb\Models\Bucket;

class BucketObserver
{
    public function created(Bucket $bucket): void
    {
        $bucket->accessTokens()->forceCreate([
            'secret' => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='),
            'abilities' => ['read', 'write'],
        ]);
    }
}
