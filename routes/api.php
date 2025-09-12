<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\FolderController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
Route::apiResource('residents', ResidentController::class);

//folder routes
Route::get('/folders', [FolderController::class, 'index']);
Route::post('/folders', [FolderController::class, 'store']);         
Route::get('/folders/download/{zipName}', [FolderController::class, 'download']); 
Route::get('/folders/{id}', [FolderController::class, 'show']);
Route::put('/folders/{id}', [FolderController::class, 'update']);
Route::delete('/folders/{id}', [FolderController::class, 'destroy']);