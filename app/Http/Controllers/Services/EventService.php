<?php

namespace App\Http\Controllers\Services;

use App\Models\Event;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventService
{
    /**
     * Get all events.
     */
    public function getAllEvents()
    {
        try {
            // Optionally eager load admin who posted
            return Event::with('admin')->get();
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to fetch events.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a single event by its ID.
     */
    public function getEventById($id)
    {
        try {
            // Optionally eager load admin who posted
            $event = Event::with('admin')->find($id);
            if (!$event) {
                return [
                    'error' => 'Event not found.',
                    'code' => 404,
                ];
            }
            return $event;
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to fetch event.',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createEvent(array $data)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date' => 'required|date',
                'posted_by' => 'required|string|max:255',
                'posted_id' => 'required|integer|exists:admins,id', // posted_id is admin id (auto-set by controller)
            ]);

            if ($validator->fails()) {
                return [
                    'error' => 'Validation failed.',
                    'messages' => $validator->errors(),
                    'code' => 422,
                ];
            }

            $validated = $validator->validated();

            // Optionally, you can check if the admin exists (redundant due to validation)
            $admin = Admin::find($validated['posted_id']);
            if (!$admin) {
                return [
                    'error' => 'Admin not found for posted_id.',
                    'code' => 404,
                ];
            }

            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'date' => $validated['date'],
                'posted_by' => $validated['posted_by'],
                'posted_id' => $validated['posted_id'], // This is the admin's id
            ]);
            return $event;
        } catch (ValidationException $ve) {
            return [
                'error' => 'Validation exception.',
                'messages' => $ve->errors(),
                'code' => 422,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to create event.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an event.
     * 
     * 'posted_id' is the id of the admin who posts the event (from admins table)
     */
    public function updateEvent($id, array $data)
    {
        try {
            $event = Event::find($id);
            if (!$event) {
                return [
                    'error' => 'Event not found.',
                    'code' => 404,
                ];
            }

            $validator = Validator::make($data, [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'date' => 'sometimes|required|date',
                'posted_by' => 'sometimes|required|string|max:255',
                'posted_id' => 'sometimes|required|integer|exists:admins,id', // posted_id is admin id
            ]);

            if ($validator->fails()) {
                return [
                    'error' => 'Validation failed.',
                    'messages' => $validator->errors(),
                    'code' => 422,
                ];
            }

            $validated = $validator->validated();

            // If posted_id is being updated, check if the admin exists
            if (isset($validated['posted_id'])) {
                $admin = Admin::find($validated['posted_id']);
                if (!$admin) {
                    return [
                        'error' => 'Admin not found for posted_id.',
                        'code' => 404,
                    ];
                }
            }

            $event->update($validated);
            return $event;
        } catch (ValidationException $ve) {
            return [
                'error' => 'Validation exception.',
                'messages' => $ve->errors(),
                'code' => 422,
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to update event.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an event.
     *
     * @param  int  $id
     * @return bool|array
     */
    public function deleteEvent($id)
    {
        try {
            $event = Event::find($id);
            if (!$event) {
                return [
                    'error' => 'Event not found.',
                    'code' => 404,
                ];
            }
            return $event->delete();
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to delete event.',
                'message' => $e->getMessage(),
            ];
        }
    }
}
