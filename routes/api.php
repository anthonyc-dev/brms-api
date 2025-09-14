<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\FolderController;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'App\Http\Controllers\API'], function () {
    // --------------- Register and Login ----------------//
    Route::post('register', 'AuthenticationController@register')->name('register');
    Route::post('login', 'AuthenticationController@login')->name('login');

    // ------------------ Get Data ----------------------//
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('get-user', 'AuthenticationController@userInfo')->name('get-user');
        Route::post('logout', 'AuthenticationController@logOut')->name('logout');
        Route::post('request-document', 'RequestDocumentController@store')->name('request-document');
    });
});

Route::apiResource('products', ProductController::class);
Route::apiResource('residents', ResidentController::class);

//folder routes
Route::get('/folders', [FolderController::class, 'index']);
Route::post('/folders', [FolderController::class, 'store']);         
Route::get('/folders/download/{zipName}', [FolderController::class, 'download']); 
Route::get('/folders/{id}', [FolderController::class, 'show']);
Route::put('/folders/{id}', [FolderController::class, 'update']);
Route::delete('/folders/{id}', [FolderController::class, 'destroy']);