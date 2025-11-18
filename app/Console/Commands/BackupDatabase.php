<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Exception;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:complete {--type=complete : Backup type (folders|database|complete)} {--keep-days=7 : Days to keep old backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create automated backup of the system (database + files)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $keepDays = (int) $this->option('keep-days');

        $this->info("=== Automated Backup Started ===");
        $this->info("Type: {$type}");
        $this->info("Time: " . date('Y-m-d H:i:s'));
        $this->newLine();

        try {
            // Create backup based on type
            switch ($type) {
                case 'folders':
                    $result = $this->backupFolders();
                    break;
                case 'database':
                    $result = $this->backupDatabase();
                    break;
                case 'complete':
                default:
                    $result = $this->backupComplete();
                    break;
            }

            if ($result['success']) {
                $this->info("✓ Backup created successfully!");
                $this->info("File: {$result['backup_file']}");
                $this->info("Size: {$result['size_mb']} MB");

                if (isset($result['statistics'])) {
                    $this->newLine();
                    $this->info("Statistics:");
                    foreach ($result['statistics'] as $key => $value) {
                        if (!is_array($value)) {
                            $this->line("  - " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}");
                        }
                    }
                }

                // Clean up old backups
                $this->newLine();
                $this->info("Cleaning up old backups (keeping last {$keepDays} days)...");
                $deleted = $this->cleanupOldBackups($keepDays);
                $this->info("✓ Deleted {$deleted} old backup(s)");

                Log::info("Automated backup completed successfully", $result);

                return Command::SUCCESS;
            } else {
                $this->error("✗ Backup failed: " . $result['message']);
                Log::error("Automated backup failed", $result);
                return Command::FAILURE;
            }

        } catch (Exception $e) {
            $this->error("✗ Backup failed with exception: " . $e->getMessage());
            Log::error("Automated backup exception: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Backup folders and files
     */
    private function backupFolders()
    {
        try {
            $backupFolder = storage_path('app/backups');
            if (!file_exists($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            $timestamp = date('Y-m-d_His');
            $backupZipName = 'auto_folders_backup_' . $timestamp . '.zip';
            $backupZipPath = $backupFolder . '/' . $backupZipName;

            $zip = new ZipArchive;
            if ($zip->open($backupZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Could not create backup zip file');
            }

            // Backup folder-zip directory
            $folderZipPath = storage_path('app/public/uploads/folder-zip');
            $backupCount = 0;

            if (file_exists($folderZipPath)) {
                $files = scandir($folderZipPath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $sourceFilePath = $folderZipPath . '/' . $file;
                        if (is_file($sourceFilePath)) {
                            $zip->addFile($sourceFilePath, 'folder-zips/' . $file);
                            $backupCount++;
                        }
                    }
                }
            }

            $readmeContent = "=== AUTOMATED FOLDERS BACKUP ===\n";
            $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readmeContent .= "Total Zip Files: {$backupCount}\n";
            $zip->addFromString('README.txt', $readmeContent);

            $zip->close();

            $fileSize = filesize($backupZipPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return [
                'success' => true,
                'backup_file' => $backupZipName,
                'backup_path' => $backupZipPath,
                'size_mb' => $fileSizeMB,
                'statistics' => [
                    'total_files' => $backupCount
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Backup complete database
     */
    private function backupDatabase()
    {
        try {
            $backupFolder = storage_path('app/backups');
            if (!file_exists($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            $timestamp = date('Y-m-d_His');
            $backupZipName = 'auto_database_backup_' . $timestamp . '.zip';
            $backupZipPath = $backupFolder . '/' . $backupZipName;

            $zip = new ZipArchive;
            if ($zip->open($backupZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Could not create backup zip file');
            }

            // Get all tables
            $tables = $this->getAllTables();
            $totalRecords = 0;
            $backupData = [];

            foreach ($tables as $table) {
                $tableData = DB::table($table)->get()->toArray();
                $backupData[$table] = $tableData;
                $totalRecords += count($tableData);

                $tableJson = json_encode($tableData, JSON_PRETTY_PRINT);
                $zip->addFromString("database/tables/{$table}.json", $tableJson);
            }

            $completeDump = json_encode($backupData, JSON_PRETTY_PRINT);
            $zip->addFromString('database/complete_database.json', $completeDump);

            $readmeContent = "=== AUTOMATED DATABASE BACKUP ===\n";
            $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readmeContent .= "Database: " . DB::connection()->getDatabaseName() . "\n";
            $readmeContent .= "Total Tables: " . count($tables) . "\n";
            $readmeContent .= "Total Records: {$totalRecords}\n";
            $zip->addFromString('README.txt', $readmeContent);

            $zip->close();

            $fileSize = filesize($backupZipPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return [
                'success' => true,
                'backup_file' => $backupZipName,
                'backup_path' => $backupZipPath,
                'size_mb' => $fileSizeMB,
                'statistics' => [
                    'total_tables' => count($tables),
                    'total_records' => $totalRecords
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Backup complete system (database + files)
     */
    private function backupComplete()
    {
        try {
            $backupFolder = storage_path('app/backups');
            if (!file_exists($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            $timestamp = date('Y-m-d_His');
            $backupZipName = 'auto_complete_backup_' . $timestamp . '.zip';
            $backupZipPath = $backupFolder . '/' . $backupZipName;

            $zip = new ZipArchive;
            if ($zip->open($backupZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Could not create backup zip file');
            }

            // 1. Backup database
            $this->line("  Backing up database...");
            $tables = $this->getAllTables();
            $totalRecords = 0;
            $backupData = [];

            foreach ($tables as $table) {
                $tableData = DB::table($table)->get()->toArray();
                $backupData[$table] = $tableData;
                $totalRecords += count($tableData);

                $tableJson = json_encode($tableData, JSON_PRETTY_PRINT);
                $zip->addFromString("database/tables/{$table}.json", $tableJson);
            }

            $completeDump = json_encode($backupData, JSON_PRETTY_PRINT);
            $zip->addFromString('database/complete_database.json', $completeDump);

            // 2. Backup folder zips
            $this->line("  Backing up folder files...");
            $folderZipPath = storage_path('app/public/uploads/folder-zip');
            $backupCount = 0;

            if (file_exists($folderZipPath)) {
                $files = scandir($folderZipPath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $sourceFilePath = $folderZipPath . '/' . $file;
                        if (is_file($sourceFilePath)) {
                            $zip->addFile($sourceFilePath, 'files/folder-zips/' . $file);
                            $backupCount++;
                        }
                    }
                }
            }

            // 3. Backup profile images
            $this->line("  Backing up profile images...");
            $profilePath = storage_path('app/public/profiles');
            $profileCount = 0;

            if (file_exists($profilePath)) {
                $files = scandir($profilePath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $sourceFilePath = $profilePath . '/' . $file;
                        if (is_file($sourceFilePath)) {
                            $zip->addFile($sourceFilePath, 'files/profiles/' . $file);
                            $profileCount++;
                        }
                    }
                }
            }

            $readmeContent = "=== AUTOMATED COMPLETE SYSTEM BACKUP ===\n";
            $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readmeContent .= "Database: " . DB::connection()->getDatabaseName() . "\n";
            $readmeContent .= "Total Tables: " . count($tables) . "\n";
            $readmeContent .= "Total Records: {$totalRecords}\n";
            $readmeContent .= "Folder Zips: {$backupCount}\n";
            $readmeContent .= "Profile Images: {$profileCount}\n";
            $zip->addFromString('README.txt', $readmeContent);

            $zip->close();

            $fileSize = filesize($backupZipPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return [
                'success' => true,
                'backup_file' => $backupZipName,
                'backup_path' => $backupZipPath,
                'size_mb' => $fileSizeMB,
                'statistics' => [
                    'total_tables' => count($tables),
                    'total_records' => $totalRecords,
                    'folder_zips' => $backupCount,
                    'profile_images' => $profileCount
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Clean up old backups
     */
    private function cleanupOldBackups($keepDays)
    {
        $backupFolder = storage_path('app/backups');
        $deleted = 0;

        if (!file_exists($backupFolder)) {
            return $deleted;
        }

        $files = scandir($backupFolder);
        $cutoffTime = time() - ($keepDays * 24 * 60 * 60);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $filePath = $backupFolder . '/' . $file;

                // Only delete automated backups (those starting with 'auto_')
                if (strpos($file, 'auto_') === 0 && filemtime($filePath) < $cutoffTime) {
                    if (unlink($filePath)) {
                        $deleted++;
                        $this->line("  - Deleted: {$file}");
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * Get all table names
     */
    private function getAllTables()
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . DB::connection()->getDatabaseName();

        return array_map(function($table) use ($dbName) {
            return $table->$dbName;
        }, $tables);
    }
}
