<?php
/**
 * Created by PhpStorm.
 * User: nilyang
 * Date: 15/6/19
 * Time: 15:43
 */

class Server
{
    private $serv;

    public function __construct()
    {
        $this->serv = new swoole_server('0.0.0.0', 9501);
        $this->serv->set([
            'worker_num'=>8,
            'daemonize'=>true,
            'max_request'=>10000,
            'dispatch_mode'=>2,
            'debug_mode'=>1,
            'log_file' => '/var/log/swoole.log'
        ]);

        $this->serv->on('Start',[$this,'onStart']);
        $this->serv->on('Connect',[$this,'onConnect']);
        $this->serv->on('Receive',[$this,'onReceive']);
        $this->serv->on('Close',[$this,'onClose']);

        $this->serv->start();
    }

    public function onStart( $serv )
    {
        echo "Start\n";
    }

    public function onConnect( $serv, $fd, $from_id )
    {
        $serv->send( $fd, "Hello {$fd}!" );
    }

    public function onReceive( swoole_server $serv, $fd, $from_id, $data )
    {
        echo "Get Message From Client {$fd}:{$data}\n";
    }

    public function onClose( $serv, $fd, $from_id )
    {
        echo "Client {$fd} close connection\n";
    }
}


$server  = new Server();


