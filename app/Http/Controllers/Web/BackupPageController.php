<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use Exception;

class BackupPageController extends Controller
{
    /**
     * Display the backup management page
     */
    public function index()
    {
        $backups = $this->getBackupList();
        $statistics = $this->getSystemStatistics();
        $scheduler = $this->getSchedulerInfo();

        return view('backup.index', compact('backups', 'statistics', 'scheduler'));
    }

    /**
     * Create a new backup via web interface
     */
    public function createBackup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:folders,database,complete'
        ]);

        $type = $request->input('type');

        try {
            // Run the backup command
            Artisan::call('backup:complete', [
                '--type' => $type,
                '--keep-days' => 7
            ]);

            $output = Artisan::output();

            return redirect()->route('backup.index')
                ->with('success', "Backup created successfully! Type: {$type}")
                ->with('output', $output);

        } catch (Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a specific backup file
     */
    public function downloadBackup($filename)
    {
        $backupPath = storage_path('app/backups/' . $filename);

        if (!file_exists($backupPath)) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup file not found');
        }

        return response()->download($backupPath);
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup($filename)
    {
        $backupPath = storage_path('app/backups/' . $filename);

        if (!file_exists($backupPath)) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup file not found');
        }

        if (unlink($backupPath)) {
            return redirect()->route('backup.index')
                ->with('success', 'Backup deleted successfully');
        }

        return redirect()->route('backup.index')
            ->with('error', 'Failed to delete backup');
    }

    /**
     * Get list of all backups
     */
    private function getBackupList()
    {
        $backupFolder = storage_path('app/backups');
        $backups = [];

        if (!file_exists($backupFolder)) {
            return $backups;
        }

        $files = scandir($backupFolder);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $filePath = $backupFolder . '/' . $file;
                $backups[] = [
                    'filename' => $file,
                    'size' => filesize($filePath),
                    'size_mb' => round(filesize($filePath) / 1024 / 1024, 2),
                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                    'timestamp' => filemtime($filePath),
                    'type' => $this->getBackupType($file),
                    'is_auto' => strpos($file, 'auto_') === 0
                ];
            }
        }

        // Sort by creation date, newest first
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Get backup type from filename
     */
    private function getBackupType($filename)
    {
        if (strpos($filename, 'complete') !== false) {
            return 'Complete System';
        } elseif (strpos($filename, 'database') !== false) {
            return 'Database Only';
        } elseif (strpos($filename, 'folders') !== false) {
            return 'Folders Only';
        }
        return 'Unknown';
    }

    /**
     * Get system statistics
     */
    private function getSystemStatistics()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = 'Tables_in_' . DB::connection()->getDatabaseName();

            $totalTables = count($tables);
            $totalRecords = 0;

            foreach ($tables as $table) {
                $tableName = $table->$dbName;
                $count = DB::table($tableName)->count();
                $totalRecords += $count;
            }

            $folderZipPath = storage_path('app/public/uploads/folder-zip');
            $folderZipCount = 0;
            if (file_exists($folderZipPath)) {
                $files = scandir($folderZipPath);
                $folderZipCount = count(array_filter($files, function($file) use ($folderZipPath) {
                    return $file !== '.' && $file !== '..' && is_file($folderZipPath . '/' . $file);
                }));
            }

            $profilePath = storage_path('app/public/profiles');
            $profileCount = 0;
            if (file_exists($profilePath)) {
                $files = scandir($profilePath);
                $profileCount = count(array_filter($files, function($file) use ($profilePath) {
                    return $file !== '.' && $file !== '..' && is_file($profilePath . '/' . $file);
                }));
            }

            return [
                'database_name' => DB::connection()->getDatabaseName(),
                'total_tables' => $totalTables,
                'total_records' => $totalRecords,
                'folder_zips' => $folderZipCount,
                'profile_images' => $profileCount
            ];

        } catch (Exception $e) {
            return [
                'database_name' => 'N/A',
                'total_tables' => 0,
                'total_records' => 0,
                'folder_zips' => 0,
                'profile_images' => 0
            ];
        }
    }

    /**
     * Get scheduler information
     */
    private function getSchedulerInfo()
    {
        try {
            // Get scheduler output
            Artisan::call('schedule:list');
            $output = Artisan::output();

            // Parse the schedule for backup command
            $isScheduled = strpos($output, 'backup:complete') !== false;

            // Calculate next run time (2:00 AM)
            $now = now();
            $nextRun = now()->setTime(2, 0, 0);

            // If it's already past 2 AM, schedule for tomorrow
            if ($now->greaterThan($nextRun)) {
                $nextRun->addDay();
            }

            // Get recent automated backups
            $backupFolder = storage_path('app/backups');
            $recentAutoBackups = [];

            if (file_exists($backupFolder)) {
                $files = scandir($backupFolder);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && strpos($file, 'auto_') === 0) {
                        $filePath = $backupFolder . '/' . $file;
                        $recentAutoBackups[] = [
                            'filename' => $file,
                            'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'timestamp' => filemtime($filePath)
                        ];
                    }
                }

                // Sort by timestamp, newest first
                usort($recentAutoBackups, function($a, $b) {
                    return $b['timestamp'] - $a['timestamp'];
                });

                // Keep only last 5
                $recentAutoBackups = array_slice($recentAutoBackups, 0, 5);
            }

            return [
                'is_active' => $isScheduled,
                'schedule_time' => '02:00 AM Daily',
                'timezone' => config('app.timezone', 'UTC'),
                'next_run' => $nextRun->format('Y-m-d H:i:s'),
                'next_run_human' => $nextRun->diffForHumans(),
                'retention_days' => 7,
                'backup_type' => 'Complete System',
                'recent_auto_backups' => $recentAutoBackups,
                'total_auto_backups' => count($recentAutoBackups)
            ];

        } catch (Exception $e) {
            return [
                'is_active' => false,
                'schedule_time' => 'Not configured',
                'timezone' => 'N/A',
                'next_run' => 'N/A',
                'next_run_human' => 'N/A',
                'retention_days' => 7,
                'backup_type' => 'N/A',
                'recent_auto_backups' => [],
                'total_auto_backups' => 0
            ];
        }
    }
}
