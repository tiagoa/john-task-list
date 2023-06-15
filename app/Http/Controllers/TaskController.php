<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{

    public function index()
    {
        $tasks = Task::orderBy('completed', 'asc')->get();
        return response()->json($tasks);
    }

    private function upload($request)
    {
        $attachments = [];
        if ($request->file('attachments')) {
            foreach ($request->file('attachments') as $attachment) {
                $file_name = $attachment->hashName();
                $attachment->storeAs('attachments', $file_name, ['disk' => 'public']);
                $attachments[] = $file_name;
            }
        }
        return $attachments;
    }

    public function store(TaskRequest $request)
    {
        $new_task = $request->all();

        $new_task['attachments'] = $this->upload($request);
        $new_task['author'] = auth()->user()->id;
        $task = Task::create($new_task);

        return response()->json($task);
    }

    public function show(Task $task)
    {
        return response()->json($task->load('author', 'editor'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $new_task = $request->all();
        $task->title = $new_task['title'] ?? $task->title;
        $task->description = $new_task['description'] ?? $task->description;
        if (isset($new_task['completed']) && $new_task['completed']) {
            $task->completed = Carbon::now();
        } else {
            $task->completed = null;
        }
        $new_attachments = $this->upload($request);
        if (is_array($task->attachments)) {
            $task->attachments = array_merge($task->attachments, $new_attachments);
            if (isset($new_task['del_attachments'])) {
                $remaining = $task->attachments;
                foreach ($new_task['del_attachments'] as $del) {
                    $key = array_search($del, $remaining);
                    if ($key !== false) {
                        Storage::delete('public/attachments/'.$del);
                        unset($remaining[$key]);
                    }
                }
                $task->attachments = $remaining;
            }
        } else {
            $task->attachments = $new_attachments;
        }

        $task->editor = auth()->user()->id;
        $task->save();

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        if ($task->attachments) {
            foreach ($task->attachments as $attachment) {
                Storage::disk('public')->delete('attachments/'.$attachment);
            }
        }
        $task->delete();
        return response()->json('', 204);
    }
}
