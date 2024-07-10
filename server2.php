<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server as ReactServer;
use React\Socket\SecureServer;

$logFile = '/home/ubuntu/phpchat/server.log'; 

function logMessage($message) { 
    global $logFile; 
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND); 
} 

logMessage("Starting server...");  

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$webSock = new ReactServer('0.0.0.0:3000', $loop);

// Secure the WebSocket server
$secure_webSock = new SecureServer($webSock, $loop, [
    'local_cert' => '/path/to/your/cert.pem', // path to your cert
    'local_pk' => '/path/to/your/key.pem',    // path to your server private key
    'allow_self_signed' => true,
    'verify_peer' => false
]);

$webServer = new IoServer(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    $secure_webSock,
    $loop
);

echo "Server started at port 3000\n";

$loop->run();

echo "Server stopped\n";
?>
