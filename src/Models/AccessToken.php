<?php

namespace GhostZero\Kvdb\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $bucket_id
 * @property string $name
 * @property array $abilities
 * @property string $created_at
 * @property string $updated_at
 * @property string $expires_at
 * @property boolean $is_revoked
 * @property Bucket $bucket
 */
class AccessToken extends Model
{
    use HasUuids;

    protected $table = 'kvdb_access_tokens';

    protected $guarded = [];

    protected $casts = [
        'abilities' => 'array',
    ];

    public function bucket(): BelongsTo
    {
        return $this->belongsTo(Bucket::class);
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities);
    }
}
