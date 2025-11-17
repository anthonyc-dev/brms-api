<?php

namespace App\Http\Controllers\Services;

use App\Models\Folder;
use Illuminate\Http\Request;
use ZipArchive;
use Exception;
use Illuminate\Support\Str;

class FolderService
{
    /**
     * Create a new folder with uploaded files
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'folder_name' => 'required|string|max:255|unique:folders,folder_name',
            'description' => 'nullable|string',
            'date_created' => 'nullable|date',
            'folder' => 'required|array',
            'folder.*' => 'file|max:10240', // 10MB per file
        ]);

        // Ensure the upload directory exists
        $uploadPath = storage_path('app/public/uploads/folder-zip');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Generate safe unique zip filename
        $sanitizedName = Str::slug($request->folder_name);
        $zipFileName = 'folder_' . $sanitizedName . '_' . time() . '_' . uniqid() . '.zip';
        $zipPath = $uploadPath . '/' . $zipFileName;

        // Create zip archive
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $originalFiles = [];
            foreach ($request->file('folder') as $file) {
                $originalFileName = $file->getClientOriginalName();
                $zip->addFile($file->getRealPath(), $originalFileName);
                $originalFiles[] = $originalFileName;
            }
            $zip->close();
        } else {
            throw new Exception('Could not create zip file');
        }

        // Save folder metadata to database
        $folder = Folder::create([
            'folder_name' => $request->folder_name,
            'zip_name' => $zipFileName,
            'original_files' => $originalFiles, // Laravel will auto-cast to JSON
            'description' => $request->description,
            'date_created' => $request->date_created ?? now()->toDateString(),
        ]);

        return [
            'folder' => $folder,
            'download_url' => url("storage/uploads/folder-zip/{$zipFileName}")
        ];
    }

    /**
     * Get all folders
     */
    public function getAllFolders()
    {
        return Folder::orderBy('created_at', 'desc')->get();
    }

    /**
     * Get folder by ID
     */
    public function getFolderById($id)
    {
        return Folder::find($id);
    }

    /**
     * Get folder by zip name
     */
    public function getFolderByZipName($zipName)
    {
        return Folder::where('zip_name', $zipName)->first();
    }

    /**
     * Update folder
     */
    public function updateFolder($id, Request $request)
    {
        $folder = Folder::find($id);
        if (!$folder) {
            return null;
        }
    
        // Validate request
        $validated = $request->validate([
            'folder_name' => 'sometimes|required|string|max:255|unique:folders,folder_name,' . $id,
            'description' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240',
            'existing_files' => 'nullable|string', // JSON string
        ]);
    
        $oldFolderName = $folder->folder_name;
    
        // Update basic fields
        if (isset($validated['folder_name'])) {
            $folder->folder_name = $validated['folder_name'];
        }
        if (isset($validated['description'])) {
            $folder->description = $validated['description'];
        }
    
        $hasNewFiles = $request->hasFile('files');
        $hasExistingFilesUpdate = $request->has('existing_files');
    
        if ($hasNewFiles || $hasExistingFilesUpdate) {
    
            // Decode existing_files safely
            $existingFilesToKeep = [];
            if ($request->has('existing_files')) {
                $decoded = json_decode($request->input('existing_files'), true);
                $existingFilesToKeep = is_array($decoded) ? $decoded : [];
            }
    
            // Normalize original_files to array (FIX)
            $rawFiles = $folder->original_files;
    
            if (is_string($rawFiles)) {
                $currentFiles = json_decode($rawFiles, true) ?? [];
            } elseif (is_array($rawFiles)) {
                $currentFiles = $rawFiles;
            } else {
                $currentFiles = [];
            }
    
            // Paths
            $oldFolderPath = storage_path('app/public/folders/' . $oldFolderName);
            $newFolderPath = storage_path('app/public/folders/' . $folder->folder_name);
    
            // If folder name changed, rename physical folder
            if ($oldFolderName !== $folder->folder_name && file_exists($oldFolderPath)) {
                rename($oldFolderPath, $newFolderPath);
            }
    
            // Ensure the new folder exists
            if (!file_exists($newFolderPath)) {
                mkdir($newFolderPath, 0755, true);
            }
    
            // Delete files not kept
            foreach ($currentFiles as $currentFile) {
                if (!in_array($currentFile, $existingFilesToKeep)) {
                    $filePath = $newFolderPath . '/' . basename($currentFile);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
    
            // Upload new files
            $newFiles = [];
            if ($hasNewFiles) {
                foreach ($request->file('files') as $file) {
                    $name = $file->getClientOriginalName();
                    $file->storeAs('public/folders/' . $folder->folder_name, $name);
                    $newFiles[] = $name;
                }
            }
    
            // Merge kept + new files
            $allFiles = array_values(array_unique(array_merge($existingFilesToKeep, $newFiles)));
            $folder->original_files = json_encode($allFiles);
    
            // Delete old ZIP if folder was renamed
            if ($oldFolderName !== $folder->folder_name) {
                $oldZip = storage_path('app/public/folders/' . $oldFolderName . '.zip');
                if (file_exists($oldZip)) unlink($oldZip);
            }
    
            // Remove existing ZIP
            $zipPath = storage_path('app/public/folders/' . $folder->folder_name . '.zip');
            if (file_exists($zipPath)) unlink($zipPath);
    
            // Create ZIP if there are files
            if (count($allFiles) > 0) {
                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                    foreach ($allFiles as $file) {
                        $filePath = $newFolderPath . '/' . basename($file);
                        if (file_exists($filePath)) {
                            $zip->addFile($filePath, basename($file));
                        }
                    }
                    $zip->close();
                }
                $folder->zip_name = $folder->folder_name . '.zip';
            } else {
                $folder->zip_name = null;
            }
        }
    
        $folder->save();
    
        return $folder;
    }
    

    /**
     * Delete folder and its zip file
     */
    public function deleteFolder($id)
    {
        $folder = Folder::find($id);
        if (!$folder) {
            return false;
        }

        // Delete the zip file from storage
        $zipPath = storage_path("app/public/uploads/folder-zip/{$folder->zip_name}");
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Delete from database
        $folder->delete();
        return true;
    }

    /**
     * Get zip file path for download
     */
    public function getZipFilePath($zipName)
    {
        $filePath = storage_path("app/public/uploads/folder-zip/{$zipName}");
        
        if (!file_exists($filePath)) {
            return null;
        }

        return $filePath;
    }

    /**
     * Check if zip file exists
     */
    public function zipFileExists($zipName)
    {
        $filePath = storage_path("app/public/uploads/folder-zip/{$zipName}");
        return file_exists($filePath);
    }

    /**
 * Download selected files from a folder as ZIP
 */
public function downloadSelectedFiles($id, $fileNames)
{
    $folder = Folder::find($id);
    if (!$folder) {
        return null;
    }

    // Ensure the temp directory exists
    $tempPath = storage_path('app/public/uploads/temp');
    if (!file_exists($tempPath)) {
        mkdir($tempPath, 0755, true);
    }

    // Get the original ZIP path
    $originalZipPath = storage_path("app/public/uploads/folder-zip/{$folder->zip_name}");
    
    if (!file_exists($originalZipPath)) {
        throw new Exception('Original ZIP file not found');
    }

    // Generate unique temp zip filename
    $sanitizedName = Str::slug($folder->folder_name);
    $tempZipFileName = 'selected_' . $sanitizedName . '_' . time() . '_' . uniqid() . '.zip';
    $tempZipPath = $tempPath . '/' . $tempZipFileName;

    // Open original ZIP
    $originalZip = new ZipArchive;
    if ($originalZip->open($originalZipPath) !== TRUE) {
        throw new Exception('Could not open original ZIP file');
    }

    // Create new ZIP with selected files
    $newZip = new ZipArchive;
    if ($newZip->open($tempZipPath, ZipArchive::CREATE) === TRUE) {
        // Add each selected file from original ZIP
        foreach ($fileNames as $fileName) {
            // Get file index in original ZIP
            $index = $originalZip->locateName($fileName);
            
            if ($index !== false) {
                // Get file content from original ZIP
                $fileContent = $originalZip->getFromIndex($index);
                
                if ($fileContent !== false) {
                    // Add to new ZIP
                    $newZip->addFromString($fileName, $fileContent);
                }
            }
        }
        $newZip->close();
    } else {
        $originalZip->close();
        throw new Exception('Could not create new ZIP file');
    }
    
    $originalZip->close();

    return [
        'zip_path' => $tempZipPath,
        'zip_name' => $tempZipFileName
    ];
}
}