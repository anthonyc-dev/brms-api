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
        Route::post('update-profile/{id}', 'AuthenticationController@updateProfile')->name('update-profile');
        Route::get('get-profile/{id}', 'AuthenticationController@getProfileById')->name('get-profile');

        // ------------------ Email Notifications ----------------------//
        Route::post('send-test-email', 'NotificationController@sendTestEmail')->name('send-test-email');
        Route::post('send-notification/{requestId}', 'NotificationController@sendDocumentRequestNotification')->name('send-notification');
        Route::post('send-to-email', 'NotificationController@sendToEmail')->name('send-to-email');
    });
    

      // ------------------ Admin----------------------//
    Route::middleware('auth:admin')->group(function () {
        Route::get('admin-dashboard', 'AdminController@dashboard')->name('admin-dashboard');
        Route::post('admin-logout', 'AdminController@logOut')->name('admin-logout');
        Route::get('admin-displayById/{id}', 'AdminController@getById')->name('admin-display');
        Route::put('admin-update/{id}', 'AdminController@update')->name('admin-update');
        Route::delete('admin-delete/{id}', 'AdminController@destroy')->name('admin-delete');

         // ------------------ Admin/official event----------------------//
        Route::get('admin-get-event-by-id/{id}', 'EventController@show')->name('admin-get-event-by-id');
        Route::post('admin-event', 'EventController@store')->name('admin-event');
        Route::put('admin-event-update/{id}', 'EventController@update')->name('admin-event-update');  
        Route::delete('admin-event-delete/{id}', 'EventController@destroy')->name('admin-event-delete'); 
        
        // ------------------ Admin file storage----------------------//
      
        Route::post('/folders', [FolderController::class, 'store']);
        Route::get('/folders/download/{zipName}', [FolderController::class, 'download']);
        Route::get('/folders/{id}', [FolderController::class, 'show']);
        Route::put('/folders/{id}', [FolderController::class, 'update']);
        Route::delete('/folders/{id}', [FolderController::class, 'destroy']);
        Route::post('/folders/{id}/download-selected', [FolderController::class, 'downloadSelected']);
        Route::post('/folders/downloadSingle/{id}', [FolderController::class, 'downloadSingle']);

        // ------------------ Backup & Restore----------------------//
        Route::get('/backup/folders', 'BackupController@backupFolderTable')->name('backup-folders');
        Route::get('/backup/database', 'BackupController@backupDatabase')->name('backup-database');
        Route::get('/backup/complete', 'BackupController@backupComplete')->name('backup-complete');
        Route::get('/backup/list', 'BackupController@listBackups')->name('backup-list');
        Route::get('/backup/download/{backupFileName}', 'BackupController@downloadBackup')->name('backup-download');
        Route::post('/backup/restore-folders', 'BackupController@restoreBackup')->name('backup-restore-folders');
        Route::post('/backup/restore-database', 'BackupController@restoreDatabase')->name('backup-restore-database');
        Route::post('/backup/restore-complete', 'BackupController@restoreComplete')->name('backup-restore-complete');
        Route::delete('/backup/delete/{backupFileName}', 'BackupController@deleteBackup')->name('backup-delete');

        //-----admin update-------//
        Route::get('getById/{id}', 'AdminController@getById')->name('getById');
        
    
        
    });
   //-------General Routes-----------------------//
    Route::get('getAllByPosted/{posted_by}', 'EventController@getAllByPosted')->name('getAllByPosted');

    // ------------------ Routes Accessible by Both Sanctum & Admin ----------------//
    Route::middleware('auth:admin,sanctum')->group(function () {
        Route::post('request-document', 'RequestDocumentController@store')->name('request-document');
        Route::get('get-document/{userId}', 'RequestDocumentController@getDocumentsById')->name('get-document');
        Route::put('update-document/{id}', 'RequestDocumentController@update')->name('update-document');
        Route::delete('delete-document/{id}', 'RequestDocumentController@destroy')->name('delete-document');
        Route::get('getAlldocument', 'RequestDocumentController@index')->name('getAlldocument');  

          // ------------------ Complainant ----------------------//
        Route::post('complainant', 'ComplainantController@store')->name('complainant');
        Route::get('complainant-get/{userId}', 'ComplainantController@show')->name('complainant-get');
        Route::put('complainant-update/{id}', 'ComplainantController@update')->name('complainant-update');
        Route::delete('complainant-delete/{id}', 'ComplainantController@destroy')->name('complainant-delete');
        Route::get('complainant-history', 'ComplainantController@index')->name('complainant-history');

        Route::get('admin-get-event', 'EventController@index')->name('admin-get-event');
        Route::get('/folders', [FolderController::class, 'index']);
        Route::get('admin-display', 'AdminController@index')->name('admin-display');
        
    });

   

   
});
 

// ------------------ Resident login/register----------------------//
Route::apiResource('residents', ResidentController::class);
Route::get('residents/by-user/{userId}', [App\Http\Controllers\Api\ResidentController::class, 'showByUserId'])->name('residents.by-user');
