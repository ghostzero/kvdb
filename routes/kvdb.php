<?php

use GhostZero\Kvdb\Http\Controllers\BucketController;
use GhostZero\Kvdb\Http\Controllers\CRUDController;
use GhostZero\Kvdb\Http\Middleware\HasAccessToken;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'kvdb.',
    'prefix' => config('kvdb.path', 'kvdb'),
    'domain' => config('kvdb.domain'),
], function () {
    Route::post('/buckets', [BucketController::class, 'store'])
        ->name('crud.create');

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