<?php

namespace App\Http\Controllers\Services;

use App\Models\Folder;
use Illuminate\Http\Request;
use ZipArchive;
use Exception;

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

        // Generate unique zip filename
        $zipFileName = 'folder_' . $request->description . time() . '.zip';
        $zipPath = $uploadPath . '/' . $zipFileName;

        // Create zip archive
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $originalFiles = [];
            foreach ($request->file('folder') as $file) {
                $zip->addFile($file->getRealPath(), $file->getClientOriginalName());
                $originalFiles[] = $file->getClientOriginalName();
            }
            $zip->close();
        } else {
            throw new Exception('Could not create zip file');
        }

        // Save folder metadata to database
        $folder = Folder::create([
            'folder_name' => $request->folder_name,
            'zip_name' => $zipFileName,
            'original_files' => $originalFiles,
            'description' => $request->description,
            'date_created' => $request->date_created,
        ]);

        return [
            'folder' => $folder,
            'download_url' => url("/api/folders/{$zipFileName}")
        ];
    }

    

    /**
     * Get all folders
     */
    public function getAllFolders()
    {
        return Folder::all();
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
            'folder_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date_created' => 'nullable|date',
        ]);

        // Update folder with validated data
        $folder->update($validated);

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
}
