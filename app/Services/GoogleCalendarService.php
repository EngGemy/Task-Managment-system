<?php

namespace App\Services;

use App\Models\Task;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarService
{
    /**
     * Returns an instance of Google_Client.
     *
     * @return Google_Client
     */
    public function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName(config('app.name'));
        $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
        $client->setAuthConfig(storage_path('client_secret.json'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setRedirectUri(config('app.url') . '/auth/google/callback');

        $credentialsPath = storage_path('client_secret_generated.json');
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
            $client->setAccessToken($accessToken);
        }

        return $client;
    }

    /**
     * Saves the access token to a file.
     *
     * @param array $accessToken
     */
    public function saveAccessToken($accessToken)
    {
        $credentialsPath = storage_path('client_secret_generated.json');
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
    }

    /**
     * Adds a task to the Google Calendar.
     *
     * @param Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    /**
     * Adds a task to the Google Calendar.
     *
     * @param Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addToGoogleCalendar(Task $task)
    {
        $client = $this->getClient();

        if ($client->isAccessTokenExpired() && !$client->getRefreshToken()) {
            return redirect()->to($client->createAuthUrl());
        }

        if ($client->isAccessTokenExpired()) {
            $accessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $client->setAccessToken($accessToken);
            $this->saveAccessToken($accessToken);
        }

        $service = new Google_Service_Calendar($client);
        $dueDate = new Carbon($task->due_date);
        $event = new Google_Service_Calendar_Event([
            'summary' => $task->title,
            'description' => $task->description,
            'start' => ['dateTime' => $dueDate->toRfc3339String(), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => $dueDate->toRfc3339String(), 'timeZone' => 'UTC'],
        ]);

        try {
            // After successfully adding the event
            $createdEvent = $service->events->insert('primary', $event);
            // Update the task's google_calendar_event_id here
            $task->google_calendar_event_id = $createdEvent->getId(); // Save the event ID to the task
            $task->save(); // Save the task with the event ID

            return redirect()->route('tasks.index')->with('success', 'Task created and added to Google Calendar');
        } catch (\Exception $e) {
            Log::error('Google Calendar API error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error adding event to Google Calendar: ' . $e->getMessage());
        }
    }



    /**
     * Updates a Google Calendar event for the given task.
     *
     * @param Task $task
     */
    /**
     * Updates a Google Calendar event for the given task.
     *
     * @param Task $task
     */
    public function updateGoogleCalendarEvent(Task $task)
    {
        $client = $this->getClient();
        // Refresh token if needed, similar to addToGoogleCalendar

        $service = new Google_Service_Calendar($client);
        $eventId = $task->google_calendar_event_id; // Retrieve the stored event ID

        if ($eventId === null) {
            Log::error('Google Calendar API error: Missing Event ID for task ID ' . $task->id);
            // Handle the error appropriately
            return;
        }

        $dueDate = new Carbon($task->due_date);
        $updatedEvent = new Google_Service_Calendar_Event([
            'summary' => $task->title,
            'description' => $task->description,
            'start' => ['dateTime' => $dueDate->toRfc3339String(), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => $dueDate->toRfc3339String(), 'timeZone' => 'UTC'],
        ]);

        try {
            $service->events->update('primary', $eventId, $updatedEvent);
            // Optionally, update the Task model here if necessary
        } catch (\Exception $e) {
            Log::error('Google Calendar API error: ' . $e->getMessage());
            // Handle error
        }
    }


    /**
     * Removes a task from the Google Calendar.
     *
     * @param Task $task
     */
    /**
     * Removes a task from the Google Calendar.
     *
     * @param Task $task
     * @return bool
     */
    public function removeFromGoogleCalendar(Task $task)
    {
        $client = $this->getClient();

        if ($client->isAccessTokenExpired() && !$client->getRefreshToken()) {
            // Handle the case where the access token is expired and there's no refresh token.
            // You may want to redirect the user to reauthorize the application.
            return false;
        }

        if ($client->isAccessTokenExpired()) {
            $accessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $client->setAccessToken($accessToken);
            $this->saveAccessToken($accessToken);
        }

        $service = new Google_Service_Calendar($client);
        $eventId = $task->google_calendar_event_id; // Retrieve the stored event ID

        if ($eventId === null) {
            Log::error('Google Calendar API error: Missing Event ID for task ID ' . $task->id);
            // Handle the error appropriately
            return false;
        }

        try {
            $service->events->delete('primary', $eventId);
            // After successful deletion, you can clear the google_calendar_event_id from the task
            $task->google_calendar_event_id = null;
            $task->save(); // Save the task without the event ID

            return true; // Deletion was successful
        } catch (\Exception $e) {
            Log::error('Google Calendar API error: ' . $e->getMessage());
            // Handle error
            return false;
        }
    }

}
