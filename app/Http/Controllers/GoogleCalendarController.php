<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service_Calendar;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    /**
     * Redirects to Google for authentication.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect()
    {
        $client = $this->initializeGoogleClient();
        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    /**
     * Handles the callback from Google authentication.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        $client = $this->initializeGoogleClient();
        $authCode = $request->input('code');

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $this->saveAccessToken($accessToken);
            return redirect('/google-calendar')->with('message', 'Credentials saved');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Fetches resources from Google Calendar.
     *
     * @return string
     */
    public function getResources()
    {
        $client = $this->getAuthenticatedClient();

        if (!$client) {
            return 'Authentication required.';
        }

        return $this->fetchCalendarEvents($client);
    }

    /**
     * Initializes the Google_Client.
     *
     * @return Google_Client
     */
    private function initializeGoogleClient()
    {
        $client = new Google_Client();
        $client->setApplicationName(config('app.name'));
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setAuthConfig(storage_path('client_secret.json'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setRedirectUri(config('app.url') . '/auth/google/callback');
        return $client;
    }

    /**
     * Authenticates the user with Google and returns the client instance.
     *
     * @return Google_Client|false
     */
    private function getAuthenticatedClient()
    {
        $client = $this->initializeGoogleClient();
        $credentialsPath = storage_path('client_secret_generated.json');

        if (!file_exists($credentialsPath)) {
            return false;
        }

        $accessToken = json_decode(file_get_contents($credentialsPath), true);
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->saveAccessToken($client->getAccessToken());
        }

        return $client;
    }

    /**
     * Saves the access token to a file.
     *
     * @param array $accessToken
     */
    private function saveAccessToken($accessToken)
    {
        $credentialsPath = storage_path('client_secret_generated.json');
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
    }

    /**
     * Fetches calendar events.
     *
     * @param Google_Client $client
     * @return string
     */
    private function fetchCalendarEvents(Google_Client $client)
    {
        $service = new Google_Service_Calendar($client);
        $calendarId = 'primary';
        $optParams = [
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        ];

        try {
            $results = $service->events->listEvents($calendarId, $optParams);
            return $this->formatEventsList($results->getItems());
        } catch (\Exception $e) {
            return 'Error fetching events: ' . $e->getMessage();
        }
    }

    /**
     * Formats a list of Google Calendar events.
     *
     * @param array $events
     * @return string
     */
    private function formatEventsList(array $events)
    {
        if (empty($events)) {
            return "No upcoming events found.";
        }

        $eventList = "Upcoming events:\n";
        foreach ($events as $event) {
            $start = $event->start->dateTime ?: $event->start->date;
            $eventList .= sprintf("%s (%s)\n", $event->getSummary(), $start);
        }

        return $eventList;
    }
}
