<?php

namespace SwellSystems\Conversa\Tests\Unit;

use SwellSystems\Conversa\Tests\TestCase;
use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaReminder;
use SwellSystems\Conversa\Models\ConversaScheduledMessage;
use SwellSystems\Conversa\Events\ReminderDue;
use SwellSystems\Conversa\Events\MessageSent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversaReminderAndScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_reminder_with_natural_language()
    {
        $message = ConversaMessage::factory()->create();
        $userId = 1;

        $reminder = ConversaReminder::createFromText(
            'Remind me tomorrow at 9am',
            $message->id,
            $userId,
            $message->workspace_id
        );

        $this->assertNotNull($reminder);
        $this->assertEquals('pending', $reminder->status);
        $this->assertEquals(
            now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d H:i'),
            $reminder->remind_at->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_can_snooze_reminder()
    {
        $reminder = ConversaReminder::factory()->create();
        $originalTime = $reminder->remind_at;

        $reminder->snooze('3h');

        $this->assertTrue(
            $reminder->fresh()->remind_at->equalTo(now()->addHours(3))
        );
        $this->assertNotEquals($originalTime, $reminder->fresh()->remind_at);
    }

    /** @test */
    public function it_can_mark_reminder_as_completed()
    {
        $reminder = ConversaReminder::factory()->create();

        $reminder->markAsReminded();

        $this->assertEquals('completed', $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->reminded_at);
    }

    /** @test */
    public function it_can_schedule_message_with_natural_language()
    {
        $scheduled = ConversaScheduledMessage::scheduleFromText([
            'workspace_id' => 1,
            'space_id' => 1,
            'user_id' => 1,
            'content' => 'Test message',
        ], 'tomorrow at 9am');

        $this->assertNotNull($scheduled);
        $this->assertEquals('pending', $scheduled->status);
        $this->assertEquals(
            now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d H:i'),
            $scheduled->scheduled_for->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_can_reschedule_message()
    {
        $scheduled = ConversaScheduledMessage::factory()->create();
        $originalTime = $scheduled->scheduled_for;

        $scheduled->reschedule('next monday at 10am');

        $this->assertNotEquals($originalTime, $scheduled->fresh()->scheduled_for);
    }

    /** @test */
    public function it_can_cancel_scheduled_message()
    {
        $scheduled = ConversaScheduledMessage::factory()->create();

        $scheduled->cancel();

        $this->assertEquals('cancelled', $scheduled->fresh()->status);
    }

    /** @test */
    public function it_sends_scheduled_message_when_due()
    {
        Event::fake();

        $scheduled = ConversaScheduledMessage::factory()->due()->create();
        
        $message = $scheduled->send();

        $this->assertNotNull($message);
        $this->assertEquals('sent', $scheduled->fresh()->status);
        $this->assertNotNull($scheduled->fresh()->sent_at);
        
        Event::assertDispatched(MessageSent::class);
    }

    /** @test */
    public function it_dispatches_event_when_reminder_is_due()
    {
        Event::fake();

        $reminder = ConversaReminder::factory()->due()->create();

        event(new ReminderDue($reminder));

        Event::assertDispatched(ReminderDue::class);
    }

    /** @test */
    public function it_can_get_upcoming_reminders_for_user()
    {
        $userId = 1;
        
        // Create some reminders
        ConversaReminder::factory()->count(3)->upcoming()->create([
            'user_id' => $userId,
        ]);
        
        ConversaReminder::factory()->count(2)->completed()->create([
            'user_id' => $userId,
        ]);

        $upcoming = ConversaReminder::getUpcomingForUser($userId);
        
        $this->assertCount(3, $upcoming);
    }

    /** @test */
    public function it_can_get_upcoming_scheduled_messages_for_user()
    {
        $userId = 1;
        
        // Create some scheduled messages
        ConversaScheduledMessage::factory()->count(3)->upcoming()->create([
            'user_id' => $userId,
        ]);
        
        ConversaScheduledMessage::factory()->count(2)->sent()->create([
            'user_id' => $userId,
        ]);

        $upcoming = ConversaScheduledMessage::getUpcomingForUser($userId);
        
        $this->assertCount(3, $upcoming);
    }

    /** @test */
    public function it_processes_due_reminders_and_scheduled_messages()
    {
        Event::fake();

        // Create due reminders and scheduled messages
        ConversaReminder::factory()->count(2)->due()->create();
        ConversaScheduledMessage::factory()->count(2)->due()->create();

        $this->artisan('conversa:process-scheduled');

        Event::assertDispatched(ReminderDue::class, 2);
        Event::assertDispatched(MessageSent::class, 2);
    }

    /** @test */
    public function it_can_set_reminder_from_message()
    {
        $message = ConversaMessage::factory()->create();
        
        $reminder = $message->setReminder(1, 'Check this tomorrow');

        $this->assertNotNull($reminder);
        $this->assertEquals($message->id, $reminder->message_id);
        $this->assertEquals('pending', $reminder->status);
    }

    /** @test */
    public function it_can_schedule_existing_message_for_later()
    {
        $message = ConversaMessage::factory()->create();
        
        $scheduled = $message->scheduleFor('tomorrow at 9am');

        $this->assertNotNull($scheduled);
        $this->assertEquals($message->content, $scheduled->content);
        $this->assertEquals('pending', $scheduled->status);
    }
}
