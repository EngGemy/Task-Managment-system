<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function index()
    {
        $tasks = Cache::remember('tasks', 3600, function () {
            return Task::all();
        });

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        return view('tasks.create');
    }

    public function store(Request $request)
    {
        $task = Task::create($request->all());

        // Check if google_calendar_event_id is set in the request
        if ($request->has('google_calendar_event_id')) {
            $task->google_calendar_event_id = $request->input('google_calendar_event_id');
            $task->save();
        }

        // Clear the tasks cache
        Cache::forget('tasks');

        $addToCalendarResponse = $this->calendarService->addToGoogleCalendar($task);
        if ($addToCalendarResponse instanceof \Illuminate\Http\RedirectResponse) {
            return $addToCalendarResponse;
        }

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $taskDetails = Cache::remember('task_' . $task->id, 3600, function () use ($task) {
            return $task;
        });

        return view('tasks.show', compact('taskDetails'));
    }

    public function edit(Task $task)
    {
        return view('tasks.edit', compact('task'));
    }

    public function update(Request $request, Task $task)
    {
        $task->update($request->all());

        // Check if google_calendar_event_id is set in the request
        if ($request->has('google_calendar_event_id')) {
            $task->google_calendar_event_id = $request->input('google_calendar_event_id');
            $task->save();
        }

        // Clear the tasks cache
        Cache::forget('tasks');

        $this->calendarService->updateGoogleCalendarEvent($task);
        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $this->calendarService->removeFromGoogleCalendar($task);
        $task->delete();

        // Clear the tasks cache
        Cache::forget('tasks');

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}
