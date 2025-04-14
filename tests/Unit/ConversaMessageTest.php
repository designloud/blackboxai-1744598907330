<?php

namespace VendorName\Conversa\Tests\Unit;

use VendorName\Conversa\Tests\TestCase;
use VendorName\Conversa\Models\ConversaMessage;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Models\ConversaThread;
use VendorName\Conversa\Models\ConversaReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_message()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Hello, world!',
            'type' => 'text',
        ]);

        $this->assertDatabaseHas('conversa_messages', [
            'content' => 'Hello, world!',
            'type' => 'text',
        ]);
    }

    /** @test */
    public function it_can_create_a_reply_to_a_message()
    {
        $parentMessage = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Parent message',
            'type' => 'text',
        ]);

        $reply = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 2,
            'parent_id' => $parentMessage->id,
            'content' => 'Reply message',
            'type' => 'text',
        ]);

        $this->assertEquals($parentMessage->id, $reply->parent_id);
        $this->assertTrue($parentMessage->replies()->exists());
    }

    /** @test */
    public function it_can_add_and_remove_reactions()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        // Add reaction
        $message->addReaction(2, '👍');
        $this->assertTrue($message->reactions()->where('user_id', 2)->exists());

        // Remove reaction
        $message->removeReaction(2, '👍');
        $this->assertFalse($message->reactions()->where('user_id', 2)->exists());
    }

    /** @test */
    public function it_can_get_reaction_counts()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        // Add multiple reactions
        $message->addReaction(2, '👍');
        $message->addReaction(3, '👍');
        $message->addReaction(4, '❤️');

        $counts = $message->getReactionCounts();

        $this->assertEquals([
            '👍' => 2,
            '❤️' => 1,
        ], $counts);
    }

    /** @test */
    public function it_can_parse_mentions()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Hello @john and @jane!',
            'type' => 'text',
        ]);

        $mentions = $message->parseMentions();

        $this->assertEquals(['john', 'jane'], $mentions);
    }

    /** @test */
    public function it_can_determine_if_message_is_editable()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        // Message should be editable within the time limit
        $this->assertTrue($message->isEditable());

        // Simulate message being older than edit timeout
        $message->created_at = now()->subMinutes(10);
        $message->save();

        // Message should not be editable after timeout
        $this->assertFalse($message->isEditable());
    }

    /** @test */
    public function it_tracks_edited_status()
    {
        $message = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
            'type' => 'text',
        ]);

        $this->assertFalse($message->hasBeenEdited());

        $message->markAsEdited();

        $this->assertTrue($message->hasBeenEdited());
        $this->assertNotNull($message->edited_at);
    }

    /** @test */
    public function it_scopes_to_root_messages()
    {
        // Create root message
        $rootMessage = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Root message',
            'type' => 'text',
        ]);

        // Create reply
        $reply = ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 2,
            'parent_id' => $rootMessage->id,
            'content' => 'Reply message',
            'type' => 'text',
        ]);

        $rootMessages = ConversaMessage::rootMessages()->get();

        $this->assertCount(1, $rootMessages);
        $this->assertEquals('Root message', $rootMessages->first()->content);
    }

    /** @test */
    public function it_scopes_messages_by_type()
    {
        ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Text message',
            'type' => 'text',
        ]);

        ConversaMessage::create([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'System message',
            'type' => 'system',
        ]);

        $textMessages = ConversaMessage::ofType('text')->get();
        $systemMessages = ConversaMessage::ofType('system')->get();

        $this->assertCount(1, $textMessages);
        $this->assertCount(1, $systemMessages);
        $this->assertEquals('Text message', $textMessages->first()->content);
        $this->assertEquals('System message', $systemMessages->first()->content);
    }
}
