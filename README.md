# Key-Value Store for Laravel

## Installation

You can install the package via Composer:

```bash
composer require ghostzero/kvdb
```

## Configuration

Publish the config file (optional — the package ships with sensible defaults):

```bash
php artisan vendor:publish --tag=kvdb-config
```

Run the migrations:

```bash
php artisan migrate
```

### Environment Variables

| Variable          | Default    | Description                                                                             |
|--------------------|------------|-------------------------------------------------------------------------------------------|
| `KVDB_JWT_SECRET`  | `null`     | Default signing secret used to verify frontend JWTs (see below). Required to use JWT auth. |
| `KVDB_JWT_ALGO`    | `HS256`    | Default JWT algorithm. Must match whatever your issuer signs tokens with.                  |

## Buckets & Access Tokens

Every dataset lives in its own **bucket** — an isolated SQLite database created via:

```bash
curl -X POST https://your-app.test/v1/buckets \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "owner@example.com"}'
```

The response includes the bucket `id` and a default read/write **access token**:

```json
{
  "id": "9d1cb4c7-c683-4fa9-bc5f-13f5ad1ba745",
  "access_tokens": [
    { "secret": "9b9634a1-1655-4baf-bdf5-c04feffc68bd", "abilities": ["read", "write"] }
  ]
}
```

That access token is a **backend-only secret** — it grants unrestricted read/write access to the entire bucket and must never be shipped to a browser or app. It authenticates requests under `/v1/{bucket}/...`. This is the flow to use from your own server/Edge Function.

## Frontend JWT Access (BaaS)

If a frontend widget only needs to read/write **its own user's data** (e.g. a personal to-do list, per-viewer settings), you don't need to write backend code to proxy that. kvdb can authenticate the request directly with a JWT the frontend already has, and authorize it against a declarative, per-bucket rule set — without ever handing the frontend the bucket's access token.

This is a **separate route group** (`/v1/frontend/{bucket}/...`), authenticated and authorized completely differently from the backend routes above:

| Backend (`/v1/{bucket}/...`)                | Frontend (`/v1/frontend/{bucket}/...`)               |
|-----------------------------------------------|---------------------------------------------------------|
| `Authorization: Bearer <accessToken>`         | `Authorization: Bearer <user JWT>`                       |
| One secret = full bucket access               | One JWT = access scoped to key paths matching its `sub`  |
| `get`, `put`, `delete`, `list`, `atomic`      | `get`, `put`, `delete` only (see [Limitations](#limitations)) |

### 1. Configure how JWTs are verified

By default every bucket verifies frontend JWTs with the app-wide secret/algorithm from `.env`:

```dotenv
KVDB_JWT_SECRET=a-long-random-secret-shared-with-your-auth-issuer
KVDB_JWT_ALGO=HS256
```

If different buckets are issued tokens by different auth providers (multi-tenant), override this per bucket via the `jwt_config` column instead:

```php
use GhostZero\Kvdb\Models\Bucket;

$bucket = Bucket::find($bucketId);
$bucket->jwt_config = [
    'secret' => 'this-tenants-own-signing-secret',
    'algo' => 'HS256', // optional, defaults to config('kvdb.jwt.algo')
];
$bucket->save();
```

> [!IMPORTANT]
> The algorithm is always taken from this configuration — **never** from the JWT header itself. This prevents an "alg confusion" attack where a forged token header tries to downgrade or disable signature verification.

kvdb only **verifies** JWTs; it does not issue them. Mint tokens with your own auth system (login flow, session exchange, etc.), signed with the same secret/algorithm you configured above. The only claim kvdb reads is `sub` — it must be the stable identifier for the user, as a non-empty string:

```php
use Firebase\JWT\JWT;

$token = JWT::encode([
    'sub' => (string) $user->id, // required — matched against `{user_id}` in your rules
    'iat' => time(),
    'exp' => time() + 3600,      // required for any meaningful expiry — always set one
], config('kvdb.jwt.secret'), config('kvdb.jwt.algo'));
```

### 2. Define which key paths a JWT may touch

Each bucket has a `frontend_rules` column: a list of `{pattern, abilities}` rules. A request is allowed if **any** rule's pattern matches the requested key **and** lists the required ability (`read` for GET, `write` for PUT/DELETE).

```php
$bucket->frontend_rules = [
    [
        'pattern' => ['todos', '{user_id}', '*'],
        'abilities' => ['read', 'write'],
    ],
    [
        'pattern' => ['settings', '{user_id}'],
        'abilities' => ['read'],
    ],
];
$bucket->save();
```

Pattern segments:

| Segment       | Matches                                                                 |
|---------------|--------------------------------------------------------------------------|
| a literal     | that exact string, nothing else                                          |
| `*`           | any single, non-empty segment                                            |
| `{user_id}`   | a single segment **strictly equal** to the JWT's `sub` claim              |

This is deliberately **not** an expression language — there's no `auth.role == 'x'` syntax to evaluate, no boolean operators, and nothing that can reference values outside of the caller's own `sub`. A pattern is a fixed-length shape: the requested key must have exactly as many segments as the pattern, every segment must be non-empty, and no segment may contain a literal `/`. There is no prefix or partial matching — `['todos', '{user_id}']` does **not** also allow `['todos', '{user_id}', 'x']`.

With the rules above, a JWT for user `123` can:
- `GET`/`PUT`/`DELETE` any key under `todos/123/...` (one level deep, via `*`)
- `GET` (but not write) `settings/123`
- nothing else — `GET todos/456/x` (another user's data) or `GET todos/123` (wrong depth) are both rejected

### 3. Connect from the frontend

Using [`@gz/kv`](https://github.com/ghostzero/kv), pass `jwt` instead of `accessToken`:

```ts
import { connect } from "@gz/kv";

const kv = await connect({
    bucket: "9d1cb4c7-c683-4fa9-bc5f-13f5ad1ba745",
    jwt: userJwt, // the JWT for the currently logged-in viewer
});

await kv.set(["todos", currentUserId, "task1"], { text: "Buy milk" });
const entry = await kv.get(["todos", currentUserId, "task1"]);
```

See the [`@gz/kv` README](https://github.com/ghostzero/kv#frontend-safe-access-with-jwt) for the full client-side guide.

### Error responses

| Status | Meaning                                                                                          |
|--------|----------------------------------------------------------------------------------------------------|
| `401`  | No bearer token, bucket not found, JWT auth not configured for this bucket, or the JWT itself is missing/malformed/expired/signed with the wrong key. |
| `403`  | The JWT is valid, but no rule allows this ability on this key path.                                |

### Limitations

- **`list` and `atomic` are not available on the frontend routes.** Both operate on a *set* of keys — a prefix scan, or an arbitrary batch of checks/operations — rather than the single key path these rules authorize per request, so neither can be safely rule-checked yet. Use `get`/`put`/`delete` on individual keys from the frontend, and keep any batch or listing operations server-side.
- Cross-user access (e.g. "a broadcaster can read any viewer's data") isn't supported by this rule format — patterns can only ever scope a key to *its own* `sub`. That's intentionally out of scope for now, since it needs role claims and a threat model for token replay that hasn't been built out yet.
