<?php

namespace App\Http\Controllers\Api;

use App\Models\Resident;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Services\ResidentService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class ResidentController extends Controller
{
    protected $residentService;

    public function __construct(ResidentService $residentService)
    {
        $this->residentService = $residentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $residents = Resident::all();
            return response()->json([
                'status' => 'success',
                'data' => $residents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve residents'
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage (Register new resident).
     */
    public function store(Request $request)
    {
        try {
            $resident = $this->residentService->registerUser($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Resident registered successfully',
                'data' => $resident
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Resident $resident)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $resident
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve resident'
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resident $resident)
    {
        //
    }

    /**
     * Update the specified resource in storage by ID.
     */
    public function update(Request $request, $id)
    {
        try {
            $resident = Resident::findOrFail($id);

            $resident = $this->residentService->updateUser($request->all(), $resident);

            return response()->json([
                'status' => 'success',
                'message' => 'Resident updated successfully',
                'data' => $resident
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resident not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident)
    {
        try {
            $resident->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Resident deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete resident'
            ], 500);
        }
    }

/**
 * Display details for a resident by user ID.
 *
 * @param  int  $userId
 * @return \Illuminate\Http\JsonResponse
 */
public function showByUserId($userId)
{
    try {
        $resident = Resident::where('user_id', $userId)->first();

        if (!$resident) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resident not found for given user id'
            ], 404);
        }

        // ✅ Convert stored image path to full public URL
        if ($resident->valid_id_path) {
            $resident->valid_id_url = asset('storage/' . $resident->valid_id_path);
        } else {
            $resident->valid_id_url = null;
        }

        return response()->json([
            'status' => 'success',
            'data' => $resident
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve resident details',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}

