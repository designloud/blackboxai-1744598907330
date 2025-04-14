<?php

namespace VendorName\Conversa\Tests\Unit;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Models\ConversaMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaSpaceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_space()
    {
        $space = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'General',
            'description' => 'General discussion',
            'type' => 'public',
            'created_by' => 1,
        ]);

        $this->assertDatabaseHas('conversa_spaces', [
            'name' => 'General',
            'type' => 'public',
        ]);
    }

    /** @test */
    public function it_generates_a_slug_when_creating_a_space()
    {
        $space = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'General Discussion',
            'type' => 'public',
            'created_by' => 1,
        ]);

        $this->assertEquals('general-discussion', $space->slug);
    }

    /** @test */
    public function it_can_determine_if_space_is_public_or_private()
    {
        $publicSpace = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Public Space',
            'type' => 'public',
            'created_by' => 1,
        ]);

        $privateSpace = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Private Space',
            'type' => 'private',
            'created_by' => 1,
        ]);

        $this->assertTrue($publicSpace->isPublic());
        $this->assertFalse($publicSpace->isPrivate());
        $this->assertTrue($privateSpace->isPrivate());
        $this->assertFalse($privateSpace->isPublic());
    }

    /** @test */
    public function it_can_add_and_remove_members()
    {
        $space = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Team Space',
            'type' => 'private',
            'created_by' => 1,
        ]);

        // Add member
        $space->addMember(2, 'member');
        $this->assertTrue($space->hasMember(2));

        // Remove member
        $space->removeMember(2);
        $this->assertFalse($space->hasMember(2));
    }

    /** @test */
    public function it_can_update_member_role()
    {
        $space = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Team Space',
            'type' => 'private',
            'created_by' => 1,
        ]);

        // Add member with initial role
        $space->addMember(2, 'member');
        $this->assertFalse($space->isAdmin(2));

        // Update to admin role
        $space->updateMemberRole(2, 'admin');
        $this->assertTrue($space->isAdmin(2));
    }

    /** @test */
    public function it_can_mark_as_read_for_member()
    {
        $space = ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Team Space',
            'type' => 'private',
            'created_by' => 1,
        ]);

        $space->addMember(2, 'member');
        $space->markAsReadFor(2);

        $this->assertDatabaseHas('conversa_space_members', [
            'space_id' => $space->id,
            'user_id' => 2,
            'last_read_at' => now()->toDateTimeString(),
        ]);
    }

    /** @test */
    public function it_scopes_to_public_spaces()
    {
        ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Public Space 1',
            'type' => 'public',
            'created_by' => 1,
        ]);

        ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Private Space',
            'type' => 'private',
            'created_by' => 1,
        ]);

        ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Public Space 2',
            'type' => 'public',
            'created_by' => 1,
        ]);

        $publicSpaces = ConversaSpace::public()->get();

        $this->assertCount(2, $publicSpaces);
        $this->assertEquals(['Public Space 1', 'Public Space 2'], $publicSpaces->pluck('name')->toArray());
    }

    /** @test */
    public function it_scopes_to_private_spaces()
    {
        ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Public Space',
            'type' => 'public',
            'created_by' => 1,
        ]);

        ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Private Space 1',
            'type' => 'private',
            'created_by' => 1,
        ]);

        ConversaSpace::create([
            'workspace_id' => 1,
            'name' => 'Private Space 2',
            'type' => 'private',
            'created_by' => 1,
        ]);

        $privateSpaces = ConversaSpace::private()->get();

        $this->assertCount(2, $privateSpaces);
        $this->assertEquals(['Private Space 1', 'Private Space 2'], $privateSpaces->pluck('name')->toArray());
    }
}
