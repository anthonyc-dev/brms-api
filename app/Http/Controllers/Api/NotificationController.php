<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RequestDocument;
use App\Notifications\DocumentRequestCreated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    /**
     * Send a test email notification to the authenticated user
     */
    public function sendTestEmail(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Create a dummy request document for testing
            $dummyRequest = new RequestDocument([
                'document_type' => 'Test Document',
                'full_name' => $user->name,
                'reference_number' => 'TEST-' . time(),
                'status' => 'pending',
                'purpose' => 'This is a test email notification'
            ]);
            $dummyRequest->id = 0;

            // Send notification
            $user->notify(new DocumentRequestCreated($dummyRequest));

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $user->email
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification for a specific document request
     */
    public function sendDocumentRequestNotification(Request $request, $requestId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Find the document request
            $documentRequest = RequestDocument::find($requestId);

            if (!$documentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document request not found'
                ], 404);
            }

            // Check if user owns this request
            if ($documentRequest->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this request'
                ], 403);
            }

            // Send notification
            $user->notify(new DocumentRequestCreated($documentRequest));

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully to ' . $user->email
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification to a specific email address
     */
    public function sendToEmail(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'email' => 'required|email',
                'request_id' => 'required|integer|exists:request_documents,id'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $email = $request->input('email');
            $requestId = $request->input('request_id');

            // Find the document request
            $documentRequest = RequestDocument::find($requestId);

            // Send notification to the specified email using anonymous notifiable
            Notification::route('mail', $email)
                ->notify(new DocumentRequestCreated($documentRequest));

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully to ' . $email
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }
}
