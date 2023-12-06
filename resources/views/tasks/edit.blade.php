@extends('layouts.master')

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2>Edit Task</h2>
                <form action="{{ route('tasks.update', $task->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ $task->title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required>{{ $task->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date:</label>
                        <input type="datetime-local" class="form-control" id="due_date" name="due_date" value="{{ \Carbon\Carbon::parse($task->due_date)->format('Y-m-d\TH:i') }}" required>

                    </div>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
