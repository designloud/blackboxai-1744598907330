<?php

namespace VendorName\Conversa\Tests\Unit;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaReaction;
use VendorName\Conversa\Models\ConversaMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaReactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_reaction()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        $reaction = ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '👍',
        ]);

        $this->assertDatabaseHas('conversa_reactions', [
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '👍',
        ]);
    }

    /** @test */
    public function it_can_check_if_user_has_reacted()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '👍',
        ]);

        $this->assertTrue(ConversaReaction::hasUserReacted($message->id, 2, '👍'));
        $this->assertFalse(ConversaReaction::hasUserReacted($message->id, 2, '❤️'));
        $this->assertFalse(ConversaReaction::hasUserReacted($message->id, 3, '👍'));
    }

    /** @test */
    public function it_can_toggle_reaction()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        // Add reaction
        $added = ConversaReaction::toggle($message->id, 2, '👍');
        $this->assertTrue($added);
        $this->assertTrue(ConversaReaction::hasUserReacted($message->id, 2, '👍'));

        // Remove reaction
        $removed = ConversaReaction::toggle($message->id, 2, '👍');
        $this->assertFalse($removed);
        $this->assertFalse(ConversaReaction::hasUserReacted($message->id, 2, '👍'));
    }

    /** @test */
    public function it_can_get_reaction_counts_for_message()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '👍',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 3,
            'reaction' => '👍',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 4,
            'reaction' => '❤️',
        ]);

        $counts = ConversaReaction::getCountsForMessage($message->id);

        $this->assertEquals([
            '👍' => 2,
            '❤️' => 1,
        ], $counts);
    }

    /** @test */
    public function it_can_get_users_for_reaction()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        // Create test users (in a real app, you'd use factories)
        $users = [
            ['id' => 2, 'name' => 'John'],
            ['id' => 3, 'name' => 'Jane'],
        ];

        foreach ($users as $user) {
            ConversaReaction::create([
                'message_id' => $message->id,
                'user_id' => $user['id'],
                'reaction' => '👍',
            ]);
        }

        $reactedUsers = ConversaReaction::getUsersForReaction($message->id, '👍');

        $this->assertCount(2, $reactedUsers);
        // Note: In a real test with actual user models, you'd verify the exact names
    }

    /** @test */
    public function it_scopes_reactions_by_type()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '👍',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 3,
            'reaction' => '❤️',
        ]);

        $thumbsUpReactions = ConversaReaction::ofType('👍')->get();
        $heartReactions = ConversaReaction::ofType('❤️')->get();

        $this->assertCount(1, $thumbsUpReactions);
        $this->assertCount(1, $heartReactions);
    }

    /** @test */
    public function it_scopes_reactions_by_user()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '👍',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 2,
            'reaction' => '❤️',
        ]);

        ConversaReaction::create([
            'message_id' => $message->id,
            'user_id' => 3,
            'reaction' => '👍',
        ]);

        $user2Reactions = ConversaReaction::byUser(2)->get();
        $user3Reactions = ConversaReaction::byUser(3)->get();

        $this->assertCount(2, $user2Reactions);
        $this->assertCount(1, $user3Reactions);
    }
}
