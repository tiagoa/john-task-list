<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /**
     * @OA\Get(
     *      path="/tasks",
     *      summary="Get all tasks",
     *      description="Get al tasks",
     *      tags={"Tasks"},
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort to",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *              enum={"completed","created_at","updated_at"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="order_by",
     *         in="query",
     *         description="Sorting direction",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *              enum={"asc","desc"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="How many tasks to retrieve",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             maximum=100,
     *             minimum=1,
     *             default=10,
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Task count to start retrieving from",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             maximum=100,
     *             minimum=1,
     *             default=0
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Task list",
     *          @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/task")
     *         ),
     *      )
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'sort_by' => 'in:completed,created_at,updated_at',
            'order_by' => 'in:asc,desc',
            'limit' => 'integer|min:1|max:100',
            'offset' => 'integer|min:1|max:100',
        ]);
        $sorted = $request->query('sort_by') ?? 'completed';
        $ordered = $request->query('asc') ?? 'asc';
        $offset = $request->query('offset') ?? 0;
        $limit = $request->query('limit') ?? 10;
        $tasks = Task::orderBy($sorted, $ordered)
            ->offset($offset)
            ->limit($limit)
            ->get();
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

    /**
     * @OA\Schema(
     *     schema="new_task_payload",
     *     required={"title"},
     *     @OA\Property(property="title", type="string", format="text", example="Task"),
     *     @OA\Property(property="description", type="string", format="text", example="Task description"),
     * ),
     * @OA\Schema(
     *     schema="new_task",
     *     @OA\Property(property="id", type="string", example="99695937-175d-4aae-b2b2-ea00244a7970"),
     *     @OA\Property(property="title", type="string", example="Task"),
     *     @OA\Property(property="description", type="string", example="Task description"),
     *     @OA\Property(property="author", type="string", example="996b817c-9416-481f-b955-f7ab4bacaef9"),
     *     @OA\Property(property="attachments", type="array", @OA\Items(type="string", example="file.jpg")),
     *     @OA\Property(property="created_at", type="string", example="2023-06-15T23:02:09.000000Z"),
     *     @OA\Property(property="updated_at", type="string", example="2023-06-15T23:02:09.000000Z"),
     * ),
     * @OA\Schema(
     *     schema="task",
     *     allOf={
     *          @OA\Schema(ref="#/components/schemas/new_task"),
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(property="completed", type="string", example="2023-06-15T23:02:09.000000Z"),
     *              @OA\Property(property="editor", type="string", example="996b817c-9416-481f-b955-f7ab4bacaef9"),
     *          )
     *      },
     * )
     * @OA\Schema(
     *     schema="task_attachment",
     *     @OA\Property(
     *         description="Task data",
     *         property="data",
     *         type="string",
     *         example="{""title"":""Task title"", ""description"":""Task description""}"
     *     ),
     *     @OA\Property(
     *         description="Attachments",
     *         property="attachments[]",
     *         type="array",
     *         @OA\Items(type="string", format="binary")
     *     )
     * ),
     * @OA\Post(
     *      path="/tasks",
     *      tags={"Tasks"},
     *      security={{"Bearer":{}}},
     *      summary="Create task",
     *      description="Create a new task",
     *      @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={@OA\Schema(ref="#/components/schemas/new_task_payload")}
     *              )
     *          ),
     *          @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={@OA\Schema(ref="#/components/schemas/task_attachment")}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Task created",
     *          @OA\JsonContent(
     *              allOf={@OA\Schema(ref="#/components/schemas/new_task")}
     *          ),
     *     ),
     * )
     */
    public function store(TaskRequest $request)
    {
        $new_task = $request->all();

        $new_task['attachments'] = $this->upload($request);
        $new_task['author'] = auth()->user()->id;
        $task = Task::create($new_task);

        return response()->json($task);
    }

    /**
     *  @OA\Parameter(
     *      description="Task ID",
     *      in="path",
     *      name="id",
     *      required=true,
     *     example="0006faf6-7a61-426c-9034-579f2cfcfa83",
     *      @OA\Schema(type="string"),
     * ),
     * @OA\Get(
     *      path="/tasks/{id}",
     *      summary="Get task",
     *      description="Get task details",
     *      tags={"Tasks"},
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          ref="#/components/parameters/id"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Task details",
     *          @OA\JsonContent(
     *              allOf={@OA\Schema(ref="#/components/schemas/task")}
     *          ),
     *      )
     * )
     */
    public function show(Task $task)
    {
        return response()->json($task->load('author', 'editor'));
    }

    /**
     * @OA\Put(
     *      path="/tasks/{id}",
     *      summary="Update a task",
     *      description="Update task details",
     *      tags={"Tasks"},
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          ref="#/components/parameters/id"
     *      ),
     *      @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  allOf={
     *                      @OA\Schema(ref="#/components/schemas/new_task_payload"),
     *                      @OA\Schema(
     *                          type="object",
     *                          @OA\Property(property="completed", type="boolean", example="true"),
     *                          @OA\Property(property="del_attachments", type="array", @OA\Items(type="string", example="file.jpg")),
     *                      )
     *                  }
     *              )
     *          ),
     *          @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 allOf={@OA\Schema(ref="#/components/schemas/task_attachment")}
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Task updated",
     *          @OA\JsonContent(
     *              allOf={@OA\Schema(ref="#/components/schemas/task")}
     *          ),
     *      )
     * )
     */
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

    /**
     * @OA\Delete(
     *      path="/tasks/{id}",
     *      summary="Delete task",
     *      description="Delete task",
     *      tags={"Tasks"},
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          ref="#/components/parameters/id"
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Task deleted",
     *      )
     * )
     */
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
