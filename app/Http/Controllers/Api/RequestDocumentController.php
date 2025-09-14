<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RequestDocument;
use Illuminate\Validation\ValidationException;
class RequestDocumentController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Validate input, but do not require user_id from client
            $validatedData = $request->validate([
                'document_type'   => 'required|string|max:255',
                'full_name'       => 'required|string|max:255',
                'address'         => 'required|string|max:255',
                'contact_number'  => 'required|string|max:50',
                'email'           => 'required|email|max:255',
                'purpose'         => 'required|string',
                'reference_number'=> 'nullable|string|max:255|unique:document_requests,reference_number',
                'status'          => 'nullable|in:pending,processing,ready,claimed',
            ]);

            // Always use authenticated user's id
            $validatedData['user_id'] = $user->id;

            // Set default status if not provided
            if (!isset($validatedData['status'])) {
                $validatedData['status'] = 'pending';
            }

            $requestDocument = RequestDocument::create($validatedData);

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
