<?php

namespace SwellSystems\Conversa\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SwellSystems\Conversa\Services\UserPresenceManager;
use SwellSystems\Conversa\Services\MessageEncryption;
use Illuminate\Support\Facades\Log;
use Exception;

class WebSocketServer implements MessageComponentInterface
{
    /**
     * Active client connections.
     *
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * Channel subscriptions.
     *
     * @var array
     */
    protected $subscriptions = [];

    /**
     * The presence manager instance.
     *
     * @var \SwellSystems\Conversa\Services\UserPresenceManager
     */
    protected $presenceManager;

    /**
     * The encryption service instance.
     *
     * @var \SwellSystems\Conversa\Services\MessageEncryption
     */
    protected $encryption;

    /**
     * Create a new WebSocket server instance.
     */
    public function __construct(UserPresenceManager $presenceManager, MessageEncryption $encryption)
    {
        $this->clients = new \SplObjectStorage;
        $this->presenceManager = $presenceManager;
        $this->encryption = $encryption;
    }

    /**
     * Handle client connection.
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        Log::info('New client connected', [
            'id' => $conn->resourceId,
            'ip' => $conn->remoteAddress,
        ]);
    }

    /**
     * Handle incoming messages.
     *
     * @param ConnectionInterface $from
     * @param string $msg
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['event'])) {
            return;
        }

        switch ($data['event']) {
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;

            case 'unsubscribe':
                $this->handleUnsubscribe($from, $data);
                break;

            case 'presence':
                $this->handlePresence($from, $data);
                break;

            case 'message':
                $this->handleMessage($from, $data);
                break;

            case 'typing':
                $this->handleTyping($from, $data);
                break;
        }
    }

    /**
     * Handle client disconnection.
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->removeFromAllChannels($conn);

        // Update presence status if authenticated
        if (isset($conn->userId)) {
            $this->presenceManager->markOffline($conn->userId, $conn->workspaceId);
            $this->broadcastPresenceUpdate($conn->userId, $conn->workspaceId, 'offline');
        }

        Log::info('Client disconnected', ['id' => $conn->resourceId]);
    }

    /**
     * Handle errors.
     *
     * @param ConnectionInterface $conn
     * @param Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        Log::error('WebSocket error', [
            'client_id' => $conn->resourceId,
            'error' => $e->getMessage(),
        ]);

        $conn->close();
    }

    /**
     * Handle channel subscription.
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleSubscribe(ConnectionInterface $conn, array $data)
    {
        if (!isset($data['channel'])) {
            return;
        }

        $channel = $data['channel'];
        
        // Store authentication data
        if (isset($data['auth'])) {
            $conn->userId = $data['auth']['user_id'];
            $conn->workspaceId = $data['auth']['workspace_id'];
        }

        // Add to channel subscribers
        if (!isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = new \SplObjectStorage;
        }
        $this->subscriptions[$channel]->attach($conn);

        // Send confirmation
        $conn->send(json_encode([
            'event' => 'subscribed',
            'channel' => $channel,
        ]));

        // Handle presence channel subscription
        if (strpos($channel, 'presence.') === 0 && isset($conn->userId)) {
            $this->handlePresenceSubscription($conn, $channel);
        }
    }

    /**
     * Handle channel unsubscription.
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleUnsubscribe(ConnectionInterface $conn, array $data)
    {
        if (!isset($data['channel'])) {
            return;
        }

        $this->removeFromChannel($conn, $data['channel']);
    }

    /**
     * Handle presence update.
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handlePresence(ConnectionInterface $conn, array $data)
    {
        if (!isset($conn->userId) || !isset($data['status'])) {
            return;
        }

        $this->presenceManager->updatePresence(
            $conn->userId,
            $conn->workspaceId,
            $data['status'],
            $data['status_message'] ?? null
        );

        $this->broadcastPresenceUpdate($conn->userId, $conn->workspaceId, $data['status']);
    }

    /**
     * Handle message broadcast.
     *
     * @param ConnectionInterface $from
     * @param array $data
     * @return void
     */
    protected function handleMessage(ConnectionInterface $from, array $data)
    {
        if (!isset($data['channel']) || !isset($data['message'])) {
            return;
        }

        // Encrypt message if needed
        if (config('conversa.encryption.enabled', false)) {
            $data['message'] = $this->encryption->encrypt($data['message']);
        }

        $this->broadcast($data['channel'], 'message', $data['message'], [$from]);
    }

    /**
     * Handle typing indicator.
     *
     * @param ConnectionInterface $from
     * @param array $data
     * @return void
     */
    protected function handleTyping(ConnectionInterface $from, array $data)
    {
        if (!isset($data['channel'])) {
            return;
        }

        $this->broadcast($data['channel'], 'typing', [
            'user_id' => $from->userId,
        ], [$from]);
    }

    /**
     * Broadcast message to channel.
     *
     * @param string $channel
     * @param string $event
     * @param mixed $data
     * @param array $exclude
     * @return void
     */
    protected function broadcast(string $channel, string $event, $data, array $exclude = [])
    {
        if (!isset($this->subscriptions[$channel])) {
            return;
        }

        $message = json_encode([
            'event' => $event,
            'channel' => $channel,
            'data' => $data,
        ]);

        foreach ($this->subscriptions[$channel] as $client) {
            if (!in_array($client, $exclude)) {
                $client->send($message);
            }
        }
    }

    /**
     * Remove client from channel.
     *
     * @param ConnectionInterface $conn
     * @param string $channel
     * @return void
     */
    protected function removeFromChannel(ConnectionInterface $conn, string $channel)
    {
        if (isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel]->detach($conn);
        }
    }

    /**
     * Remove client from all channels.
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    protected function removeFromAllChannels(ConnectionInterface $conn)
    {
        foreach ($this->subscriptions as $channel => $clients) {
            $clients->detach($conn);
        }
    }

    /**
     * Handle presence channel subscription.
     *
     * @param ConnectionInterface $conn
     * @param string $channel
     * @return void
     */
    protected function handlePresenceSubscription(ConnectionInterface $conn, string $channel)
    {
        // Mark user as online
        $this->presenceManager->markOnline($conn->userId, $conn->workspaceId);

        // Get current members
        $members = $this->getChannelMembers($channel);

        // Send current members to the new subscriber
        $conn->send(json_encode([
            'event' => 'presence_state',
            'channel' => $channel,
            'data' => $members,
        ]));

        // Broadcast join event
        $this->broadcastPresenceUpdate($conn->userId, $conn->workspaceId, 'online');
    }

    /**
     * Get channel members.
     *
     * @param string $channel
     * @return array
     */
    protected function getChannelMembers(string $channel): array
    {
        $members = [];
        
        if (isset($this->subscriptions[$channel])) {
            foreach ($this->subscriptions[$channel] as $client) {
                if (isset($client->userId)) {
                    $members[] = [
                        'user_id' => $client->userId,
                        'workspace_id' => $client->workspaceId,
                    ];
                }
            }
        }

        return $members;
    }

    /**
     * Broadcast presence update.
     *
     * @param int $userId
     * @param int $workspaceId
     * @param string $status
     * @return void
     */
    protected function broadcastPresenceUpdate(int $userId, int $workspaceId, string $status)
    {
        $channel = "presence.workspace.{$workspaceId}";
        
        $this->broadcast($channel, 'presence_update', [
            'user_id' => $userId,
            'status' => $status,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
