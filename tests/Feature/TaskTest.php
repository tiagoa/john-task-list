<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private $endpoint = '/api/tasks';
    private $disk = 'public';

    public function test_index(): void
    {
        $response = $this->getJson($this->endpoint);
        $response->assertStatus(401);

        Task::factory()
            ->count(3)
            ->create();

        $this->registerAndLogin();
        $response = $this->getJson('api/tasks');
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json->count(3));

        $tasks = Task::factory()
            ->count(30)
            ->create();
        $response = $this->getJson('api/tasks');
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json->count(10));

        $tasks[0]->completed = date('Y-m-d H:i:s');
        $tasks[0]->save();
        $response_sorted = $this->getJson('api/tasks?order_by=desc');
        $this->assertNotEquals($response->json(0)['id'], $response_sorted->json(0)['id']);
    }

    private function create()
    {
        return $this->postJson($this->endpoint, ['title' => 'test', 'description' => 'test']);
    }

    public function test_store(): void
    {
        $response = $this->postJson($this->endpoint);
        $response->assertStatus(401);

        $this->registerAndLogin();
        $response = $this->postJson($this->endpoint, []);
        $response->assertStatus(422);
        $response = $this->postJson($this->endpoint, ['title' => 1]);
        $response->assertStatus(422);
        $response = $this->postJson($this->endpoint, ['title' => 'test', 'description' => 1]);
        $response->assertStatus(422);

        $response = $this->create();
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('title', 'test')
                ->where('description', 'test')
                ->where('author', auth()->user()->id)
                ->etc()
        );
    }

    public function test_store_with_attachments()
    {
        Storage::fake($this->disk);
        $this->registerAndLogin();
        $attachment = UploadedFile::fake()->image('photo1.jpg');
        $response = $this->postJson($this->endpoint, [
            'title' => 'test',
            'description' => 'test',
            'attachments' => [
                $attachment,
            ]
        ]);
        $response->assertOk();
        Storage::disk($this->disk)->assertExists('attachments/'.$attachment->hashName());
    }

    public function test_show(): void
    {
        Storage::fake($this->disk);
        $this->registerAndLogin();

        $response = $this->getJson($this->endpoint.'/a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0');
        $response->assertStatus(404);

        $created = $this->create();
        $response = $this->getJson($this->endpoint.'/'.$created->json('id'));
        $response->assertOk();

        $attachment = UploadedFile::fake()->image('photo1.jpg');
        $response = $this->postJson($this->endpoint, [
            'title' => 'with attachment',
            'description' => 'with attachment',
            'attachments' => [
                $attachment,
            ]
        ]);
        Storage::disk($this->disk)->assertExists('attachments/'.$attachment->hashName());
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('title', 'with attachment')
                ->where('description', 'with attachment')
                ->where('author', auth()->user()->id)
                ->where('attachments.0', $attachment->hashName())
                ->etc()
        );
    }

    public function test_update(): void
    {
        $response = $this->putJson($this->endpoint);
        $response->assertStatus(405);

        $task = Task::factory()->create();

        $response = $this->putJson($this->endpoint.'/'.$task->id);
        $response->assertStatus(401);

        $this->registerAndLogin();
        $response = $this->putJson($this->endpoint . '/' . $task->id, []);
        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
        $json->where('title', $task->title)
            ->where('description', $task->description)
            ->where('author', $task->author)
            ->where('editor', auth()->user()->id)
            ->whereNot('updated_at', $task->updated_at)
            ->etc()
        );

        $response = $this->putJson($this->endpoint . '/' . $task->id, ['completed' => true]);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('title', $task->title)
                ->where('description', $task->description)
                ->where('author', $task->author)
                ->where('editor', auth()->user()->id)
                ->whereNot('updated_at', $task->updated_at)
                ->whereNot('completed', $task->completed)
                ->etc()
        );

        $response = $this->putJson($this->endpoint . '/' . $task->id, ['completed' => false]);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('completed', $task->completed)
                ->etc()
        );

        $response = $this->putJson($this->endpoint . '/' . $task->id, ['title' => 'updated']);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('title', 'updated')
                ->etc()
        );

        $attachment = UploadedFile::fake()->image('photo1.jpg');
        $response = $this->putJson($this->endpoint . '/' . $task->id, [
            'attachments' => [
                $attachment,
            ]
        ]);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('attachments.0', $attachment->hashName())->etc()
        );
        Storage::disk($this->disk)->assertExists('attachments/'.$attachment->hashName());

        $attachment2 = UploadedFile::fake()->image('photo2.jpg');
        $response = $this->putJson($this->endpoint . '/' . $task->id, [
            'attachments' => [
                $attachment2,
            ]
        ]);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('attachments.0', $attachment->hashName())
                ->where('attachments.1', $attachment2->hashName())
                ->etc()
        );
        Storage::disk($this->disk)->assertExists('attachments/'.$attachment->hashName());
        Storage::disk($this->disk)->assertExists('attachments/'.$attachment2->hashName());

        $response = $this->putJson($this->endpoint . '/' . $task->id, [
            'del_attachments' => [
                $attachment2->hashName(),
            ]
        ]);
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) =>
        $json->where('attachments.0', $attachment->hashName())
            ->etc()
        );
        Storage::disk($this->disk)->assertMissing('attachments/'.$attachment2->hashName());
    }

    public function test_destroy(): void
    {
        Storage::fake($this->disk);
        $this->registerAndLogin();

        $response = $this->deleteJson($this->endpoint.'/a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0');
        $response->assertStatus(404);

        $created = $this->create();
        $response = $this->deleteJson($this->endpoint.'/'.$created->json('id'));
        $response->assertStatus(204);

        $attachment = UploadedFile::fake()->image('photo1.jpg');
        $to_delete = $this->postJson($this->endpoint, [
            'title' => 'with attachment',
            'description' => 'with attachment',
            'attachments' => [
                $attachment,
            ]
        ]);
        $response = $this->deleteJson($this->endpoint.'/'.$to_delete->json('id'));
        $response->assertStatus(204);
        Storage::disk($this->disk)->assertMissing('attachments/'.$attachment->hashName());
    }
}
