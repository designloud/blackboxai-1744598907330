<?php

namespace VendorName\Conversa\Tests\Feature;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Models\ConversaMessage;
use VendorName\Conversa\Models\ConversaThread;
use VendorName\Conversa\Events\MessageSent;
use VendorName\Conversa\Events\MessageReacted;
use VendorName\Conversa\Events\MessageDeleted;
use VendorName\Conversa\Events\UserTyping;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;

class ConversaRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;
    protected $space;
    protected $thread;

    protected function setUp(): void
    {
        parent::setUp();

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
    public function it_broadcasts_message_sent_event_for_space()
    {
        Event::fake([MessageSent::class]);

        $message = ConversaMessage::factory()->create([
            'workspace_id' => $this->workspace->id,
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
        ]);

        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id &&
                   $event->broadcastOn()[0]->name === "private-space.{$this->space->id}";
        });
    }

    /** @test */
    public function it_broadcasts_message_sent_event_for_thread()
    {
        Event::fake([MessageSent::class]);

        $message = ConversaMessage::factory()->create([
            'workspace_id' => $this->workspace->id,
            'thread_id' => $this->thread->id,
            'user_id' => $this->user->id,
        ]);

        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id &&
                   $event->broadcastOn()[0]->name === "private-thread.{$this->thread->id}";
        });
    }

    /** @test */
    public function it_broadcasts_message_reacted_event()
    {
        Event::fake([MessageReacted::class]);

        $message = ConversaMessage::factory()->create([
            'workspace_id' => $this->workspace->id,
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
        ]);

        $reaction = $message->addReaction($this->user->id, '👍');

        Event::assertDispatched(MessageReacted::class, function ($event) use ($message, $reaction) {
            return $event->message->id === $message->id &&
                   $event->reaction->id === $reaction->id &&
                   $event->added === true;
        });
    }

    /** @test */
    public function it_broadcasts_message_deleted_event()
    {
        Event::fake([MessageDeleted::class]);

        $message = ConversaMessage::factory()->create([
            'workspace_id' => $this->workspace->id,
            'space_id' => $this->space->id,
            'user_id' => $this->user->id,
        ]);

        $message->delete();

        Event::assertDispatched(MessageDeleted::class, function ($event) use ($message) {
            return $event->messageId === $message->id;
        });
    }

    /** @test */
    public function it_broadcasts_user_typing_event()
    {
        Event::fake([UserTyping::class]);

        UserTyping::dispatch($this->user, $this->space->id);

        Event::assertDispatched(UserTyping::class, function ($event) {
            return $event->getUser()->id === $this->user->id &&
                   $event->getSpaceId() === $this->space->id;
        });
    }

    /** @test */
    public function it_authorizes_space_channel_access()
    {
        Broadcast::shouldReceive('channel')
            ->with('space.{spaceId}', \Closure::class)
            ->andReturnSelf();

        $this->assertTrue(
            $this->canAccessChannel("private-space.{$this->space->id}")
        );

        // Test unauthorized access
        $otherSpace = ConversaSpace::factory()->create([
            'workspace_id' => $this->createTestWorkspace(2)->id,
        ]);

        $this->assertFalse(
            $this->canAccessChannel("private-space.{$otherSpace->id}")
        );
    }

    /** @test */
    public function it_authorizes_thread_channel_access()
    {
        $this->thread->addMember($this->user->id);

        $this->assertTrue(
            $this->canAccessChannel("private-thread.{$this->thread->id}")
        );

        // Test unauthorized access
        $otherThread = ConversaThread::factory()->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $this->assertFalse(
            $this->canAccessChannel("private-thread.{$otherThread->id}")
        );
    }

    /** @test */
    public function it_rate_limits_messages()
    {
        $maxAttempts = config('conversa.rate_limits.messages_per_minute', 30);

        // Send messages up to the limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->postJson(route('conversa.messages.store'), [
                'space_id' => $this->space->id,
                'content' => "Test message {$i}",
            ]);

            $response->assertSuccessful();
        }

        // The next message should be rate limited
        $response = $this->postJson(route('conversa.messages.store'), [
            'space_id' => $this->space->id,
            'content' => 'Rate limited message',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'message',
                'errors',
                'retry_after',
                'retry_after_seconds',
            ]);
    }

    /**
     * Helper method to check channel authorization.
     */
    protected function canAccessChannel(string $channel): bool
    {
        return Broadcast::check($this->user, $channel);
    }

    /**
     * Create a test user.
     */
    protected function createTestUser(int $id = 1): object
    {
        return new class($id) {
            public $id;
            public $workspace_id = 1;
            public $name = 'Test User';
            public $email = 'test@example.com';
            public $avatar_url = 'https://example.com/avatar.jpg';

            public function __construct($id)
            {
                $this->id = $id;
            }
        };
    }

    /**
     * Create a test workspace.
     */
    protected function createTestWorkspace(int $id = 1): object
    {
        return new class($id) {
            public $id;

            public function __construct($id)
            {
                $this->id = $id;
            }
        };
    }
}
