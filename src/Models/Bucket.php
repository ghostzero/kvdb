<?php

namespace GhostZero\Kvdb\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 */
class Bucket extends Model
{
    use HasUuids;

    protected $table = 'kvdb_buckets';

    protected $guarded = [];

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }
}
