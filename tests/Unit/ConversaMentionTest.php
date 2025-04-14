<?php

namespace SwellSystems\Conversa\Tests\Unit;

use SwellSystems\Conversa\Tests\TestCase;
use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaMention;
use SwellSystems\Conversa\Models\ConversaSpace;
use SwellSystems\Conversa\Events\UserMentioned;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaMentionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_user_mentions_from_message_content()
    {
        Event::fake();

        $message = ConversaMessage::factory()->create([
            'content' => 'Hey @john and @jane, please check this out!',
        ]);

        $this->assertEquals(2, $message->mentions_count);
        $this->assertEquals(2, $message->mentions()->count());

        Event::assertDispatched(UserMentioned::class, 2);
    }

    /** @test */
    public function it_can_create_channel_mentions_from_message_content()
    {
        $space = ConversaSpace::factory()->create(['name' => 'general']);

        $message = ConversaMessage::factory()->create([
            'content' => 'Please check #general for updates',
        ]);

        $this->assertEquals(1, $message->mentions_count);
        $this->assertEquals(1, $message->mentions()->where('mentionable_type', ConversaSpace::class)->count());
    }

    /** @test */
    public function it_updates_mentions_when_message_content_is_updated()
    {
        $message = ConversaMessage::factory()->create([
            'content' => 'Hey @john!',
        ]);

        $this->assertEquals(1, $message->mentions_count);

        $message->update([
            'content' => 'Hey @john and @jane!',
        ]);

        $this->assertEquals(2, $message->fresh()->mentions_count);
    }

    /** @test */
    public function it_can_mark_mention_as_read()
    {
        $mention = ConversaMention::factory()->create([
            'read_at' => null,
        ]);

        $mention->markAsRead();

        $this->assertNotNull($mention->fresh()->read_at);
    }

    /** @test */
    public function it_can_get_unread_mentions_for_user()
    {
        $userId = 1;
        $workspaceId = 1;

        // Create read and unread mentions
        ConversaMention::factory()->count(3)->forUser($userId)->read()->create([
            'workspace_id' => $workspaceId,
        ]);

        ConversaMention::factory()->count(2)->forUser($userId)->unread()->create([
            'workspace_id' => $workspaceId,
        ]);

        $unreadCount = ConversaMention::getUnreadCountForUser($userId, $workspaceId);
        $this->assertEquals(2, $unreadCount);
    }

    /** @test */
    public function it_can_get_recent_mentions_for_user()
    {
        $userId = 1;
        $workspaceId = 1;

        ConversaMention::factory()->count(5)->forUser($userId)->create([
            'workspace_id' => $workspaceId,
        ]);

        $recentMentions = ConversaMention::getRecentForUser($userId, $workspaceId, 3);
        $this->assertCount(3, $recentMentions);
    }

    /** @test */
    public function it_can_mark_all_mentions_as_read()
    {
        $userId = 1;
        $workspaceId = 1;

        ConversaMention::factory()->count(3)->forUser($userId)->unread()->create([
            'workspace_id' => $workspaceId,
        ]);

        ConversaMention::markAllAsRead($userId, $workspaceId);

        $unreadCount = ConversaMention::getUnreadCountForUser($userId, $workspaceId);
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_dispatches_event_when_user_is_mentioned()
    {
        Event::fake();

        $mention = ConversaMention::factory()->forUser()->create();

        Event::assertDispatched(UserMentioned::class, function ($event) use ($mention) {
            return $event->mention->id === $mention->id;
        });
    }

    /** @test */
    public function it_respects_mention_notification_preferences()
    {
        $threadId = 1;
        $userId = 1;

        // Create thread settings with mentions disabled
        $threadSettings = ConversaThreadSetting::factory()->create([
            'thread_id' => $threadId,
            'user_id' => $userId,
            'mention_notifications_enabled' => false,
        ]);

        $message = ConversaMessage::factory()->create([
            'thread_id' => $threadId,
            'content' => "Hey @user{$userId}, check this out!",
        ]);

        $mention = $message->mentions()->where('mentionable_id', $userId)->first();

        $this->assertNull($mention->notified_at);
    }

    /** @test */
    public function it_can_handle_multiple_mentions_of_same_user()
    {
        $message = ConversaMessage::factory()->create([
            'content' => 'Hey @john! @john please respond!',
        ]);

        // Should only create one mention record
        $this->assertEquals(1, $message->mentions_count);
    }

    /** @test */
    public function it_can_handle_invalid_mentions()
    {
        $message = ConversaMessage::factory()->create([
            'content' => 'Hey @nonexistentuser and #nonexistentchannel!',
        ]);

        $this->assertEquals(0, $message->mentions_count);
    }

    /** @test */
    public function it_properly_scopes_mentions_queries()
    {
        $userId = 1;
        
        ConversaMention::factory()->count(2)->forUser($userId)->read()->create();
        ConversaMention::factory()->count(3)->forUser($userId)->unread()->create();
        ConversaMention::factory()->count(2)->forChannel()->create();

        $this->assertEquals(2, ConversaMention::read()->count());
        $this->assertEquals(3, ConversaMention::unread()->count());
        $this->assertEquals(5, ConversaMention::forUser($userId)->count());
    }
}
