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
        
        // Call the service method
        $folder = $this->folderService->updateFolder($id, $request);

        if (!$folder) {
            return response()->json(['error' => 'Folder not found'], 404);
        }

        return response()->json([
            'message' => 'Folder updated successfully',
            'folder' => $folder
        ]);
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

}
