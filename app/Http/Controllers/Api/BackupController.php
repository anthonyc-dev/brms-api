<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use ZipArchive;
use Exception;

class BackupController extends Controller
{
    /**
     * Create a complete backup of folders table and all zip files
     */
    public function backupFolderTable()
    {
        try {
            // Create backup directory if it doesn't exist
            $backupFolder = storage_path('app/backups');
            if (!file_exists($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            // Create timestamped backup filename
            $timestamp = date('Y-m-d_His');
            $backupZipName = 'folders_complete_backup_' . $timestamp . '.zip';
            $backupZipPath = $backupFolder . '/' . $backupZipName;

            // Initialize ZIP archive
            $zip = new ZipArchive;
            if ($zip->open($backupZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Could not create backup zip file');
            }

            // 1. Backup database table as JSON
            $folders = Folder::all()->toArray();
            $dbBackupJson = json_encode($folders, JSON_PRETTY_PRINT);
            $zip->addFromString('database/folders_table.json', $dbBackupJson);

            // Also create CSV for easy viewing
            if (!empty($folders)) {
                $csvContent = $this->arrayToCsv($folders);
                $zip->addFromString('database/folders_table.csv', $csvContent);
            }

            // 2. Backup all zip files from folder-zip directory
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

            // 3. Create a README file with backup information
            $readmeContent = "=== FOLDERS BACKUP ===\n";
            $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readmeContent .= "Total Folders in Database: " . count($folders) . "\n";
            $readmeContent .= "Total Zip Files Backed Up: " . $backupCount . "\n\n";
            $readmeContent .= "Structure:\n";
            $readmeContent .= "- database/folders_table.json - Database records in JSON format\n";
            $readmeContent .= "- database/folders_table.csv - Database records in CSV format\n";
            $readmeContent .= "- folder-zips/ - All folder zip files\n\n";
            $readmeContent .= "To restore, use the /api/backup/restore endpoint with this backup file.\n";

            $zip->addFromString('README.txt', $readmeContent);

            // Close the zip
            $zip->close();

            // Get file size for response
            $fileSize = filesize($backupZipPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return response()->json([
                'success' => true,
                'message' => 'Complete backup created successfully!',
                'backup_file' => $backupZipName,
                'backup_path' => $backupZipPath,
                'statistics' => [
                    'total_folders' => count($folders),
                    'total_zip_files' => $backupCount,
                    'backup_size_mb' => $fileSizeMB,
                    'backup_date' => date('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a specific backup file
     */
    public function downloadBackup($backupFileName)
    {
        try {
            $backupPath = storage_path('app/backups/' . $backupFileName);

            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }

            return response()->download($backupPath);

        } catch (Exception $e) {
            Log::error('Download backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to download backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all available backups
     */
    public function listBackups()
    {
        try {
            $backupFolder = storage_path('app/backups');

            if (!file_exists($backupFolder)) {
                return response()->json([
                    'success' => true,
                    'backups' => [],
                    'message' => 'No backups found'
                ], 200);
            }

            $backups = [];
            $files = scandir($backupFolder);

            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    $filePath = $backupFolder . '/' . $file;
                    $backups[] = [
                        'filename' => $file,
                        'size_mb' => round(filesize($filePath) / 1024 / 1024, 2),
                        'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'download_url' => url('/api/backup/download/' . $file)
                    ];
                }
            }

            // Sort by creation date, newest first
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'total_backups' => count($backups)
            ], 200);

        } catch (Exception $e) {
            Log::error('List backups error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore from a backup file
     */
    public function restoreBackup(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:zip'
            ]);

            $backupFile = $request->file('backup_file');
            $tempPath = $backupFile->getRealPath();

            // Open backup zip
            $zip = new ZipArchive;
            if ($zip->open($tempPath) !== TRUE) {
                throw new Exception('Could not open backup file');
            }

            // Extract database JSON
            $dbJsonContent = $zip->getFromName('database/folders_table.json');
            if ($dbJsonContent === false) {
                throw new Exception('Invalid backup file: missing database backup');
            }

            $foldersData = json_decode($dbJsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid backup file: corrupted database data');
            }

            // Create temp directory for extraction
            $tempDir = storage_path('app/temp/restore_' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // Extract all files
            $zip->extractTo($tempDir);
            $zip->close();

            // Restore database records
            DB::beginTransaction();
            try {
                // Optional: Clear existing data (comment out if you want to keep existing)
                // Folder::truncate();

                foreach ($foldersData as $folderData) {
                    // Remove auto-generated fields
                    unset($folderData['id']);

                    Folder::create($folderData);
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception('Database restore failed: ' . $e->getMessage());
            }

            // Restore zip files
            $sourceZipDir = $tempDir . '/folder-zips';
            $targetZipDir = storage_path('app/public/uploads/folder-zip');

            if (!file_exists($targetZipDir)) {
                mkdir($targetZipDir, 0777, true);
            }

            $restoredFiles = 0;
            if (file_exists($sourceZipDir)) {
                $files = scandir($sourceZipDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $source = $sourceZipDir . '/' . $file;
                        $target = $targetZipDir . '/' . $file;

                        if (copy($source, $target)) {
                            $restoredFiles++;
                        }
                    }
                }
            }

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully!',
                'statistics' => [
                    'folders_restored' => count($foldersData),
                    'files_restored' => $restoredFiles
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Restore error: ' . $e->getMessage());

            // Cleanup temp directory on error
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }

            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup($backupFileName)
    {
        try {
            $backupPath = storage_path('app/backups/' . $backupFileName);

            if (!file_exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }

            unlink($backupPath);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error('Delete backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Convert array to CSV string
     */
    private function arrayToCsv(array $data)
    {
        if (empty($data)) {
            return '';
        }

        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csvRow = [];
            foreach ($row as $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $csvRow[] = '"' . str_replace('"', '""', $value ?? '') . '"';
            }
            $csv .= implode(',', $csvRow) . "\n";
        }

        return $csv;
    }

    /**
     * Create a complete database backup (all tables)
     */
    public function backupDatabase()
    {
        try {
            $backupFolder = storage_path('app/backups');
            if (!file_exists($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            $timestamp = date('Y-m-d_His');
            $backupZipName = 'database_backup_' . $timestamp . '.zip';
            $backupZipPath = $backupFolder . '/' . $backupZipName;

            // Initialize ZIP archive
            $zip = new ZipArchive;
            if ($zip->open($backupZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Could not create backup zip file');
            }

            // Get all table names
            $tables = $this->getAllTables();
            $totalRecords = 0;
            $backupData = [];

            // Backup each table
            foreach ($tables as $table) {
                $tableData = DB::table($table)->get()->toArray();
                $backupData[$table] = $tableData;
                $totalRecords += count($tableData);

                // Save individual table as JSON
                $tableJson = json_encode($tableData, JSON_PRETTY_PRINT);
                $zip->addFromString("database/tables/{$table}.json", $tableJson);

                // Save individual table as CSV
                if (!empty($tableData)) {
                    $tableCsv = $this->arrayToCsv($tableData);
                    $zip->addFromString("database/tables/{$table}.csv", $tableCsv);
                }
            }

            // Create complete database dump as JSON
            $completeDump = json_encode($backupData, JSON_PRETTY_PRINT);
            $zip->addFromString('database/complete_database.json', $completeDump);

            // Try to create SQL dump using mysqldump
            $sqlDump = $this->createMySQLDump();
            if ($sqlDump) {
                $zip->addFromString('database/database_dump.sql', $sqlDump);
            }

            // Create README
            $readmeContent = "=== COMPLETE DATABASE BACKUP ===\n";
            $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readmeContent .= "Database Name: " . DB::connection()->getDatabaseName() . "\n";
            $readmeContent .= "Total Tables: " . count($tables) . "\n";
            $readmeContent .= "Total Records: " . $totalRecords . "\n\n";
            $readmeContent .= "Tables Backed Up:\n";
            foreach ($tables as $table) {
                $count = count($backupData[$table]);
                $readmeContent .= "  - {$table}: {$count} records\n";
            }
            $readmeContent .= "\nStructure:\n";
            $readmeContent .= "- database/complete_database.json - Complete database in JSON\n";
            $readmeContent .= "- database/database_dump.sql - MySQL dump (if available)\n";
            $readmeContent .= "- database/tables/*.json - Individual tables in JSON\n";
            $readmeContent .= "- database/tables/*.csv - Individual tables in CSV\n\n";
            $readmeContent .= "To restore, use the /api/backup/restore-database endpoint.\n";

            $zip->addFromString('README.txt', $readmeContent);
            $zip->close();

            $fileSize = filesize($backupZipPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return response()->json([
                'success' => true,
                'message' => 'Complete database backup created successfully!',
                'backup_file' => $backupZipName,
                'backup_path' => $backupZipPath,
                'statistics' => [
                    'total_tables' => count($tables),
                    'total_records' => $totalRecords,
                    'backup_size_mb' => $fileSizeMB,
                    'backup_date' => date('Y-m-d H:i:s'),
                    'tables' => array_map(function($table) use ($backupData) {
                        return [
                            'name' => $table,
                            'records' => count($backupData[$table])
                        ];
                    }, $tables)
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Database backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create COMPLETE backup (database + files + folders)
     */
    public function backupComplete()
    {
        try {
            $backupFolder = storage_path('app/backups');
            if (!file_exists($backupFolder)) {
                mkdir($backupFolder, 0777, true);
            }

            $timestamp = date('Y-m-d_His');
            $backupZipName = 'complete_backup_' . $timestamp . '.zip';
            $backupZipPath = $backupFolder . '/' . $backupZipName;

            $zip = new ZipArchive;
            if ($zip->open($backupZipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Could not create backup zip file');
            }

            // 1. Backup ALL database tables
            $tables = $this->getAllTables();
            $totalRecords = 0;
            $backupData = [];

            foreach ($tables as $table) {
                $tableData = DB::table($table)->get()->toArray();
                $backupData[$table] = $tableData;
                $totalRecords += count($tableData);

                $tableJson = json_encode($tableData, JSON_PRETTY_PRINT);
                $zip->addFromString("database/tables/{$table}.json", $tableJson);

                if (!empty($tableData)) {
                    $tableCsv = $this->arrayToCsv($tableData);
                    $zip->addFromString("database/tables/{$table}.csv", $tableCsv);
                }
            }

            // Complete database dump
            $completeDump = json_encode($backupData, JSON_PRETTY_PRINT);
            $zip->addFromString('database/complete_database.json', $completeDump);

            // SQL dump
            $sqlDump = $this->createMySQLDump();
            if ($sqlDump) {
                $zip->addFromString('database/database_dump.sql', $sqlDump);
            }

            // 2. Backup all folder zip files
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

            // Create comprehensive README
            $readmeContent = "=== COMPLETE SYSTEM BACKUP ===\n";
            $readmeContent .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
            $readmeContent .= "Database Name: " . DB::connection()->getDatabaseName() . "\n\n";
            $readmeContent .= "DATABASE:\n";
            $readmeContent .= "  Total Tables: " . count($tables) . "\n";
            $readmeContent .= "  Total Records: " . $totalRecords . "\n";
            $readmeContent .= "\nTables:\n";
            foreach ($tables as $table) {
                $count = count($backupData[$table]);
                $readmeContent .= "  - {$table}: {$count} records\n";
            }
            $readmeContent .= "\nFILES:\n";
            $readmeContent .= "  Folder Zips: {$backupCount}\n";
            $readmeContent .= "  Profile Images: {$profileCount}\n\n";
            $readmeContent .= "Structure:\n";
            $readmeContent .= "- database/ - Complete database backup\n";
            $readmeContent .= "- files/folder-zips/ - All folder zip files\n";
            $readmeContent .= "- files/profiles/ - Profile images\n\n";
            $readmeContent .= "To restore, use the /api/backup/restore-complete endpoint.\n";

            $zip->addFromString('README.txt', $readmeContent);
            $zip->close();

            $fileSize = filesize($backupZipPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return response()->json([
                'success' => true,
                'message' => 'Complete system backup created successfully!',
                'backup_file' => $backupZipName,
                'backup_path' => $backupZipPath,
                'statistics' => [
                    'total_tables' => count($tables),
                    'total_records' => $totalRecords,
                    'folder_zips' => $backupCount,
                    'profile_images' => $profileCount,
                    'backup_size_mb' => $fileSizeMB,
                    'backup_date' => date('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Complete backup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Complete backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreDatabase(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:zip'
            ]);

            $backupFile = $request->file('backup_file');
            $tempPath = $backupFile->getRealPath();

            $zip = new ZipArchive;
            if ($zip->open($tempPath) !== TRUE) {
                throw new Exception('Could not open backup file');
            }

            // Extract complete database JSON
            $dbJsonContent = $zip->getFromName('database/complete_database.json');
            if ($dbJsonContent === false) {
                throw new Exception('Invalid backup file: missing database backup');
            }

            $databaseData = json_decode($dbJsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid backup file: corrupted database data');
            }

            $zip->close();

            // Restore tables
            DB::beginTransaction();
            try {
                $restoredTables = 0;
                $restoredRecords = 0;

                foreach ($databaseData as $tableName => $tableData) {
                    // Truncate table before restore
                    DB::table($tableName)->truncate();

                    // Insert data
                    if (!empty($tableData)) {
                        // Convert stdClass objects to arrays
                        $tableData = json_decode(json_encode($tableData), true);

                        // Insert in chunks to avoid memory issues
                        $chunks = array_chunk($tableData, 100);
                        foreach ($chunks as $chunk) {
                            DB::table($tableName)->insert($chunk);
                        }
                    }

                    $restoredTables++;
                    $restoredRecords += count($tableData);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Database restored successfully!',
                    'statistics' => [
                        'tables_restored' => $restoredTables,
                        'records_restored' => $restoredRecords
                    ]
                ], 200);

            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception('Database restore failed: ' . $e->getMessage());
            }

        } catch (Exception $e) {
            Log::error('Database restore error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore complete backup (database + files)
     */
    public function restoreComplete(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:zip'
            ]);

            $backupFile = $request->file('backup_file');
            $tempPath = $backupFile->getRealPath();

            $zip = new ZipArchive;
            if ($zip->open($tempPath) !== TRUE) {
                throw new Exception('Could not open backup file');
            }

            // Create temp directory
            $tempDir = storage_path('app/temp/restore_complete_' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // Extract all files
            $zip->extractTo($tempDir);
            $zip->close();

            // 1. Restore database
            $dbJsonPath = $tempDir . '/database/complete_database.json';
            if (!file_exists($dbJsonPath)) {
                throw new Exception('Database backup not found in archive');
            }

            $databaseData = json_decode(file_get_contents($dbJsonPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Corrupted database data');
            }

            DB::beginTransaction();
            try {
                $restoredTables = 0;
                $restoredRecords = 0;

                foreach ($databaseData as $tableName => $tableData) {
                    DB::table($tableName)->truncate();

                    if (!empty($tableData)) {
                        $tableData = json_decode(json_encode($tableData), true);
                        $chunks = array_chunk($tableData, 100);
                        foreach ($chunks as $chunk) {
                            DB::table($tableName)->insert($chunk);
                        }
                    }

                    $restoredTables++;
                    $restoredRecords += count($tableData);
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception('Database restore failed: ' . $e->getMessage());
            }

            // 2. Restore folder zips
            $sourceZipDir = $tempDir . '/files/folder-zips';
            $targetZipDir = storage_path('app/public/uploads/folder-zip');
            $restoredFolderZips = 0;

            if (file_exists($sourceZipDir) && !file_exists($targetZipDir)) {
                mkdir($targetZipDir, 0777, true);
            }

            if (file_exists($sourceZipDir)) {
                $files = scandir($sourceZipDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $source = $sourceZipDir . '/' . $file;
                        $target = $targetZipDir . '/' . $file;
                        if (copy($source, $target)) {
                            $restoredFolderZips++;
                        }
                    }
                }
            }

            // 3. Restore profile images
            $sourceProfileDir = $tempDir . '/files/profiles';
            $targetProfileDir = storage_path('app/public/profiles');
            $restoredProfiles = 0;

            if (file_exists($sourceProfileDir) && !file_exists($targetProfileDir)) {
                mkdir($targetProfileDir, 0777, true);
            }

            if (file_exists($sourceProfileDir)) {
                $files = scandir($sourceProfileDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $source = $sourceProfileDir . '/' . $file;
                        $target = $targetProfileDir . '/' . $file;
                        if (copy($source, $target)) {
                            $restoredProfiles++;
                        }
                    }
                }
            }

            // Clean up
            $this->deleteDirectory($tempDir);

            return response()->json([
                'success' => true,
                'message' => 'Complete system restored successfully!',
                'statistics' => [
                    'tables_restored' => $restoredTables,
                    'records_restored' => $restoredRecords,
                    'folder_zips_restored' => $restoredFolderZips,
                    'profiles_restored' => $restoredProfiles
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Complete restore error: ' . $e->getMessage());

            if (isset($tempDir) && file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }

            return response()->json([
                'success' => false,
                'message' => 'Complete restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all table names from database
     */
    private function getAllTables()
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . DB::connection()->getDatabaseName();

        return array_map(function($table) use ($dbName) {
            return $table->$dbName;
        }, $tables);
    }

    /**
     * Create MySQL dump using mysqldump command
     */
    private function createMySQLDump()
    {
        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPass = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');
            $dbPort = env('DB_PORT', '3306');

            $dumpFile = storage_path('app/temp/dump_' . time() . '.sql');

            // Try to find mysqldump
            $mysqldumpPath = 'mysqldump'; // Assumes mysqldump is in PATH

            // Build command
            $command = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
                $mysqldumpPath,
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($dumpFile)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($dumpFile)) {
                $content = file_get_contents($dumpFile);
                unlink($dumpFile);
                return $content;
            }

            return null;
        } catch (Exception $e) {
            Log::warning('mysqldump not available: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper: Recursively delete a directory
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
