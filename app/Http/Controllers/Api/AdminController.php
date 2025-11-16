<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Services\AdminService;
use App\Models\Admin;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Admin Login (Sanctum).
     */
    public function login(Request $request)
    {
        try {
            $result = $this->adminService->login($request->all());

            return response()->json([
                'id'        => $result['admin']->id,
                'name'      => $result['admin']->name,
                'username'  => $result['admin']->username,
                'role'      => $result['admin']->role,
                'token'     => $result['token'],
                'token_type'=> 'Bearer',
                'message'   => 'Login successful as ' . $result['admin']->role,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Admin Login Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during login.',
            ], 500);
        }
    }

    /**
     * Admin can Register officials
     */
    public function register(Request $request)
    {
        try {
            $admin = $this->adminService->register($request->all());

            return response()->json([
                'id'       => $admin->id,
                'name'     => $admin->name,
                'username' => $admin->username,
                'role'     => $admin->role,
                'message'  => 'Admin/Official registered successfully.',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Admin Register Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during registration.',
            ], 500);
        }
    }

    /**
     * Example protected route (dashboard).
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized.',
                ], 401);
            }

            return response()->json([
                'message' => 'Welcome to the Admin Dashboard, ' . $user->name,
                'role'    => $user->role,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Dashboard Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while accessing the dashboard.',
            ], 500);
        }
    }

    /**
     * Update the specified admin.
     */
    public function update(Request $request, $id)
    {
        try {
            $admin = Admin::findOrFail($id);

            $updatedAdmin = $this->adminService->update($admin, $request->all());

            return response()->json([
                'id'       => $updatedAdmin->id,
                'name'     => $updatedAdmin->name,
                'username' => $updatedAdmin->username,
                'role'     => $updatedAdmin->role,
                'message'  => $admin->role. ' updated successfully.',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Admin not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin Update Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during update.',
            ], 500);
        }
    }

    /**
     * Log out the currently authenticated admin.
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized.',
                ], 401);
            }

            $this->adminService->logout($user);

            return response()->json([
                'message' => $user->role . ' logged out successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Logout Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during logout.',
            ], 500);
        }

        
    }

    
    /**
     * Get an admin by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getById($id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'message' => 'Admin not found.',
                ], 404);
            }

            return response()->json([
                'id'       => $admin->id,
                'name'     => $admin->name,
                'username' => $admin->username,
                'role'     => $admin->role,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin Get By Id Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while retrieving admin.',
            ], 500);
        }
    }

    /**
     * Delete an admin by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'message' => 'Admin not found.',
                ], 404);
            }

            $admin->delete();

            return response()->json([
                'message' => 'Admin deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Admin Delete Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting admin.',
            ], 500);
        }
    }

    /**
     * Display a listing of all admins.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Order admins by newest first
            $admins = Admin::orderBy('id', 'desc')->get();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Display all admins successfully.',
                'data' => $admins
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Admin Index Error: ' . $e->getMessage());
    
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while retrieving admins.',
            ], 500);
        }
    }
     
}
