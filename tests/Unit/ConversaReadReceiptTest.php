<?php

namespace SwellSystems\Conversa\Tests\Unit;

use SwellSystems\Conversa\Tests\TestCase;
use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaReadReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaReadReceiptTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_mark_a_message_as_read()
    {
        $message = ConversaMessage::factory()->create();
        $userId = 1;

        $receipt = ConversaReadReceipt::factory()->create([
            'message_id' => $message->id,
            'user_id' => $userId,
            'read_at' => null,
        ]);

        $receipt->markAsRead();

        $this->assertNotNull($receipt->fresh()->read_at);
        $this->assertTrue($receipt->fresh()->isRead());
    }

    /** @test */
    public function it_can_mark_a_message_as_unread()
    {
        $message = ConversaMessage::factory()->create();
        $userId = 1;

        $receipt = ConversaReadReceipt::factory()->create([
            'message_id' => $message->id,
            'user_id' => $userId,
            'read_at' => now(),
        ]);

        $receipt->markAsUnread();

        $this->assertNull($receipt->fresh()->read_at);
        $this->assertFalse($receipt->fresh()->isRead());
    }

    /** @test */
    public function it_updates_message_read_counts_when_marked_as_read()
    {
        $message = ConversaMessage::factory()->create([
            'read_count' => 0,
            'unread_count' => 1,
        ]);

        $receipt = ConversaReadReceipt::factory()->create([
            'message_id' => $message->id,
            'user_id' => 1,
            'read_at' => null,
        ]);

        $receipt->markAsRead();
        $message->refresh();

        $this->assertEquals(1, $message->read_count);
        $this->assertEquals(0, $message->unread_count);
    }

    /** @test */
    public function it_prevents_duplicate_read_receipts()
    {
        $message = ConversaMessage::factory()->create();
        $userId = 1;

        ConversaReadReceipt::factory()->create([
            'message_id' => $message->id,
            'user_id' => $userId,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ConversaReadReceipt::factory()->create([
            'message_id' => $message->id,
            'user_id' => $userId,
        ]);
    }

    /** @test */
    public function it_can_get_unread_messages_for_user()
    {
        $userId = 1;
        $workspaceId = 1;

        // Create some read and unread messages
        $readMessage = ConversaMessage::factory()->create(['workspace_id' => $workspaceId]);
        $unreadMessage = ConversaMessage::factory()->create(['workspace_id' => $workspaceId]);

        ConversaReadReceipt::factory()->create([
            'message_id' => $readMessage->id,
            'user_id' => $userId,
            'read_at' => now(),
        ]);

        ConversaReadReceipt::factory()->create([
            'message_id' => $unreadMessage->id,
            'user_id' => $userId,
            'read_at' => null,
        ]);

        $unreadMessages = ConversaReadReceipt::getUnreadMessagesForUser($userId, $workspaceId);

        $this->assertCount(1, $unreadMessages);
        $this->assertEquals($unreadMessage->id, $unreadMessages[0]['id']);
    }

    /** @test */
    public function it_can_get_unread_count_for_user()
    {
        $userId = 1;
        $workspaceId = 1;

        // Create multiple read and unread messages
        ConversaMessage::factory()->count(3)->create(['workspace_id' => $workspaceId])
            ->each(function ($message) use ($userId) {
                ConversaReadReceipt::factory()->create([
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'read_at' => now(),
                ]);
            });

        ConversaMessage::factory()->count(2)->create(['workspace_id' => $workspaceId])
            ->each(function ($message) use ($userId) {
                ConversaReadReceipt::factory()->create([
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'read_at' => null,
                ]);
            });

        $unreadCount = ConversaReadReceipt::getUnreadCountForUser($userId, $workspaceId);

        $this->assertEquals(2, $unreadCount);
    }

    /** @test */
    public function it_can_mark_all_messages_as_read_in_a_space()
    {
        $userId = 1;
        $spaceId = 1;

        // Create multiple unread messages in a space
        ConversaMessage::factory()->count(3)->create([
            'space_id' => $spaceId,
        ])->each(function ($message) use ($userId) {
            ConversaReadReceipt::factory()->create([
                'message_id' => $message->id,
                'user_id' => $userId,
                'read_at' => null,
            ]);
        });

        ConversaReadReceipt::markAllAsRead($userId, ['space_id' => $spaceId]);

        $unreadCount = ConversaReadReceipt::getUnreadCountForUser($userId);
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_can_mark_all_messages_as_read_in_a_thread()
    {
        $userId = 1;
        $threadId = 1;

        // Create multiple unread messages in a thread
        ConversaMessage::factory()->count(3)->create([
            'thread_id' => $threadId,
        ])->each(function ($message) use ($userId) {
            ConversaReadReceipt::factory()->create([
                'message_id' => $message->id,
                'user_id' => $userId,
                'read_at' => null,
            ]);
        });

        ConversaReadReceipt::markAllAsRead($userId, ['thread_id' => $threadId]);

        $unreadCount = ConversaReadReceipt::getUnreadCountForUser($userId);
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_scopes_read_receipts_correctly()
    {
        $userId = 1;
        
        // Create read and unread receipts
        ConversaReadReceipt::factory()->count(2)->read()->create(['user_id' => $userId]);
        ConversaReadReceipt::factory()->count(3)->unread()->create(['user_id' => $userId]);

        $readCount = ConversaReadReceipt::read()->count();
        $unreadCount = ConversaReadReceipt::unread()->count();
        $userReceiptsCount = ConversaReadReceipt::forUser($userId)->count();

        $this->assertEquals(2, $readCount);
        $this->assertEquals(3, $unreadCount);
        $this->assertEquals(5, $userReceiptsCount);
    }

    /** @test */
    public function it_calculates_time_elapsed_correctly()
    {
        $receipt = ConversaReadReceipt::factory()->create([
            'read_at' => now()->subHours(2),
        ]);

        $this->assertEquals('2 hours ago', $receipt->time_elapsed);
    }
}
