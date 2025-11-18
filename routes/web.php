<?php

use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Web\BackupPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//---------backup GUI (Web Interface)--------------//
Route::get('/backup', [BackupPageController::class, 'index'])->name('backup.index');
Route::post('/backup/create', [BackupPageController::class, 'createBackup'])->name('backup.create');
Route::get('/backup/download/{filename}', [BackupPageController::class, 'downloadBackup'])->name('backup.download');
Route::delete('/backup/delete/{filename}', [BackupPageController::class, 'deleteBackup'])->name('backup.delete');

//---------backup API (Legacy)--------------//
Route::get('/backup/folder-table', [BackupController::class, 'backupFolderTable']);

