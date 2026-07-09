<?php

use GhostZero\Kvdb\Http\Controllers\BucketController;
use GhostZero\Kvdb\Http\Controllers\CRUDController;
use GhostZero\Kvdb\Http\Middleware\HasAccessToken;
use GhostZero\Kvdb\Http\Middleware\HasFrontendJwt;
use Illuminate\Support\Facades\Route;

/*
 * Frontend-safe routes, authenticated with a user JWT instead of a backend
 * accessToken and authorized per-request against the bucket's declarative
 * `frontend_rules` (see HasFrontendJwt). Kept on a distinct prefix so these
 * never share a URL with the backend accessToken routes below — and
 * registered first, since `kvdb.path`'s `/{bucket}/{path?}` wildcard would
 * otherwise greedily match `/v1/frontend/...` too (with a literal bucket id
 * of "frontend") if it were checked first.
 *
 * `list` and `atomic` are intentionally not exposed here: both operate on a
 * set of keys (a prefix scan, or an arbitrary batch of checks/operations)
 * rather than the single key path HasFrontendJwt authorizes per request, so
 * neither can be safely rule-checked yet.
 */
Route::group([
    'as' => 'kvdb.frontend.',
    'prefix' => config('kvdb.frontend_path', 'kvdb/v1/frontend'),
    'domain' => config('kvdb.domain'),
], function () {
    Route::get('/{bucket}/{path?}', [CRUDController::class, 'get'])
        ->middleware([HasFrontendJwt::class . ':read'])
        ->where('path', '.*')
        ->name('crud.get');

    Route::put('/{bucket}/{path?}', [CRUDController::class, 'put'])
        ->middleware([HasFrontendJwt::class . ':write'])
        ->where('path', '.*')
        ->name('crud.put');

    Route::delete('/{bucket}/{path?}', [CRUDController::class, 'delete'])
        ->middleware([HasFrontendJwt::class . ':write'])
        ->where('path', '.*')
        ->name('crud.delete');
});

Route::group([
    'as' => 'kvdb.',
    'prefix' => config('kvdb.path', 'kvdb/v1'),
    'domain' => config('kvdb.domain'),
], function () {
    Route::post('/buckets', [BucketController::class, 'store'])
        ->name('buckets.create');

    Route::get('/{bucket}', [CRUDController::class, 'list'])
        ->middleware([HasAccessToken::class . ':read'])
        ->name('crud.list');

    Route::post('/{bucket}/atomic', [CRUDController::class, 'atomic'])
        ->middleware([HasAccessToken::class . ':write'])
        ->name('crud.atomic');

    Route::get('/{bucket}/{path?}', [CRUDController::class, 'get'])
        ->middleware([HasAccessToken::class . ':read'])
        ->where('path', '.*')
        ->name('crud.get');

    Route::put('/{bucket}/{path?}', [CRUDController::class, 'put'])
        ->middleware([HasAccessToken::class . ':write'])
        ->where('path', '.*')
        ->name('crud.put');

    Route::delete('/{bucket}/{path?}', [CRUDController::class, 'delete'])
        ->middleware([HasAccessToken::class . ':write'])
        ->where('path', '.*')
        ->name('crud.delete');
});
