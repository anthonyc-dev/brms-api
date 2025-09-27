<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Services\ComplainantService;
use Illuminate\Http\JsonResponse;

class ComplainantController extends Controller
{
    protected $complainantService;

    public function __construct(ComplainantService $complainantService)
    {
        $this->complainantService = $complainantService;
    }

    /**
     * List all reports
     */
    public function index(): JsonResponse
    {
        return response()->json($this->complainantService->getReports());
    }

    /**
     * Store new report
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string',
            'date_time' => 'required|date',
            'complainant_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'is_anonymous' => 'boolean',
            'urgency_level' => 'required|in:low,medium,high,emergency',
            'witnesses' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'status' => 'nullable|in:pending,under_investigation,resolved,rejected',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }


        $validated['user_id'] = $user->id;

        $report = $this->complainantService->createReport($validated);

        return response()->json($report, 201);

    }

    /**
     * Show single report
     */
    public function show($id): JsonResponse
    {
        $report = $this->complainantService->getReportById($id);
        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }
        return response()->json($report);
    }

    /**
     * Update report
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,under_investigation,resolved,rejected',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'urgency_level' => 'nullable|in:low,medium,high,emergency',
        ]);

        

        $report = $this->complainantService->updateReport($id, $validated);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json($report);
    }

    /**
     * Delete report
     */
    public function destroy($id): JsonResponse
    {
        $deleted = $this->complainantService->deleteReport($id);

        if (!$deleted) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json(['message' => 'Report deleted successfully']);
    }
}
