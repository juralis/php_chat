<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use CoolChat\Events;

require dirname(__DIR__) . '/vendor/autoload.php';
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Events()
        )
    ),
    7000
);

$server->run();