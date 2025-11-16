<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\FolderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FolderController extends Controller
{
    protected $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Store new folder with uploaded files
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $result = $this->folderService->createFolder($request);
            
            return response()->json([
                'message' => 'Folder uploaded, zipped, and stored in DB successfully',
                'folder' => $result['folder'],
                'download_url' => $result['download_url']
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download zipped folder
     */
    public function download($zipName)
    {
        // Get the full path of the zip file
        $filePath = $this->folderService->getZipFilePath($zipName);
    
        // Check if file exists
        if (!$filePath || !file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }
    
        // Return the file as a download response
        return response()->download($filePath, $zipName);
    }    

    /**
     * Get all folders
     */
    public function index()
    {
        $folders = $this->folderService->getAllFolders();
        return response()->json($folders);
    }

    /**
     * Get folder by ID
     */
    public function show($id)
    {
        Log::info("Looking for folder id: " . $id);
        $folder = $this->folderService->getFolderById($id);
        if (!$folder) {
            return response()->json(['error' => 'Folder not found'], 404);
        }
        return response()->json($folder);
    }

    

     /**
     * Update folder metadata
     */
    public function update(Request $request, $id)
    {
        try {
            // Call the service method
            $folder = $this->folderService->updateFolder($id, $request);
    
            if (!$folder) {
                return response()->json(['error' => 'Folder not found'], 404);
            }
    
            return response()->json([
                'message' => 'Folder updated successfully',
                'folder' => $folder
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update folder',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete folder
     */
    public function destroy($id)
    {
        $deleted = $this->folderService->deleteFolder($id);
        if (!$deleted) {
            return response()->json(['error' => 'Folder not found'], 404);
        }
        return response()->json(['message' => 'Folder deleted successfully']);
    }

    /**
 * Download selected files from a folder
 */
public function downloadSelected(Request $request, $id)
{
    try {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|string',
        ]);

        $result = $this->folderService->downloadSelectedFiles($id, $request->files);
        
        if (!$result) {
            return response()->json(['error' => 'Folder not found'], 404);
        }

        // Return the file as a download response
        $response = response()->download($result['zip_path'], $result['zip_name']);
        
        // Delete the temp file after sending
        $response->deleteFileAfterSend(true);
        
        return $response;
    } catch (\Exception $e) {
        Log::error('Download selected files error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


/**
 * Download a single file from a folder's zip archive.
 */
public function downloadSingle(Request $request, $id)
{
    try {
        $request->validate([
            'file' => 'required|string'
        ]);

        $folder = $this->folderService->getFolderById($id);
        if (!$folder) {
            return response()->json(['error' => 'Folder not found'], 404);
        }

        // Get path to the zip file
        $zipPath = storage_path("app/public/uploads/folder-zip/{$folder->zip_name}");
        if (!file_exists($zipPath)) {
            return response()->json(['error' => 'Folder zip file not found'], 404);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return response()->json(['error' => 'Could not open zip file'], 500);
        }

        $fileName = $request->file;
        $index = $zip->locateName($fileName);

        if ($index === false) {
            $zip->close();
            return response()->json(['error' => 'Requested file not found in zip'], 404);
        }

        $fileContent = $zip->getFromIndex($index);
        $zip->close();

        if ($fileContent === false) {
            return response()->json(['error' => 'Could not extract file from zip'], 500);
        }

        // Create a temporary file for response
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'single_file_');
        file_put_contents($tmpFilePath, $fileContent);

        return response()->download($tmpFilePath, $fileName)->deleteFileAfterSend(true);

    } catch (\Exception $e) {
        Log::error('Download single file error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


}
