<?php
namespace CoolChat;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
define('AES_256_CBC', 'aes-256-cbc');
define('SECRET_KEY', 'wetgsdgbt');
define('AES_IV', 'q1w2e3r4t5y6u7i8');


class Events implements MessageComponentInterface {
    protected $clients;
    protected $clients_logins;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->clients_logins = array();
        $this->conn = pg_connect("host=localhost dbname=php_chat user=postgres password=123456");

    }

    public function query($query){
        return pg_fetch_all(pg_query($this->conn, $query));
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Законнектился новый пассажир ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $client, $msg) {
        $msg = json_decode($msg);
        $method = $msg->method;

        if ($msg->token && $method) {
            $token = str_replace('-','=', $msg->token);
            $token = str_replace('.','+', $token);
            $token = str_replace('_','/', $token);

            $user = openssl_decrypt($token, AES_256_CBC, SECRET_KEY, 0, AES_IV);
            $login = json_decode($user)->login;

            $client->login = $login;

            if (method_exists($this, $method)) {
                Events::$method($client, $msg->body, $login);
            } else {
                // not implemented method... и чёрт с ним в принципе.
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Нет человека {$conn->resourceId} - нет проблемы.\n";
        if ($conn->login) {
            foreach ($this->clients as $client) {
                if ($client->login) {
                    $client->send(json_encode(array('method' => 'user_left', 'body' => $conn->login)));
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Еггог: {$e->getMessage()}\n";
        $conn->close();
    }

    public function send_msg($from, $msg) {
        $time = date("U");
        $msg->text = str_replace('<', '&lt;', $msg->text);
        $msg->text = str_replace("'", "\'", $msg->text);

        $que ="INSERT INTO chat_log VALUES ('$from->login', '$msg->text', $time);";
        $this->query($que);

        $msg->login = $from->login;
        $msg->time = $time;

        $msg = json_encode(array('method' => 'new_msg', 'body' => $msg));

        foreach ($this->clients as $client) {
                $client->send($msg);
        }
    }

    public function get_log($from) {
        $que = 'SELECT * FROM chat_log order by time desc LIMIT 30;';
        $log = $this->query($que);
        $msg = json_encode(array('method' => 'chat_log', 'body' => $log));

        foreach ($this->clients as $client) {
            if ($from === $client) {
                $client->send($msg);
            }
        }
    }

    public function online($from) {
        $user_list = array();

        foreach ($this->clients as $client) {
            if ($client->login){
                array_push($user_list, $client->login);
                $client->send(json_encode(array('method'=> 'user_came', 'body'=> $from->login)));
            }
        }

        $from->send(json_encode(array('method'=> 'online', 'body'=> $user_list)));
    }
}