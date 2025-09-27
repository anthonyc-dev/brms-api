<?php

use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\FolderController;
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'App\Http\Controllers\API'], function () {
    // --------------- Register and Login ----------------//
    Route::post('register', 'AuthenticationController@register')->name('register');
    Route::post('login', 'AuthenticationController@login')->name('login');

    // --------------- Admin Register and Login ----------------//
    Route::post('admin-register', 'AdminController@register')->name('admin-register');
    Route::post('admin-login', 'AdminController@login')->name('admin-login');

    // ------------------ Get Data && users ----------------------//
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('get-user', 'AuthenticationController@userInfo')->name('get-user');
        Route::post('logout', 'AuthenticationController@logOut')->name('logout');
        Route::put('update-password/{id}', 'AuthenticationController@updatePassword')->name('update-password');
        Route::post('request-document', 'RequestDocumentController@store')->name('request-document');
        Route::put('update-document/{id}', 'RequestDocumentController@update')->name('update-document');
        Route::delete('delete-document/{id}', 'RequestDocumentController@destroy')->name('delete-document');

        // ------------------ Complainant ----------------------//
        Route::post('complainant', 'ComplainantController@store')->name('complainant');
        Route::get('complainant-get/{id}', 'ComplainantController@show')->name('complainant-get');
        Route::put('complainant-update/{id}', 'ComplainantController@update')->name('complainant-update');
        Route::delete('complainant-delete/{id}', 'ComplainantController@destroy')->name('complainant-delete');
        Route::get('complainant-history', 'ComplainantController@index')->name('complainant-history');
    });

      // ------------------ Admin----------------------//
    Route::middleware('auth:admin')->group(function () {
        Route::get('admin-dashboard', 'AdminController@dashboard')->name('admin-dashboard');
        Route::post('admin-logout', 'AdminController@logOut')->name('admin-logout');
        Route::put('admin-update/{id}', 'AdminController@update')->name('admin-update');

         // ------------------ Admin/official event----------------------//
        Route::get('admin-get-event', 'EventController@index')->name('admin-get-event');
        Route::get('admin-get-event-by-id/{id}', 'EventController@show')->name('admin-get-event-by-id');
        Route::post('admin-event', 'EventController@store')->name('admin-event');
        Route::put('admin-event-update/{id}', 'EventController@update')->name('admin-event-update');  
        Route::delete('admin-event-delete/{id}', 'EventController@destroy')->name('admin-event-delete'); 
        
        // ------------------ Admin file storage----------------------//
        Route::get('/folders', [FolderController::class, 'index']);
        Route::post('/folders', [FolderController::class, 'store']);         
        Route::get('/folders/download/{zipName}', [FolderController::class, 'download']); 
        Route::get('/folders/{id}', [FolderController::class, 'show']);
        Route::put('/folders/{id}', [FolderController::class, 'update']);
        Route::delete('/folders/{id}', [FolderController::class, 'destroy']);
    });
});

// ------------------ Resident login/register----------------------//
Route::apiResource('residents', ResidentController::class);