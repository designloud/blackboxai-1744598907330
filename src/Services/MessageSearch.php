<?php

namespace SwellSystems\Conversa\Services;

use SwellSystems\Conversa\Models\ConversaMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;

class MessageSearch
{
    /**
     * The encryption service instance.
     *
     * @var \SwellSystems\Conversa\Services\MessageEncryption
     */
    protected $encryption;

    /**
     * Create a new message search service instance.
     */
    public function __construct(MessageEncryption $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Search messages with various filters.
     *
     * @param array $params Search parameters
     * @return array
     */
    public function search(array $params): array
    {
        $query = $params['query'] ?? '';
        $workspaceId = $params['workspace_id'] ?? null;
        $spaceId = $params['space_id'] ?? null;
        $threadId = $params['thread_id'] ?? null;
        $userId = $params['user_id'] ?? null;
        $hasAttachments = $params['has_attachments'] ?? null;
        $hasReactions = $params['has_reactions'] ?? null;
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $perPage = $params['per_page'] ?? 20;
        $page = $params['page'] ?? 1;

        // Create Scout search builder
        $searchBuilder = ConversaMessage::search($query);

        // Apply workspace filter
        if ($workspaceId) {
            $searchBuilder->where('workspace_id', $workspaceId);
        }

        // Apply space filter
        if ($spaceId) {
            $searchBuilder->where('space_id', $spaceId);
        }

        // Apply thread filter
        if ($threadId) {
            $searchBuilder->where('thread_id', $threadId);
        }

        // Apply user filter
        if ($userId) {
            $searchBuilder->where('user_id', $userId);
        }

        // Apply date range filters
        if ($dateFrom) {
            $searchBuilder->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $searchBuilder->where('created_at', '<=', $dateTo);
        }

        // Get search results
        $results = $searchBuilder->paginate($perPage, 'page', $page);

        // Load relationships
        $messages = $results->load([
            'user',
            'attachments',
            'reactions',
            'replies' => function ($query) {
                $query->latest()->take(3)->with(['user', 'attachments']);
            },
        ]);

        // Apply post-search filters
        if ($hasAttachments || $hasReactions) {
            $messages = $messages->filter(function ($message) use ($hasAttachments, $hasReactions) {
                if ($hasAttachments && !$message->attachments->count()) {
                    return false;
                }
                if ($hasReactions && !$message->reactions->count()) {
                    return false;
                }
                return true;
            });
        }

        // Decrypt messages if encryption is enabled
        if (config('conversa.encryption.enabled', false)) {
            $messages->transform(function ($message) {
                $message->decryptContent();
                return $message;
            });
        }

        return [
            'messages' => $messages,
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
        ];
    }

    /**
     * Get search suggestions based on partial query.
     *
     * @param string $query
     * @param int|null $workspaceId
     * @param int $limit
     * @return Collection
     */
    public function getSuggestions(string $query, ?int $workspaceId = null, int $limit = 5): Collection
    {
        $searchBuilder = ConversaMessage::search($query);

        if ($workspaceId) {
            $searchBuilder->where('workspace_id', $workspaceId);
        }

        return $searchBuilder->take($limit)->get();
    }

    /**
     * Search within a specific context (space or thread).
     *
     * @param string $query
     * @param array $context
     * @param int $perPage
     * @return array
     */
    public function searchInContext(string $query, array $context, int $perPage = 20): array
    {
        $searchBuilder = ConversaMessage::search($query);

        foreach ($context as $key => $value) {
            $searchBuilder->where($key, $value);
        }

        $results = $searchBuilder->paginate($perPage);

        return [
            'messages' => $results->load(['user', 'attachments', 'reactions']),
            'total' => $results->total(),
            'per_page' => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
        ];
    }

    /**
     * Get highlighted message content.
     *
     * @param ConversaMessage $message
     * @param string $query
     * @return string
     */
    public function getHighlightedContent(ConversaMessage $message, string $query): string
    {
        $content = $message->content;

        if (config('conversa.encryption.enabled', false)) {
            $content = $this->encryption->decrypt(['content' => $content])['content'];
        }

        $words = explode(' ', preg_quote($query, '/'));
        $pattern = '/(' . implode('|', $words) . ')/i';

        return preg_replace($pattern, '<mark>$1</mark>', $content);
    }

    /**
     * Get search filters for the current user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getSavedFilters(int $userId): Collection
    {
        return \SwellSystems\Conversa\Models\ConversaSearchFilter::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Save search filters for later use.
     *
     * @param int $userId
     * @param array $filters
     * @param string|null $name
     * @return \SwellSystems\Conversa\Models\ConversaSearchFilter
     */
    public function saveFilters(int $userId, array $filters, ?string $name = null)
    {
        return \SwellSystems\Conversa\Models\ConversaSearchFilter::create([
            'user_id' => $userId,
            'name' => $name,
            'filters' => $filters,
        ]);
    }
}
