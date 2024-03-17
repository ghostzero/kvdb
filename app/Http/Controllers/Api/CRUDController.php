<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CRUDController extends Controller
{
    public function get(Request $request, string $bucket, string $path = ''): array
    {
        return [
            'key' => $path,
            'value' => [],
            'versionstamp' => 0,
        ];
    }

    public function put(Request $request, string $bucket, string $path = ''): array
    {
        return [
            'key' => $path,
            'value' => [],
            'versionstamp' => 0,
        ];
    }

    public function delete(Request $request, string $bucket, string $path = ''): string
    {
        return "DELETE $bucket $path";
    }

    public function atomic(Request $request, string $bucket): string
    {
        return "ATOMIC $bucket";
    }
}
