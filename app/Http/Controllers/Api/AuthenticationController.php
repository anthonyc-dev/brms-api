<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Http\Controllers\Services\ResidentService;
use App\Http\Controllers\Services\AuthService;

class AuthenticationController extends Controller
{
    protected $residentService;
    protected $authService;
    public function __construct(ResidentService $residentService, AuthService $authService)
    {
        $this->residentService = $residentService;
        $this->authService = $authService;
    }
    /**
     * Register a new account.
     */
    public function register(Request $request)
    {
        try {

            $user = $this->authService->register($request->all());

            // Add user_id to the request data for resident creation
            $residentData = $request->all();
            $residentData['user_id'] = $user->id;
            
            $resident = $this->residentService->registerUser($residentData);
        
            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'Successfully registered',
                'data_user_list' => $user,
                'resident'  => $resident,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Registration failed',
            ], 500);
        }
    }
    

    /**
     * Login and return auth token.
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'Unauthorized',
                ], 401);
            }

            /** @var \App\Models\User $user */
            $user = Auth::user();
            $token = $user->createToken(name: 'authToken')->plainTextToken;

            // Get resident status
            $residentStatus = $user->resident?->status;

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Login successful',
                'user_info'     => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'profile'    => $user->profile,
                    'profile_url' => $user->profile_url,
                    'status'     => $residentStatus,
                ],
                'token'       => $token,
                'token_type'  => 'Bearer',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Login failed',
            ], 500);
        }
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'User not authenticated',
                ], 401);
            }

            // Use AuthService to validate and update password (expects user id and request data)
            $authService = app(AuthService::class);
            $authService->updatePassword($user->id, $request->all());

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Password updated successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Password Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to update password',
            ], 500);
        }
    }

    /**
     * Get list of users (paginated) — protected route.
     */
    public function userInfo()
    {
        try {
            $users = User::latest()->paginate(10);

            return response()->json([
                'response_code'  => 200,
                'status'         => 'success',
                'message'        => 'Fetched user list successfully',
                'data_user_list' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('User List Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to fetch user list',
            ], 500);
        }
    }

    /**
     * Logout user and revoke tokens — protected route.
     */
    public function logOut(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                $user->tokens()->delete();

                return response()->json([
                    'response_code' => 200,
                    'status'        => 'success',
                    'message'       => 'Successfully logged out',
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status'        => 'error',
                'message'       => 'User not authenticated',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'An error occurred during logout',
            ], 500);
        }
    }

    public function updateProfile(Request $request, $id)
    {
        try {
            // Log incoming request details
            Log::info('Update profile request received', [
                'user_id' => $id,
                'has_file' => $request->hasFile('profile'),
                'all_files' => $request->allFiles(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'all_keys' => array_keys($request->all()),
            ]);

            $authenticatedUser = $request->user();

            if (!$authenticatedUser) {
                return response()->json([
                    'response_code' => 401,
                    'status'        => 'error',
                    'message'       => 'User not authenticated',
                ], 401);
            }

            // Find user by ID
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'response_code' => 404,
                    'status'        => 'error',
                    'message'       => 'User not found',
                ], 404);
            }

            // Validate request data
            $validated = $request->validate([
                'name'    => 'sometimes|string|min:4|max:255',
                'email'   => 'sometimes|email|max:255|unique:users,email,' . $id,
                'profile' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            Log::info('Validation passed', ['validated_keys' => array_keys($validated)]);

            // Track if any changes were made
            $updated = false;

            // Update name if provided
            if ($request->filled('name') && isset($validated['name'])) {
                $user->name = $validated['name'];
                $updated = true;
                Log::info('Name updated', ['new_name' => $validated['name']]);
            }

            // Update email if provided
            if ($request->filled('email') && isset($validated['email'])) {
                $user->email = $validated['email'];
                $updated = true;
                Log::info('Email updated', ['new_email' => $validated['email']]);
            }

            // Handle profile image upload
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');

                Log::info('File detected in request', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'is_valid' => $file->isValid(),
                    'error' => $file->getError(),
                    'error_message' => $file->getErrorMessage(),
                ]);

                if (!$file->isValid()) {
                    Log::error('Invalid file upload', [
                        'error_code' => $file->getError(),
                        'error_message' => $file->getErrorMessage(),
                    ]);
                    return response()->json([
                        'response_code' => 422,
                        'status'        => 'error',
                        'message'       => 'Invalid file uploaded: ' . $file->getErrorMessage(),
                    ], 422);
                }

                try {
                    // Ensure profiles directory exists
                    if (!Storage::disk('public')->exists('profiles')) {
                        Storage::disk('public')->makeDirectory('profiles');
                        Log::info('Created profiles directory');
                    }

                    // Delete old profile image if exists
                    if ($user->profile) {
                        $oldPath = $user->profile;
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                            Log::info('Deleted old profile image', ['path' => $oldPath]);
                        }
                    }

                    // Generate unique filename
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    // Store the file
                    $profilePath = $file->storeAs('profiles', $filename, 'public');

                    if (!$profilePath) {
                        throw new \Exception('storeAs returned false');
                    }

                    // Verify file exists
                    $fullPath = Storage::disk('public')->path($profilePath);
                    $exists = Storage::disk('public')->exists($profilePath);
                    $fileExists = file_exists($fullPath);

                    Log::info('File storage result', [
                        'profile_path' => $profilePath,
                        'full_path' => $fullPath,
                        'storage_exists' => $exists,
                        'file_exists' => $fileExists,
                        'file_size' => $exists ? Storage::disk('public')->size($profilePath) : 0,
                    ]);

                    if (!$exists) {
                        throw new \Exception('File not found in storage after save');
                    }

                    // Update user profile path
                    $user->profile = $profilePath;
                    $updated = true;

                    Log::info('Profile path set on user model', [
                        'profile' => $user->profile,
                        'user_id' => $user->id,
                    ]);

                } catch (\Exception $e) {
                    Log::error('File storage exception', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json([
                        'response_code' => 500,
                        'status'        => 'error',
                        'message'       => 'Failed to save profile image: ' . $e->getMessage(),
                    ], 500);
                }
            } else {
                Log::info('No file in request', [
                    'has_profile_key' => $request->has('profile'),
                    'has_file' => $request->hasFile('profile'),
                ]);
            }

            // Save the user model
            if ($updated) {
                $saved = $user->save();
                Log::info('User model save result', [
                    'saved' => $saved,
                    'user_profile' => $user->profile,
                    'user_id' => $user->id,
                ]);

                // Refresh from database to confirm
                $user->refresh();
                Log::info('User after refresh', [
                    'profile' => $user->profile,
                ]);
            }

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Profile updated successfully',
                'user_info'     => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'profile'    => $user->profile,
                    'profile_url' => $user->profile_url,
                ],
            ]);
        } catch (ValidationException $e) {
            Log::error('Update Profile Validation Error', ['errors' => $e->errors()]);
            return response()->json([
                'response_code' => 422,
                'status'        => 'error',
                'message'       => 'Validation failed',
                'errors'        => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Profile Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getProfileById($id)
    {
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'response_code' => 404,
                    'status'        => 'error',
                    'message'       => 'User not found',
                ], 404);
            }

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Profile retrieved successfully',
                'user_info'     => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'profile'     => $user->profile,
                    'profile_url' => $user->profile_url,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Profile By Id Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to get profile',
            ], 500);
        }
    }

}