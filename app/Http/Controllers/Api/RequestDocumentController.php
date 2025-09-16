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
}
