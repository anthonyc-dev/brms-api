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
            return response()->json(['message' => 'Folder not found'], 404);
        }
    
        // Validate
        $validated = $request->validate([
            'folder_name'      => 'sometimes|required|string|max:255|unique:folders,folder_name,' . $id,
            'description'      => 'nullable|string',
            'files.*'          => 'nullable|file|max:10240',
            'existing_files'   => 'nullable|array',    // <-- FIX: array instead of JSON string
            'existing_files.*' => 'string'
        ]);
    
        // Update text fields only
        if (isset($validated['folder_name'])) {
            $folder->folder_name = $validated['folder_name'];
        }
    
        if (isset($validated['description'])) {
            $folder->description = $validated['description'];
        }
    
        $hasNewFiles = $request->hasFile('files');
        $hasExistingFilesUpdate = isset($validated['existing_files']);
    
        // If files are changed, we regenerate the ZIP
        if ($hasNewFiles || $hasExistingFilesUpdate) {
    
            // Convert existing_files to array safely
            $existingFilesToKeep = $validated['existing_files'] ?? [];
    
            // Current files in DB
            $currentFiles = is_array($folder->original_files)
                ? $folder->original_files
                : [];
    
            // New uploaded files
            $newFiles = [];
            $newFileObjects = [];
    
            if ($hasNewFiles) {
                foreach ($request->file('files') as $file) {
                    $original = $file->getClientOriginalName();
                    $newFiles[] = $original;
                    $newFileObjects[$original] = $file;
                }
            }
    
            // Merge + remove duplicates
            $allFiles = array_values(array_unique(array_merge($existingFilesToKeep, $newFiles)));
    
            // Paths
            $uploadPath = storage_path('app/public/uploads/folder-zip');
    
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
    
            // Delete old ZIP
            $oldZipPath = $uploadPath . '/' . $folder->zip_name;
            if (file_exists($oldZipPath)) {
                unlink($oldZipPath);
            }
    
            // Generate new ZIP name
            $sanitizedName = Str::slug($folder->folder_name);
            $newZipFileName = 'folder_' . $sanitizedName . '_' . time() . '_' . uniqid() . '.zip';
            $newZipPath = $uploadPath . '/' . $newZipFileName;
    
            // Create new ZIP
            $zip = new ZipArchive;
            if ($zip->open($newZipPath, ZipArchive::CREATE) === TRUE) {
    
                // Re-add ONLY kept existing files
                $oldZip = new ZipArchive;
                if ($oldZip->open($oldZipPath) === TRUE) {
    
                    foreach ($existingFilesToKeep as $fileName) {
                        $index = $oldZip->locateName($fileName);
                        if ($index !== false) {
                            $content = $oldZip->getFromIndex($index);
                            if ($content !== false) {
                                $zip->addFromString($fileName, $content);
                            }
                        }
                    }
    
                    $oldZip->close();
                }
    
                // Add NEW files
                foreach ($newFileObjects as $fileName => $file) {
                    $zip->addFile($file->getRealPath(), $fileName);
                }
    
                $zip->close();
            }
    
            // Save zip + list
            $folder->zip_name = $newZipFileName;
            $folder->original_files = $allFiles;
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