protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(\App\Http\Controllers\BackupController::class)->backupFolderTable();
    })->daily(); // run daily
}
