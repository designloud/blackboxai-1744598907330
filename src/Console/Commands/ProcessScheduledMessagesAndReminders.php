<?php

namespace SwellSystems\Conversa\Console\Commands;

use Illuminate\Console\Command;
use SwellSystems\Conversa\Models\ConversaScheduledMessage;
use SwellSystems\Conversa\Models\ConversaReminder;
use SwellSystems\Conversa\Events\ReminderDue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessScheduledMessagesAndReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversa:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled messages and reminders';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->processScheduledMessages();
        $this->processReminders();
    }

    /**
     * Process scheduled messages that are due.
     */
    protected function processScheduledMessages(): void
    {
        $messages = ConversaScheduledMessage::dueForSending()->get();

        foreach ($messages as $scheduledMessage) {
            try {
                $message = $scheduledMessage->send();

                if ($message) {
                    $this->info("Sent scheduled message #{$scheduledMessage->id}");
                    Log::info("Sent scheduled message #{$scheduledMessage->id}", [
                        'message_id' => $message->id,
                        'workspace_id' => $message->workspace_id,
                        'user_id' => $message->user_id,
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to send scheduled message #{$scheduledMessage->id}: {$e->getMessage()}");
                Log::error("Failed to send scheduled message #{$scheduledMessage->id}", [
                    'error' => $e->getMessage(),
                    'scheduled_message_id' => $scheduledMessage->id,
                ]);
            }
        }

        $this->info("Processed {$messages->count()} scheduled messages");
    }

    /**
     * Process reminders that are due.
     */
    protected function processReminders(): void
    {
        $reminders = ConversaReminder::pending()->get();

        foreach ($reminders as $reminder) {
            try {
                // Dispatch reminder event
                event(new ReminderDue($reminder));

                // Mark as reminded
                $reminder->markAsReminded();

                $this->info("Processed reminder #{$reminder->id}");
                Log::info("Processed reminder #{$reminder->id}", [
                    'message_id' => $reminder->message_id,
                    'user_id' => $reminder->user_id,
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to process reminder #{$reminder->id}: {$e->getMessage()}");
                Log::error("Failed to process reminder #{$reminder->id}", [
                    'error' => $e->getMessage(),
                    'reminder_id' => $reminder->id,
                ]);
            }
        }

        $this->info("Processed {$reminders->count()} reminders");
    }

    /**
     * Get the scheduler expression for the command.
     */
    public function scheduleInterval(): string
    {
        return '* * * * *'; // Run every minute
    }
}
