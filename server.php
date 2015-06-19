<?php
require_once __DIR__ . "/Thrift/ClassLoader/ThriftClassLoader.php";
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Server\TServerSocket;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__);
$loader->registerNamespace('Swoole', __DIR__);
$loader->registerNamespace('Services', __DIR__);
$loader->registerDefinition('Services',  __DIR__);
$loader->register();

$service = new Services\HelloSwoole\Handler();
$processor = new Services\HelloSwoole\HelloSwooleProcessor($service);
$socket_tranport = new TServerSocket('0.0.0.0', 8091);
$in_transport =$out_transport = new \Thrift\Factory\TFramedTransportFactory();
$in_protocol =$out_protocol =  new \Thrift\Factory\TBinaryProtocolFactory(true, true);

$server = new Swoole\Thrift\Server($processor, $socket_tranport, $in_transport, $out_transport, $in_protocol, $out_protocol);

$server->serve();

