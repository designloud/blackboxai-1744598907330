<?php

namespace VendorName\Conversa\Tests\Feature;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaSpace;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaChannelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and workspace
        // Note: In a real application, you would use your actual User and Workspace models
        $this->user = $this->createTestUser();
        $this->workspace = $this->createTestWorkspace();
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_list_spaces()
    {
        // Create some test spaces
        ConversaSpace::factory()->count(3)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        $response = $this->get(route('conversa.spaces.index'));

        $response->assertStatus(200)
            ->assertViewIs('conversa::spaces.index')
            ->assertViewHas('spaces');
    }

    /** @test */
    public function it_can_show_space_details()
    {
        $space = ConversaSpace::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('conversa.spaces.show', $space));

        $response->assertStatus(200)
            ->assertViewIs('conversa::spaces.show')
            ->assertViewHas('space');
    }

    /** @test */
    public function it_can_create_a_space()
    {
        $spaceData = [
            'name' => 'Test Channel',
            'description' => 'Test Description',
            'type' => 'public',
        ];

        $response = $this->post(route('conversa.spaces.store'), $spaceData);

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.spaces.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('conversa_spaces', [
            'name' => 'Test Channel',
            'description' => 'Test Description',
            'type' => 'public',
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_a_space()
    {
        $response = $this->post(route('conversa.spaces.store'), []);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name', 'type']);
    }

    /** @test */
    public function it_can_update_a_space()
    {
        $space = ConversaSpace::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Channel',
            'description' => 'Updated Description',
            'type' => 'private',
        ];

        $response = $this->put(route('conversa.spaces.update', $space), $updateData);

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.spaces.show', $space))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('conversa_spaces', [
            'id' => $space->id,
            'name' => 'Updated Channel',
            'description' => 'Updated Description',
            'type' => 'private',
        ]);
    }

    /** @test */
    public function it_can_delete_a_space()
    {
        $space = ConversaSpace::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->delete(route('conversa.spaces.destroy', $space));

        $response->assertStatus(302)
            ->assertRedirect(route('conversa.spaces.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('conversa_spaces', [
            'id' => $space->id,
        ]);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_updating_spaces()
    {
        $otherUser = $this->createTestUser();
        $space = ConversaSpace::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $otherUser->id,
        ]);

        $updateData = [
            'name' => 'Updated Channel',
            'description' => 'Updated Description',
            'type' => 'private',
        ];

        $response = $this->put(route('conversa.spaces.update', $space), $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_unauthorized_users_from_deleting_spaces()
    {
        $otherUser = $this->createTestUser();
        $space = ConversaSpace::factory()->create([
            'workspace_id' => $this->workspace->id,
            'created_by' => $otherUser->id,
        ]);

        $response = $this->delete(route('conversa.spaces.destroy', $space));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_scopes_spaces_to_current_workspace()
    {
        // Create spaces in current workspace
        ConversaSpace::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
        ]);

        // Create spaces in another workspace
        ConversaSpace::factory()->count(3)->create([
            'workspace_id' => $this->createTestWorkspace()->id,
        ]);

        $response = $this->get(route('conversa.spaces.index'));
        $spaces = $response->viewData('spaces');

        $this->assertCount(2, $spaces);
        $this->assertEquals($this->workspace->id, $spaces->first()->workspace_id);
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
