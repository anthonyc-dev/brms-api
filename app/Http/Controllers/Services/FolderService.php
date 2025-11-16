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
    
        // Validate inside the service
        $validated = $request->validate([
            'folder_name' => 'sometimes|required|string|max:255|unique:folders,folder_name,' . $id,
            'description' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240', // Max 10MB per file
            'existing_files' => 'nullable|string', // JSON string of files to keep
        ]);
    
        // Update basic fields
        if (isset($validated['folder_name'])) {
            $folder->folder_name = $validated['folder_name'];
        }
        
        if (isset($validated['description'])) {
            $folder->description = $validated['description'];
        }
    
        // Handle file updates
        if ($request->hasFile('files')) {
            // Get existing files to keep
            $existingFilesToKeep = [];
            if ($request->has('existing_files')) {
                $existingFilesToKeep = json_decode($request->input('existing_files'), true) ?? [];
            }
    
            // Path to the folder's directory
            $folderPath = storage_path('app/public/folders/' . $folder->folder_name);
            
            // Get current files
            $currentFiles = $folder->original_files ?? [];
            
            // Delete files that are not in the "keep" list
            foreach ($currentFiles as $currentFile) {
                if (!in_array($currentFile, $existingFilesToKeep)) {
                    $filePath = $folderPath . '/' . basename($currentFile);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
    
            // Add new files
            $newFiles = [];
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $file->storeAs('public/folders/' . $folder->folder_name, $originalName);
                $newFiles[] = $originalName;
            }
    
            // Combine existing files to keep + new files
            $allFiles = array_merge($existingFilesToKeep, $newFiles);
            $folder->original_files = json_encode($allFiles);
    
            // Recreate the ZIP file with all current files
            $zipPath = storage_path('app/public/folders/' . $folder->folder_name . '.zip');
            if (file_exists($zipPath)) {
                unlink($zipPath); // Delete old ZIP
            }
    
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($allFiles as $file) {
                    $filePath = $folderPath . '/' . basename($file);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, basename($file));
                    }
                }
                $zip->close();
            }
    
            $folder->zip_name = $folder->folder_name . '.zip';
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