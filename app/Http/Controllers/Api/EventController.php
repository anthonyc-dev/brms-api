<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Services\EventService;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display a listing of the events.
     */
    public function index(): JsonResponse
    {
        $events = $this->eventService->getAllEvents();

        // If error returned
        if (is_array($events) && isset($events['error'])) {
            return response()->json($events, 500);
        }

        return response()->json($events);
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Get the authenticated admin
        $admin = auth('admin')->user();
        
        if (!$admin) {
            return response()->json([
                'error' => 'Unauthorized. Admin authentication required.',
                'code' => 401
            ], 401);
        }

        $data = $request->all();
        
        // Automatically set the admin information
        $data['posted_id'] = $admin->id;
        $data['posted_by'] = $admin->name ?? $admin->username;
        
        $result = $this->eventService->createEvent($data);

        if (is_array($result) && isset($result['error'])) {
            $status = $result['code'] ?? 400;
            return response()->json($result, $status);
        }

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $result
        ], 201);
    }

    /**
     * Display the specified event.
     */
    public function show($id): JsonResponse
    {
        $event = $this->eventService->getEventById($id);

        if (is_array($event) && isset($event['error'])) {
            $status = $event['code'] ?? 404;
            return response()->json($event, $status);
        }

        return response()->json($event);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->eventService->updateEvent($id, $data);

        if (is_array($result) && isset($result['error'])) {
            $status = $result['code'] ?? 400;
            return response()->json($result, $status);
        }

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $result
        ]);
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy($id): JsonResponse
    {
        $result = $this->eventService->deleteEvent($id);

        if (is_array($result) && isset($result['error'])) {
            $status = $result['code'] ?? 404;
            return response()->json($result, $status);
        }

        return response()->json([
            'message' => 'Event deleted successfully.'
        ]);
    }


    public function getAllByPosted(string $posted_by): JsonResponse
    {
        // Validate allowed roles
        $allowedPostedBy = ['admin', 'official'];
        if (!in_array($posted_by, $allowedPostedBy)) {
            return response()->json([
                'error' => 'Invalid posted_by specified.',
                'code' => 422,
            ], 422);
        }
    
        // Fetch all events by posted_by
        $events = Event::where('posted_by', $posted_by)->get();
    
        return response()->json([
            'posted_by' => $posted_by,
            'events' => $events,
        ]);
    }
    
}
