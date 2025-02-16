<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class StreamServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $broadcaster = null;
    protected $viewers = [];
    private $fileReceivers = [];
    private $computerClient = null;
    private $phoneClient = null;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "StreamServer initialized\n";
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->id = uniqid();
        echo "New connection ({$conn->id})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            echo "Received message type: {$data['type']} from {$from->id}\n";

            if (!$data || !isset($data['type'])) {
                echo "Invalid message format\n";
                return;
            }

            switch ($data['type']) {
                case 'broadcaster':
                    $this->broadcaster = $from;
                    $from->role = 'broadcaster';
                    echo "Broadcaster {$from->id} connected\n";
                    
                    // Notify all existing viewers
                    foreach ($this->viewers as $viewer) {
                        echo "Notifying viewer {$viewer->id} about broadcaster\n";
                        $viewer->send(json_encode([
                            'type' => 'broadcaster_connected'
                        ]));
                    }
                    break;

                case 'viewer':
                    $viewerId = uniqid();
                    $from->viewerId = $viewerId;
                    $this->viewers[$viewerId] = $from;
                    echo "Viewer {$viewerId} connected\n";
                    
                    // Notify broadcaster
                    if ($this->broadcaster) {
                        echo "Notifying broadcaster about new viewer\n";
                        $this->broadcaster->send(json_encode([
                            'type' => 'viewer_joined',
                            'viewerId' => $viewerId
                        ]));
                    } else {
                        echo "No broadcaster available for viewer {$viewerId}\n";
                    }
                    break;

                case 'offer':
                    $target = $data['target'] === 'computer' ? $this->computerClient : $this->phoneClient;
                    if ($target) {
                        echo "Sending offer to target {$data['target']}\n";
                        $target->send(json_encode([
                            'type' => 'offer',
                            'offer' => $data['offer']
                        ]));
                    }
                    break;

                case 'answer':
                    // Send to the other party
                    $sender = $from === $this->computerClient ? $this->phoneClient : $this->computerClient;
                    if ($sender) {
                        echo "Sending answer to target {$data['target']} from viewer {$from->viewerId}\n";
                        $sender->send(json_encode([
                            'type' => 'answer',
                            'answer' => $data['answer']
                        ]));
                    }
                    break;

                case 'ice_candidate':
                    // Send to the other party
                    $target = $from === $this->computerClient ? $this->phoneClient : $this->computerClient;
                    if ($target) {
                        echo "Sending ICE candidate from {$from->role} to " . 
                             ($from->role === 'broadcaster' ? "viewer {$data['viewerId']}" : "broadcaster") . "\n";
                        $target->send(json_encode([
                            'type' => 'ice_candidate',
                            'viewerId' => $data['viewerId'],
                            'candidate' => $data['candidate']
                        ]));
                    }
                    break;

                case 'register':
                    if ($data['role'] === 'file_receiver') {
                        $this->fileReceivers[$from->resourceId] = $from;
                    } else if ($data['role'] === 'computer') {
                        $this->computerClient = $from;
                    } else if ($data['role'] === 'phone') {
                        $this->phoneClient = $from;
                    }
                    break;

                case 'file_uploaded':
                    foreach ($this->fileReceivers as $client) {
                        $client->send(json_encode([
                            'type' => 'file_received',
                            'fileName' => $data['fileName'],
                            'fileSize' => $data['fileSize']
                        ]));
                    }
                    break;
            }
        } catch (\Exception $e) {
            echo "Error: {$e->getMessage()}\n";
            echo "Stack trace: {$e->getTraceAsString()}\n";
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        echo "Connection {$conn->id} closed\n";
        
        if ($conn === $this->broadcaster) {
            echo "Broadcaster disconnected\n";
            $this->broadcaster = null;
            
            // Notify all viewers
            foreach ($this->viewers as $viewer) {
                $viewer->send(json_encode([
                    'type' => 'broadcaster_disconnected'
                ]));
            }
        } else if (isset($conn->viewerId)) {
            echo "Viewer {$conn->viewerId} disconnected\n";
            unset($this->viewers[$conn->viewerId]);
            if ($this->broadcaster) {
                $this->broadcaster->send(json_encode([
                    'type' => 'viewer_left',
                    'viewerId' => $conn->viewerId
                ]));
            }
        }
        $this->clients->detach($conn);
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "Error on connection {$conn->id}: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create event loop and server
$loop = Factory::create();
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new StreamServer()
        )
    ),
    8081
);

echo "WebSocket server started on port 8081\n";
$server->run(); 