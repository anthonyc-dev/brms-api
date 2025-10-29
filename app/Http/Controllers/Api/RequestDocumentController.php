<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\ReqService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RequestDocumentController extends Controller
{
    protected $reqService;

    public function __construct(ReqService $reqService)
    {
        $this->reqService = $reqService;
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Delegate validation and creation to the service
            $requestDocument = $this->reqService->store($request->all(), $user);

            return response()->json([
                'response_code' => 201,
                'status' => 'success',
                'message' => 'Request document created successfully',
                'data' => $requestDocument
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to create request document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add update method for document request
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Delegate validation and update to the service
            $updatedRequestDocument = $this->reqService->update($id, $request->all(), $user);

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Request document updated successfully',
                'data' => $updatedRequestDocument
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to update request document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    // Add show method for fetching request documents by user_id
    public function getDocumentsById($userId)
    {
        try {
            $requestDocuments = \App\Models\RequestDocument::where('user_id', $userId)->orderBy('created_at', 'desc')->get();

            if ($requestDocuments->isEmpty()) {
                return response()->json([
                    'response_code' => 404,
                    'status' => 'error',
                    'message' => 'No request documents found for this user',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Request documents retrieved successfully',
                'data' => $requestDocuments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to fetch request documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Delegate deletion to service
            $deleted = $this->reqService->destroyById($id, $user);

            if (!$deleted) {
                return response()->json([
                    'response_code' => 404,
                    'status' => 'error',
                    'message' => 'Request document not found or unauthorized to delete',
                ], 404);
            }

            return response()->json([
                'response_code' => 200,
                'status' => 'success',
                'message' => 'Request document deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'response_code' => 500,
                'status' => 'error',
                'message' => 'Failed to delete request document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    

}


