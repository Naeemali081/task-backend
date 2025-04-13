<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => auth()->user()->tasks
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('tasks', 'public');
        }

        $task = auth()->user()->tasks()->create($data);

        return response()->json($task, 201);
    }
    public function update(Request $request, Task $task)
{
    if ($task->user_id !== auth()->id()) {
        return response()->json(['message' => 'This action is unauthorized.'], 403);
    }

    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('image')) {
        if ($task->image && Storage::disk('public')->exists($task->image)) {
            Storage::disk('public')->delete($task->image);
        }
        $data['image'] = $request->file('image')->store('tasks', 'public');
    }

    $task->update($data);

    return response()->json($task);
}

    public function destroy(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }
        if ($task->image) {
            Storage::disk('public')->delete($task->image);
        }
        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }

    public function download(Task $task)
    {
        if ($task->image && Storage::disk('public')->exists($task->image)) {
            return Storage::disk('public')->download($task->image);
        }

        return response()->json(['message' => 'Image not found'], 404);
    }
}
