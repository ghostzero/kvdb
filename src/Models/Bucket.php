<?php

namespace GhostZero\Kvdb\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property array|null $jwt_config
 * @property array|null $frontend_rules
 */
class Bucket extends Model
{
    use HasUuids;

    protected $table = 'kvdb_buckets';

    protected $guarded = [];

    protected $casts = [
        'jwt_config' => 'array',
        'frontend_rules' => 'array',
    ];

    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }
}
