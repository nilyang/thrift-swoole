<?php
require_once __DIR__ . "/Thrift/ClassLoader/ThriftClassLoader.php";
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Server\TServerSocket;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__.'/Thrift');
$loader->registerNamespace('Swoole', __DIR__.'/Swoole');
$loader->registerDefinition('Services',  __DIR__.'/Services');
$loader->register();

$processor = new Services\HelloSwoole\HelloSwooleProcessor($yy_service);
$socket_tranport = new TServerSocket('0.0.0.0',8091);
//	$socket_tranport = new TNonblockingServerSocket($host,$port);
//$framed_tranport = new TFramedTransport();
$out_factory = $in_factory = new TFramedTransportFactory();
$out_protocol = $in_protocol = new TBinaryProtocolFactory();

$server = new Swoole\Thrift\Server($processor, $socket_tranport, $in_factory, $out_factory, $in_protocol, $out_protocol);
//	$server = new TForkingServer($processor,$socket_tranport,$in_factory,$out_factory,$in_protocol,$out_protocol);
//$server = new TNonblockingServer($processor,$socket_tranport,$in_factory,$out_factory,$in_protocol,$out_protocol);
$server->serve();

