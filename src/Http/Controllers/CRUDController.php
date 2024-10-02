<?php

namespace GhostZero\Kvdb\Http\Controllers;

use GhostZero\Kvdb\Rules\DocumentSize;
use GhostZero\Kvdb\Support\Database;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class CRUDController extends Controller
{
    public function get(string $bucket, string $path = ''): array
    {
        $item = Database::select($bucket)->table('kvdb_store')
            ->where('key_path', $path)
            ->first();

        if (!$item) {
            return [
                'key' => explode('/', $path),
                'value' => null,
                'version' => null,
            ];
        }

        return [
            'key' => explode('/', $item->key_path),
            'value' => json_decode($item->value),
            'version' => $item->version,
            'encrypted' => (boolean)$item->encrypted ?? false,
        ];
    }

    public function put(Request $request, string $bucket, string $path = ''): array
    {
        $attributes = $request->validate([
            'value' => ['required', new DocumentSize(16 * 1024)],
            'encrypted' => ['nullable', 'boolean'],
        ]);
        $encrypted = $attributes['encrypted'] ?? false;

        Database::select($bucket)->table('kvdb_store')->updateOrInsert(
            ['key_path' => $path],
            [
                'value' => json_encode($attributes['value']),
                'version' => $version = round(microtime(true) * 1000),
                'encrypted' => $encrypted,
            ]
        );

        return [
            'key' => explode('/', $path),
            'value' => $attributes['value'],
            'version' => $version,
            'encrypted' => $encrypted,
        ];
    }

    public function delete(string $bucket, string $path = ''): string
    {
        Database::select($bucket)->table('kvdb_store')
            ->where('key_path', $path)
            ->delete();

        return new Response('', 204);
    }

    public function list(Request $request, string $bucket): array
    {
        $attributes = array_merge([
            'prefix' => [],
            'limit' => 1000,
            'offset' => 0,
            'reverse' => false,
        ], $request->validate([
            'prefix' => 'nullable|array',
            'prefix.*' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:1000',
            'offset' => 'nullable|integer|min:0',
            'reverse' => 'nullable|boolean',
        ]));

        $query = Database::select($bucket)->table('kvdb_store');

        foreach ($attributes['prefix'] as $prefix) {
            $query->where('key_path', 'like', $prefix . '%');
        }

        $query->orderBy('key_path', $attributes['reverse'] ? 'desc' : 'asc');

        $items = $query->limit($attributes['limit'])
            ->offset($attributes['offset'])
            ->get();

        return $items->map(function ($item) {
            return [
                'key' => explode('/', $item->key_path),
                'value' => json_decode($item->value),
                'version' => $item->version,
                'encrypted' => (boolean)$item->encrypted ?? false,
            ];
        })->all();
    }

    /**
     * @throws Throwable
     */
    public function atomic(Request $request, string $bucket): array
    {
        Log::info('atomic', $request->all());

        $attributes = $request->validate([
            'checks' => ['required', 'array'],
            'checks.*.key' => ['required', 'array'],
            'checks.*.key.*' => ['required', 'string'],
            'checks.*.version' => ['nullable', 'integer'],
            'operations' => ['required', 'array'],
            'operations.*.type' => ['required', 'string', 'in:set,delete'],
            'operations.*.key' => ['required', 'array'],
            'operations.*.key.*' => ['required', 'string'],
            // value is optional for delete
            'operations.*.value' => ['nullable'],
            'operations.*.encrypted' => ['nullable', 'boolean'],
        ]);

        return Database::select($bucket)->transaction(function (Connection $connection) use ($attributes) {
            foreach ($attributes['checks'] as $check) {
                $builder = $connection->table('kvdb_store')->where([
                    ['key_path', '=', implode('/', $check['key'])],
                    ['version', '=', $check['version']],
                ]);

                if (!$builder->exists() && $check['version'] !== null) {
                    return $this->fail(sprintf(
                        'check failed: %s version %s',
                        implode('/', $check['key']),
                        $check['version']
                    ));
                }
            }

            $version = round(microtime(true) * 1000);

            foreach ($attributes['operations'] as $operation) {
                $key = implode('/', $operation['key']);
                $encrypted = $operation['encrypted'] ?? false;

                if ($operation['type'] === 'set') {
                    $connection->table('kvdb_store')->updateOrInsert(
                        ['key_path' => $key],
                        [
                            'value' => json_encode($operation['value']),
                            'version' => $version,
                            'encrypted' => $encrypted,
                        ]
                    );
                } elseif ($operation['type'] === 'delete') {
                    $connection->table('kvdb_store')->where('key_path', $key)->delete();
                }
            }

            return [
                'ok' => true,
                'version' => $version,
            ];
        });
    }

    private function fail(string $error): array
    {
        return [
            'ok' => false,
            'error' => $error,
        ];
    }
}
