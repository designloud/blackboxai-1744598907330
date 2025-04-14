<?php

namespace VendorName\Conversa\Tests\Feature;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaThread;
use VendorName\Conversa\Models\ConversaMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaThreadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;
    protected $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createTestUser();
        $this->workspace = $this->createTestWorkspace();
        $this->otherUser = $this->createTestUser(2);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_threads()
    {
        // Create some test threads
        ConversaThread::factory()
            ->count(3)
            ->create([
                'workspace_id' => $this->workspace->id,
                'created_by' => $this->user->id,
            ])
            ->each(function ($thread) {
                $thread->addMember($this->user->id);
                $thread->addMember($this->otherUser->id);
            });

        $response = $this->get(route('conversa.threads.index'));

        $response->assertStatus(200)
            ->assertViewIs('conversa::threads.index')
            ->assertViewHas('threads');
    }

    /** @test */
    public function it_can_show_thread_details()
    {
        $thread = ConversaThread::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $thread->addMember($this->user->id);
        $thread->addMember($this->otherUser->id);

        $response = $this->get(route('conversa.threads.show', $thread));

        $response->assertStatus(200)
            ->assertViewIs('conversa::threads.show')
            ->assertViewHas('thread');
    }

    /** @test */
    public function it_can_create_a_direct_message_thread()
    {
        $threadData = [
            'users' => [$this->otherUser->id],
            'message' => 'Initial message',
        ];

        $response = $this->post(route('conversa.threads.store'), $threadData);

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.threads.index'))
            ->assertSessionHas('success');

        $thread = ConversaThread::latest()->first();

        $this->assertEquals('direct', $thread->type);
        $this->assertTrue($thread->hasMember($this->user->id));
        $this->assertTrue($thread->hasMember($this->otherUser->id));

        $this->assertDatabaseHas('conversa_messages', [
            'thread_id' => $thread->id,
            'content' => 'Initial message',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_create_a_group_thread()
    {
        $otherUser2 = $this->createTestUser(3);

        $threadData = [
            'name' => 'Team Chat',
            'users' => [$this->otherUser->id, $otherUser2->id],
            'message' => 'Welcome to the group!',
            'type' => 'group',
        ];

        $response = $this->post(route('conversa.threads.store'), $threadData);

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.threads.index'))
            ->assertSessionHas('success');

        $thread = ConversaThread::latest()->first();

        $this->assertEquals('group', $thread->type);
        $this->assertEquals('Team Chat', $thread->name);
        $this->assertTrue($thread->hasMember($this->user->id));
        $this->assertTrue($thread->hasMember($this->otherUser->id));
        $this->assertTrue($thread->hasMember($otherUser2->id));
    }

    /** @test */
    public function it_validates_required_fields_when_creating_a_thread()
    {
        $response = $this->post(route('conversa.threads.store'), []);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['users', 'message']);
    }

    /** @test */
    public function it_can_update_a_group_thread()
    {
        $thread = ConversaThread::factory()->group()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $thread->addMember($this->user->id, 'admin');

        $updateData = [
            'name' => 'Updated Team Chat',
            'icon_url' => 'https://example.com/new-icon.png',
        ];

        $response = $this->put(route('conversa.threads.update', $thread), $updateData);

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.threads.show', $thread))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('conversa_threads', [
            'id' => $thread->id,
            'name' => 'Updated Team Chat',
            'icon_url' => 'https://example.com/new-icon.png',
        ]);
    }

    /** @test */
    public function it_prevents_non_admins_from_updating_group_threads()
    {
        $thread = ConversaThread::factory()->group()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->otherUser->id,
        ]);

        $thread->addMember($this->user->id, 'member');

        $updateData = [
            'name' => 'Updated Team Chat',
        ];

        $response = $this->put(route('conversa.threads.update', $thread), $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_a_thread()
    {
        $thread = ConversaThread::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $thread->addMember($this->user->id, 'admin');

        $response = $this->delete(route('conversa.threads.destroy', $thread));

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.threads.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('conversa_threads', [
            'id' => $thread->id,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_viewing_threads()
    {
        $thread = ConversaThread::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->otherUser->id,
        ]);

        $thread->addMember($this->otherUser->id);
        // Note: Current user is not a member

        $response = $this->get(route('conversa.threads.show', $thread));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_scopes_threads_to_current_workspace()
    {
        // Create threads in current workspace
        ConversaThread::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ])->each(function ($thread) {
            $thread->addMember($this->user->id);
        });

        // Create threads in another workspace
        ConversaThread::factory()->count(3)->create([
            'workspace_id' => $this->createTestWorkspace(2)->id,
            'created_by' => $this->otherUser->id,
        ]);

        $response = $this->get(route('conversa.threads.index'));
        $threads = $response->viewData('threads');

        $this->assertCount(2, $threads);
        $this->assertEquals($this->workspace->id, $threads->first()->workspace_id);
    }

    /**
     * Create a test user.
     */
    protected function createTestUser(int $id = 1): object
    {
        // Note: In a real application, you would use your actual User model
        return new class($id) {
            public $id;
            public $workspace_id = 1;

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
        // Note: In a real application, you would use your actual Workspace model
        return new class($id) {
            public $id;

            public function __construct($id)
            {
                $this->id = $id;
            }
        };
    }
}
