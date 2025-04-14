<?php

namespace SwellSystems\Conversa\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use SwellSystems\Conversa\WebSocket\WebSocketServer as WebSocketHandler;
use SwellSystems\Conversa\Services\UserPresenceManager;
use SwellSystems\Conversa\Services\MessageEncryption;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Exception;

class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversa:websocket
                          {--host=0.0.0.0 : The host address to bind to}
                          {--port=6001 : The port to listen on}
                          {--ssl-cert= : Path to SSL certificate file}
                          {--ssl-key= : Path to SSL private key file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Conversa WebSocket server';

    /**
     * Execute the console command.
     */
    public function handle(UserPresenceManager $presenceManager, MessageEncryption $encryption)
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $sslCert = $this->option('ssl-cert');
        $sslKey = $this->option('ssl-key');

        try {
            $loop = Loop::get();
            
            // Create WebSocket handler
            $webSocket = new WebSocketHandler($presenceManager, $encryption);

            // Create server stack
            $server = new IoServer(
                new HttpServer(
                    new WsServer($webSocket)
                ),
                $this->createSocket($host, $port, $sslCert, $sslKey),
                $loop
            );

            // Add periodic tasks
            $loop->addPeriodicTimer(60, function () use ($presenceManager) {
                // Clean up expired presence data
                $presenceManager->cleanup();
            });

            $this->info("WebSocket server started on {$host}:{$port}");

            // Run the server
            $server->run();

        } catch (Exception $e) {
            $this->error("Failed to start WebSocket server: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Create the socket server.
     *
     * @param string $host
     * @param int $port
     * @param string|null $sslCert
     * @param string|null $sslKey
     * @return \React\Socket\ServerInterface
     */
    protected function createSocket($host, $port, $sslCert = null, $sslKey = null)
    {
        $uri = "tcp://{$host}:{$port}";
        
        if ($sslCert && $sslKey) {
            $uri = "tls://{$host}:{$port}";
            $context = [
                'tls' => [
                    'local_cert' => $sslCert,
                    'local_pk' => $sslKey,
                    'allow_self_signed' => true,
                    'verify_peer' => false,
                ],
            ];

            return new SocketServer($uri, ['tcp' => $context]);
        }

        return new SocketServer($uri);
    }

    /**
     * Get the WebSocket server URL.
     *
     * @param string $host
     * @param int $port
     * @param bool $secure
     * @return string
     */
    protected function getWebSocketUrl($host, $port, $secure = false)
    {
        $protocol = $secure ? 'wss' : 'ws';
        return "{$protocol}://{$host}:{$port}";
    }

    /**
     * Handle the server shutdown.
     *
     * @return void
     */
    protected function handleShutdown()
    {
        $this->info('Shutting down WebSocket server...');
    }
}
