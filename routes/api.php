<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::post('/test', [ProductController::class, 'testMakePublic']);

Route::get('/categories', [ProductController::class, 'showCategories']);
Route::get('/categories/{categorySlug}', [ProductController::class, 'showByCategory']);

Route::post('/categories/{categorySlug}', [ProductController::class, 'store']);
Route::put('/categories/{categorySlug}/{productSlug}', [ProductController::class, 'update']);
Route::delete('/categories/{categorySlug}/{productSlug}', [ProductController::class, 'destroy']);