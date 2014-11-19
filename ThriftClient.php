<?php

// 引入客户端文件
require_once __DIR__ .'/rpc/Clients/ThriftClient.php';

use ThriftClient\ThriftClient as JJWWThriftClient;

// 读配置
//$GLOBALS['tqRpcConfig'] = require dirname(__DIR__) . '/Config/rpc.php';

// 传入配置，一般在某统一入口文件中调用一次该配置接口即可
JJWWThriftClient::config(ConfigManager::get("thrift"));

class ThriftClient
{

    /**
     * 客户端实例
     * @var array
     */
    private static $instance = array();

    public static function instance($serviceName, $newOne = false)
    {
        if (empty($serviceName)) {
            throw new \Exception('ServiceName can not be empty');
        }
		$yy_logo_conf = ConfigManager::get("thrift");
        if (!isset($yy_logo_conf[$serviceName])) {
            $className = "\\Services\\" . $serviceName . "\\" . $serviceName . "Handler";
        } else {
            $className = "\\Services\\" . $serviceName . "\\" . $serviceName . "Client";
        }

        // 类不存在则尝试加载
        if (!class_exists($className)) {
            $serviceDir = self::includeFile($serviceName);
            if (!class_exists($className)) {
                throw new \Exception("Class $className not found in directory $serviceDir");
            }
        }

        if ($newOne) {
            if (isset(self::$instance[$service])) {
                unset(self::$instance[$service]);
            }
        }

        if (!isset(self::$instance[$serviceName])) {
            if (!isset($yy_logo_conf[$serviceName])) {
                $instance = new ThriftInstance($serviceName);
            } else {
                $instance = JJWWThriftClient::instance($serviceName);
            }
            self::$instance[$serviceName] = $instance;
        }

        return self::$instance[$serviceName];
    }

    /**
     * 载入thrift生成的客户端文件
     * @param string
     * @throws \Exception
     * @return void
     */
    protected static function includeFile($serviceName)
    {
        // 载入该服务下的所有文件
        $serviceDir = JJWWThriftClient::getServiceDir($serviceName);
        foreach (glob($serviceDir.'/*.php') as $phpFile) {
            require_once $phpFile;
        }
        return $serviceDir;
    }
}

class ThriftInstance
{

    /**
     * 服务名
     * @var string
     */
    public $serviceName = '';

    /**
     * 对应handler的类名
     * @var string
     */
    public $className = '';

    /**
     * thrift实例
     * @var array
     */
    protected $thriftInstance = null;

    public function __construct($serviceName)
    {
        if (empty($serviceName)) {
            throw \Exception('serviceName can not be empty', 500);
        }
        $this->serviceName = $serviceName;
    }

    public function __call($methodName, $args)
    {
        $className = "\\Services\\" . $this->serviceName . "\\" . $this->serviceName . "Handler";

        // 每次都重新创建一个实例
        $this->thriftInstance = new $className();

        $callback = array($this->thriftInstance, $methodName);
        if (!is_callable($callback)) {
            throw new \Exception($this->serviceName.'->'.$methodName. ' not callable', 1400);
        }
        // 调用客户端方法
        $ret = call_user_func_array($callback, $args);
        // 每次都销毁实例
        $this->thriftInstance = null;

        return $ret;
    }

    protected function __instance()
    {
        // 客户端名称
        $className = "\\Services\\" . $this->serviceName . "\\" . $this->serviceName . "Handler";
        // 类不存在则尝试加载
        if (!class_exists($className)) {
            $serviceDir = $this->includeFile();
            if (!class_exists($className)) {
                throw new \Exception("Class $className not found in directory $serviceDir");
            }
        }

        // 初始化一个实例
        return new $className();
    }

    /**
     * 载入thrift生成的客户端文件
     * @throws \Exception
     * @return void
     */
    protected function includeFile()
    {
        // 载入该服务下的所有文件
        $serviceDir = JJWWThriftClient::getServiceDir($this->serviceName);
        foreach (glob($serviceDir.'/*.php') as $phpFile) {
            require_once $phpFile;
        }
        return $serviceDir;
    }
}
