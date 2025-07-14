<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::post('/test', [ProductController::class, 'testMakePublic']);

Route::prefix('categories')->group(function () {
    Route::get('/', [ProductController::class, 'showCategories']);
    Route::get('/{categorySlug}', [ProductController::class, 'showByCategory']);

    Route::post('/{categorySlug}', [ProductController::class, 'store']);
    Route::put('/{categorySlug}/{productSlug}', [ProductController::class, 'update']);
    Route::delete('/{categorySlug}/{productSlug}', [ProductController::class, 'destroy']);
});
