<?php
require_once __DIR__ . "/Thrift/ClassLoader/ThriftClassLoader.php";
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Server\TServerSocket;
use Thrift\Server\TNonblockingServer;
use Thrift\Factory\TFramedTransportFactory;
use Thrift\Factory\TBinaryProtocolFactory;
use Thrift\Exception\TTransportException;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__.'/Thrift');
//$loader->registerDefinition('Services',  __DIR__.'/Services');
$loader->register();

class SwooleThriftServer extends TNonblockingServer
{
    protected $processor = null;
    protected $serviceName = 'YYSports';

    public function onStart()
    {
        $processor_class = "\\Services\\" . $this->serviceName . "\\" . $this->serviceName . 'Processor';
        $handler_class = "\\Services\\" . $this->serviceName . "\\" . $this->serviceName . 'Handler';

        $handler = new $handler_class();
        $this->processor = new $processor_class($handler);
    }

    function notice($log)
    {
        echo $log."\n";
    }

    public function onReceive($serv, $fd, $from_id, $data)
    {
        $socket = new SwooleSocket();
        $socket->setHandle($fd);
        $socket->buffer = $data;
        $socket->server = $serv;
        $protocol = new Thrift\Protocol\TBinaryProtocol($socket, false, false);

        try {
            $protocol->fname = $this->serviceName;
            $this->processor->process($protocol, $protocol);
        } catch (\Exception $e) {
            $this->notice('CODE:' . $e->getCode() . ' MESSAGE:' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    function serve()
    {
        $serv = new swoole_server('127.0.0.1', 8091);
        $serv->on('workerStart', [$this, 'onStart']);
        $serv->on('receive', [$this, 'onReceive']);
        $serv->set(
            ['worker_num'            => 1,
             'dispatch_mode'         => 1, //1: 轮循, 3: 争抢
             'open_length_check'     => true, //打开包长检测
             'package_max_length'    => 8192000, //最大的请求包长度,8M
             'package_length_type'   => 'N', //长度的类型，参见PHP的pack函数
             'package_length_offset' => 0, //第N个字节是包长度的值
             'package_body_offset'   => 4, //从第几个字节计算长度
            ]
        );
        $serv->start();
    }
}


class SwooleSocket extends Thrift\Transport\TFramedTransport
{
    public $buffer = '';
    public $offset = 0;
    public $server;
    protected $fd;
    protected $read_ = true;
    protected $rBuf_ = '';
    protected $wBuf_ = '';

    public function setHandle($fd)
    {
        $this->fd = $fd;
    }

    function readFrame()
    {
        $buf = $this->_read(4);
        $val = unpack('N', $buf);
        $sz = $val[1];

        $this->rBuf_ = $this->_read($sz);
    }

    public function _read($len)
    {
        if (strlen($this->buffer) - $this->offset < $len)
        {
            throw new TTransportException('TSocket['.strlen($this->buffer).'] read '.$len.' bytes failed.');
        }
        $data = substr($this->buffer, $this->offset, $len);
        $this->offset += $len;
        return $data;
    }

    public function read($len) {
        if (!$this->read_) {
            return $this->_read($len);
        }

        if (Thrift\Factory\TStringFuncFactory::create()->strlen($this->rBuf_) === 0) {
            $this->readFrame();
        }
        // Just return full buff
        if ($len >= Thrift\Factory\TStringFuncFactory::create()->strlen($this->rBuf_)) {
            $out = $this->rBuf_;
            $this->rBuf_ = null;
            return $out;
        }

        // Return TStringFuncFactory::create()->substr
        $out = Thrift\Factory\TStringFuncFactory::create()->substr($this->rBuf_, 0, $len);
        $this->rBuf_ = Thrift\Factory\TStringFuncFactory::create()->substr($this->rBuf_, $len);
        return $out;
    }

    public function write($buf)
    {
        $this->wBuf_ .= $buf;
    }

    function flush()
    {
        $out = pack('N', strlen($this->wBuf_));
        $out .= $this->wBuf_;
        $this->server->send($this->fd, $out);
        $this->wBuf_ = '';
    }
}


try
{
    $processor = new Services\HelloSwoole\HelloSwooleProcessor($yy_service);
    $socket_tranport = new TServerSocket('0.0.0.0',8091);
//	$socket_tranport = new TNonblockingServerSocket($host,$port);
    //$framed_tranport = new TFramedTransport();
    $out_factory = $in_factory = new TFramedTransportFactory();
    $out_protocol = $in_protocol = new TBinaryProtocolFactory();

    $server = new SwooleThriftServer($processor, $socket_tranport, $in_factory, $out_factory, $in_protocol, $out_protocol);
//	$server = new TForkingServer($processor,$socket_tranport,$in_factory,$out_factory,$in_protocol,$out_protocol);
    //$server = new TNonblockingServer($processor,$socket_tranport,$in_factory,$out_factory,$in_protocol,$out_protocol);
    $server->serve();
}
catch(Expection $e)
{
    var_dump($e->__tostring());
    log::err("thrift server run error : " . $e->__tostring());
}
