<?php
namespace CoolChat;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

$dbconn = pg_connect("host=localhost dbname=php_chat user=postgres password=123456");

class Events implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Законнектился новый пассажир ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msg = json_decode($msg);
        $method = $msg->method;
        Events::$method($from, $msg->body);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Нет человека {$conn->resourceId} - нет проблемы.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Еггог: {$e->getMessage()}\n";

        $conn->close();
    }

    public function send_msg($from, $msg) {
        $msg = json_encode(array('method' => 'new_msg', 'body' => $msg));
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function auth($from, $msg) {
        // тут мутим с базой
        $msg = json_encode(array('method' => 'new_msg', 'body' => $msg));
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }
    public function reg($from, $msg) {
        // тут мутим с базой
        $msg = json_encode(array('method' => 'new_msg', 'body' => $msg));
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }
    public function get_log($from, $msg) {
        // тут мутим с базой
        $msg = json_encode(array('method' => 'new_msg', 'body' => $msg));
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }
}