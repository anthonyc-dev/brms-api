<?php

namespace App\Http\Controllers\Services;

use App\Models\RequestDocument;
use App\Notifications\DocumentRequestCreated;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Exception;

class ReqService
{
    public function store(array $data, $user)
    {
        try {
            // Validate input, but do not require user_id from client
            $validator = validator($data, [
                'document_type'    => 'required|string|max:255',
                'full_name'        => 'required|string|max:255',
                'address'          => 'required|string|max:255',
                'contact_number'   => 'required|string|max:50',
                'email'            => 'required|email|max:255',
                'purpose'          => 'required|string',
                'reference_number' => 'nullable|string|max:255|unique:document_requests,reference_number',
                'status'           => 'nullable|in:pending,ready,reject'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            // Always use authenticated user's id
            $validatedData['user_id'] = $user->id;

            // Set default status if not provided
            if (!isset($validatedData['status'])) {
                $validatedData['status'] = 'pending';
            }

            $requestDocument = RequestDocument::create($validatedData);

            // Send email notification to the user
            try {
                $user->notify(new DocumentRequestCreated($requestDocument));
            } catch (Exception $e) {
                // Log the error but don't fail the request creation
                Log::warning('Failed to send email notification: ' . $e->getMessage());
            }

            return $requestDocument;
        } catch (ValidationException $e) {
            // Rethrow validation exceptions for controller to handle
            throw $e;
        } catch (Exception $e) {
            // Wrap other exceptions in a ValidationException for consistency
            throw ValidationException::withMessages([
                'error' => ['Failed to create request document: ' . $e->getMessage()],
            ]);
        }
    }

    public function update($id, array $data, $user)
    {
        try {
            // Find the request document by ID
            $requestDocument = RequestDocument::findOrFail($id);

            // Optionally, ensure the user is allowed to update this document
            // Uncomment below to restrict updates to the owner
            // if ($requestDocument->user_id !== $user->id) {
            //     throw ValidationException::withMessages([
            //         'error' => ['You are not authorized to update this document.'],
            //     ]);
            // }

            $validator = validator($data, [
                'document_type'    => 'sometimes|required|string|max:255',
                'full_name'        => 'sometimes|required|string|max:255',
                'address'          => 'sometimes|required|string|max:255',
                'contact_number'   => 'sometimes|required|string|max:50',
                'email'            => 'sometimes|required|email|max:255',
                'purpose'          => 'sometimes|required|string',
                'reference_number' => 'sometimes|nullable|string|max:255|unique:document_requests,reference_number,' . $requestDocument->id,
                'status'           => 'sometimes|in:pending,ready,reject',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();

            // Prevent user_id from being updated
            unset($validatedData['user_id']);

            $requestDocument->update($validatedData);

            return $requestDocument;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'error' => ['Failed to update request document: ' . $e->getMessage()],
            ]);
        }
    }

    public function destroyById($id, $user)
   {
        $query = RequestDocument::where('id', $id)
                    ->where('user_id', $user->id);

        $document = $query->first();

        if (!$document) {
            return false; // Not found or not owned by this user
        }

        $document->delete();

        return true;
    }

}
