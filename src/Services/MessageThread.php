<?php

namespace SwellSystems\Conversa\Services;

use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaThread;
use SwellSystems\Conversa\Events\MessageSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class MessageThread
{
    /**
     * The encryption service instance.
     *
     * @var \SwellSystems\Conversa\Services\MessageEncryption
     */
    protected $encryption;

    /**
     * Create a new message thread service instance.
     */
    public function __construct(MessageEncryption $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Create a reply to a message.
     *
     * @param array $data Reply data
     * @param ConversaMessage $parentMessage Parent message
     * @return ConversaMessage
     */
    public function createReply(array $data, ConversaMessage $parentMessage): ConversaMessage
    {
        // Ensure the reply inherits the parent's context
        $data['workspace_id'] = $parentMessage->workspace_id;
        $data['space_id'] = $parentMessage->space_id;
        $data['thread_id'] = $parentMessage->thread_id;
        $data['parent_id'] = $parentMessage->id;

        // Encrypt message content if enabled
        if (config('conversa.encryption.enabled', false)) {
            $data = $this->encryption->encrypt($data);
        }

        // Create the reply
        $reply = ConversaMessage::create($data);

        // Update parent message's reply count and last reply timestamp
        $parentMessage->updateQuietly([
            'reply_count' => $parentMessage->replies()->count(),
            'last_reply_at' => now(),
        ]);

        // Broadcast the new reply
        event(new MessageSent($reply));

        return $reply;
    }

    /**
     * Get thread messages with proper ordering and pagination.
     *
     * @param ConversaMessage $parentMessage
     * @param int $perPage
     * @param string|null $cursor
     * @return array
     */
    public function getThreadMessages(ConversaMessage $parentMessage, int $perPage = 50, ?string $cursor = null): array
    {
        $query = $parentMessage->replies()
            ->with(['user', 'attachments', 'reactions'])
            ->orderBy('created_at', 'desc');

        if ($cursor) {
            $query->where('created_at', '<', base64_decode($cursor));
        }

        $messages = $query->take($perPage + 1)->get();

        $hasMore = $messages->count() > $perPage;
        if ($hasMore) {
            $messages->pop();
        }

        $nextCursor = $hasMore ? base64_encode($messages->last()->created_at) : null;

        // Decrypt messages if encryption is enabled
        if (config('conversa.encryption.enabled', false)) {
            $messages->transform(function ($message) {
                $message->decryptContent();
                return $message;
            });
        }

        return [
            'messages' => $messages,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
        ];
    }

    /**
     * Get messages with their threads.
     *
     * @param Builder $query
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getMessagesWithThreads(Builder $query, int $perPage = 20)
    {
        return $query->with([
            'user',
            'attachments',
            'reactions',
            'replies' => function ($query) {
                $query->with(['user', 'attachments', 'reactions'])
                    ->latest()
                    ->take(3);
            },
        ])
        ->withCount('replies')
        ->orderBy('last_reply_at', 'desc')
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    /**
     * Convert a message to a thread.
     *
     * @param ConversaMessage $message
     * @return ConversaThread
     */
    public function convertToThread(ConversaMessage $message): ConversaThread
    {
        return DB::transaction(function () use ($message) {
            // Create a new thread
            $thread = ConversaThread::create([
                'workspace_id' => $message->workspace_id,
                'type' => 'message_thread',
                'created_by' => $message->user_id,
            ]);

            // Move the message and its replies to the thread
            $message->thread_id = $thread->id;
            $message->space_id = null;
            $message->save();

            $message->replies()->update([
                'thread_id' => $thread->id,
                'space_id' => null,
            ]);

            // Add participants to the thread
            $participantIds = $message->replies()
                ->pluck('user_id')
                ->merge([$message->user_id])
                ->unique()
                ->values();

            foreach ($participantIds as $userId) {
                $thread->addMember($userId);
            }

            return $thread;
        });
    }

    /**
     * Get thread participants.
     *
     * @param ConversaMessage $message
     * @return Collection
     */
    public function getThreadParticipants(ConversaMessage $message): Collection
    {
        return $message->replies()
            ->with('user')
            ->select('user_id')
            ->distinct()
            ->get()
            ->pluck('user')
            ->merge([$message->user])
            ->unique('id');
    }

    /**
     * Mark thread as read for a user.
     *
     * @param ConversaMessage $message
     * @param int $userId
     * @return void
     */
    public function markAsRead(ConversaMessage $message, int $userId): void
    {
        $message->readReceipts()->updateOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }

    /**
     * Get unread replies count for a user.
     *
     * @param ConversaMessage $message
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(ConversaMessage $message, int $userId): int
    {
        $lastRead = $message->readReceipts()
            ->where('user_id', $userId)
            ->value('read_at');

        if (!$lastRead) {
            return $message->replies()->count();
        }

        return $message->replies()
            ->where('created_at', '>', $lastRead)
            ->count();
    }
}
