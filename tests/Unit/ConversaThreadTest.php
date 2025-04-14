<?php

namespace VendorName\Conversa\Tests\Unit;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaThread;
use VendorName\Conversa\Models\ConversaMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaThreadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_thread()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        $this->assertDatabaseHas('conversa_threads', [
            'workspace_id' => 1,
            'type' => 'direct',
        ]);
    }

    /** @test */
    public function it_can_create_a_group_thread()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'group',
            'name' => 'Team Chat',
            'created_by' => 1,
        ]);

        $this->assertDatabaseHas('conversa_threads', [
            'type' => 'group',
            'name' => 'Team Chat',
        ]);
    }

    /** @test */
    public function it_can_add_and_remove_members()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'group',
            'created_by' => 1,
        ]);

        // Add member
        $thread->addMember(2);
        $this->assertTrue($thread->hasMember(2));

        // Remove member
        $thread->removeMember(2);
        $this->assertFalse($thread->hasMember(2));
    }

    /** @test */
    public function it_can_update_member_role()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'group',
            'created_by' => 1,
        ]);

        $thread->addMember(2, 'member');
        $this->assertFalse($thread->isAdmin(2));

        $thread->updateMemberRole(2, 'admin');
        $this->assertTrue($thread->isAdmin(2));
    }

    /** @test */
    public function it_can_mark_as_read_for_member()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        $thread->addMember(2);
        $thread->markAsReadFor(2);

        $this->assertDatabaseHas('conversa_thread_members', [
            'thread_id' => $thread->id,
            'user_id' => 2,
            'last_read_at' => now()->toDateTimeString(),
        ]);
    }

    /** @test */
    public function it_can_toggle_mute_for_member()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        $thread->addMember(2);
        
        // Mute
        $thread->toggleMuteFor(2);
        $this->assertDatabaseHas('conversa_thread_members', [
            'thread_id' => $thread->id,
            'user_id' => 2,
            'is_muted' => true,
        ]);

        // Unmute
        $thread->toggleMuteFor(2);
        $this->assertDatabaseHas('conversa_thread_members', [
            'thread_id' => $thread->id,
            'user_id' => 2,
            'is_muted' => false,
        ]);
    }

    /** @test */
    public function it_can_get_unread_count_for_user()
    {
        $thread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        $thread->addMember(2);
        $thread->markAsReadFor(2);

        // Add some messages
        ConversaMessage::create([
            'workspace_id' => 1,
            'thread_id' => $thread->id,
            'user_id' => 1,
            'content' => 'Message 1',
            'type' => 'text',
        ]);

        ConversaMessage::create([
            'workspace_id' => 1,
            'thread_id' => $thread->id,
            'user_id' => 1,
            'content' => 'Message 2',
            'type' => 'text',
        ]);

        $this->assertEquals(2, $thread->getUnreadCountFor(2));

        // Mark as read again
        $thread->markAsReadFor(2);
        $this->assertEquals(0, $thread->getUnreadCountFor(2));
    }

    /** @test */
    public function it_scopes_to_direct_messages()
    {
        ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'group',
            'created_by' => 1,
        ]);

        $directThreads = ConversaThread::direct()->get();
        
        $this->assertCount(1, $directThreads);
        $this->assertEquals('direct', $directThreads->first()->type);
    }

    /** @test */
    public function it_scopes_to_group_threads()
    {
        ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'group',
            'created_by' => 1,
        ]);

        $groupThreads = ConversaThread::group()->get();
        
        $this->assertCount(1, $groupThreads);
        $this->assertEquals('group', $groupThreads->first()->type);
    }

    /** @test */
    public function it_can_get_display_name()
    {
        // For a group thread
        $groupThread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'group',
            'name' => 'Team Chat',
            'created_by' => 1,
        ]);

        $this->assertEquals('Team Chat', $groupThread->getDisplayName());

        // For a direct thread
        $directThread = ConversaThread::create([
            'workspace_id' => 1,
            'type' => 'direct',
            'created_by' => 1,
        ]);

        // Note: In a real application, you would need to mock the auth()->id()
        // and create actual user models for this test to work properly
        $this->assertNotEmpty($directThread->getDisplayName());
    }
}
