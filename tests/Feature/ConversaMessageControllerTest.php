<?php

namespace VendorName\Conversa\Tests\Feature;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Models\ConversaMessage;
use VendorName\Conversa\Models\ConversaThread;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;
    protected $space;
    protected $thread;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = $this->createTestUser();
        $this->workspace = $this->createTestWorkspace();
        
        $this->space = ConversaSpace::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $this->thread = ConversaThread::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_send_a_message_to_a_space()
    {
        $messageData = [
            'space_id' => $this->space->id,
            'content' => 'Test message content',
            'type' => 'text',
        ];

        $response = $this->postJson(route('conversa.messages.store'), $messageData);

        $response->assertStatus(201)
            ->assertJson([
                'content' => 'Test message content',
                'type' => 'text',
                'user_id' => $this->user->id,
            ]);

        $this->assertDatabaseHas('conversa_messages', [
            'space_id' => $this->space->id,
            'content' => 'Test message content',
            'type' => 'text',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_send_a_message_to_a_thread()
    {
        $messageData = [
            'thread_id' => $this->thread->id,
            'content' => 'Test message content',
            'type' => 'text',
        ];

        $response = $this->postJson(route('conversa.messages.store'), $messageData);

        $response->assertStatus(201)
            ->assertJson([
                'content' => 'Test message content',
                'type' => 'text',
                'user_id' => $this->user->id,
            ]);

        $this->assertDatabaseHas('conversa_messages', [
            'thread_id' => $this->thread->id,
            'content' => 'Test message content',
            'type' => 'text',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_send_a_message_with_attachments()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $messageData = [
            'space_id' => $this->space->id,
            'content' => 'Message with attachment',
            'type' => 'file',
            'attachments' => [$file],
        ];

        $response = $this->postJson(route('conversa.messages.store'), $messageData);

        $response->assertStatus(201);

        $message = ConversaMessage::latest()->first();
        
        $this->assertNotNull($message->attachments->first());
        Storage::disk('public')->assertExists($message->attachments->first()->file_path);
    }

    /** @test */
    public function it_validates_required_fields_when_sending_a_message()
    {
        $response = $this->postJson(route('conversa.messages.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function it_requires_either_space_id_or_thread_id()
    {
        $messageData = [
            'content' => 'Test message content',
            'type' => 'text',
        ];

        $response = $this->postJson(route('conversa.messages.store'), $messageData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['space_id', 'thread_id']);
    }

    /** @test */
    public function it_can_list_messages_for_a_space()
    {
        // Create some test messages
        ConversaMessage::factory()->count(5)->create([
            'workspace_id' => $this->workspace->id,
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('conversa.messages.index', $this->space));

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'type',
                        'user_id',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function it_paginates_messages()
    {
        ConversaMessage::factory()->count(25)->create([
            'workspace_id' => $this->workspace->id,
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('conversa.messages.index', $this->space));

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data') // Assuming default pagination is 20
            ->assertJsonStructure([
                'data',
                'links' => ['next', 'prev'],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    /** @test */
    public function it_includes_user_and_attachment_data_with_messages()
    {
        $message = ConversaMessage::factory()
            ->has(ConversaAttachment::factory())
            ->create([
                'workspace_id' => $this->workspace->id,
                'space_id' => $this->space->id,
                'user_id' => $this->user->id,
            ]);

        $response = $this->getJson(route('conversa.messages.index', $this->space));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'attachments' => [
                            '*' => [
                                'id',
                                'file_name',
                                'file_type',
                                'url',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_viewing_messages()
    {
        $otherSpace = ConversaSpace::factory()->create([
            'workspace_id' => $this->createTestWorkspace()->id,
            'type' => 'private',
        ]);

        $response = $this->getJson(route('conversa.messages.index', $otherSpace));

        $response->assertStatus(403);
    }

    /**
     * Create a test user.
     */
    protected function createTestUser(): object
    {
        // Note: In a real application, you would use your actual User model
        return new class {
            public $id = 1;
            public $workspace_id = 1;
        };
    }

    /**
     * Create a test workspace.
     */
    protected function createTestWorkspace(): object
    {
        // Note: In a real application, you would use your actual Workspace model
        return new class {
            public $id = 1;
        };
    }
}
