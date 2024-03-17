<?php

use App\Http\Controllers\Api\CRUDController;
use Illuminate\Support\Facades\Route;

Route::get('/{bucket}/{path?}', [CRUDController::class, 'get'])
    ->where('path', '.*')
    ->name('crud.get');

Route::post('/{bucket}/{path?}', [CRUDController::class, 'post'])
    ->where('path', '.*')
    ->name('crud.post');

Route::put('/{bucket}/{path?}', [CRUDController::class, 'put'])
    ->where('path', '.*')
    ->name('crud.put');

Route::delete('/{bucket}/{path?}', [CRUDController::class, 'delete'])
    ->where('path', '.*')
    ->name('crud.delete');

Route::post('/{bucket}/atomic', [CRUDController::class, 'atomic'])
    ->name('crud.atomic');
