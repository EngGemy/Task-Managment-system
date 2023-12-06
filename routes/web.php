<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GoogleCalendarController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



// Display a list of tasks
Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');

// Show the task creation form
Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');

// Store a new task
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');

// Display a specific task
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');

// Show the task editing form
Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');

// Update a specific task
Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

// Delete a specific task
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

Route::post('/google-calendar/events/create', [GoogleCalendarController::class, 'createEvent'])
    ->name('google-calendar.events.create');

Route::post('/google-calendar/connect', [GoogleCalendarController::class, 'store']);
Route::get('/google-calendar/connect', [GoogleCalendarController::class, 'connect']);
Route::get('/get-resource', [GoogleCalendarController::class, 'getResources']);


Route::get('/auth/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback']);
