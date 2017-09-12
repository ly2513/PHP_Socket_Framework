<?php
/**
 * User: yongli
 * Date: 17/9/12
 * Time: 22:28
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Api;

use App\Core\Log;
use App\Core\Trident\Confluence;
use App\Core\Trident\Business;
use App\Core\Trident\Transfer;

/**
 * 三层模型
 * Class Trident
 *
 * @package App\Api
 */
class Trident
{
    /******************** Confluence层的相关配置 *****************/
    /**
     * 地址
     *
     * @var string
     */
    public $confluenceIp = '0.0.0.0';

    /**
     * 端口
     *
     * @var string
     */
    public $confluencePort = '19911';

    /**
     *
     * @var string
     */
    public $confluenceInnerIp = '127.0.0.1';

    /**
     *
     *
     * @var string
     */
    public $confluenceName = 'YP_Socket-Trident-Confluence';

    /**
     *
     *
     * @var int
     */
    private $_confluenceChildProcessCount = 1;

    /************************ Transfer层的相关配置 *************************/
    /**
     *
     * @var string
     */
    private $_transferHost;

    /**
     *
     *
     * @var string
     */
    private $_transferPort;

    /**
     *
     * @var int
     */
    public $transferChildProcessCount = 1;

    /**
     * Transfer回复数据给客户端的时候转码函数
     *
     * @var
     */
    public $transferEncodeFunction;

    /**
     * Transfer的内网IP和端口, Business要用这个IP和端口链接到Transfer
     *
     * @var string
     */
    public $transferInnerIp = '0.0.0.0';

    /**
     *
     * @var string
     */
    public $transferInnerPort = '19912';

    /************************ Business层的相关配置 *******************/
    /**
     *
     *
     * @var int
     */
    public $businessChildProcessCount = 1;

    /**
     *
     *
     * @var string
     */
    public $businessName = 'YP_Socket-Trident-Business';

    /**
     *
     * @var array
     */
    private $_contextOptionList = [];

    /**
     *
     *
     * @var string
     */
    private $_transferApiName = '';

    /**
     *
     * @var string
     */
    private $_container = '';

    /**
     *
     *
     * @var array
     */
    public static $callbackList = [];

    /**
     *
     *
     * @var array
     */
    private $_transferApiPropertyAndMethod = [];

    /**
     *
     *
     * @var string
     */
    public static $innerProtocol = 'telnetjson';

    /**
     * Trident constructor.
     *
     * @param        $apiName Api类名
     * @param        $host    需要监听的地址
     * @param        $port    需要监听的端口
     * @param array  $contextOptionList
     * @param string $container
     */
    public function __construct($apiName, $host, $port, $contextOptionList = [], $container = '')
    {
        // 参数合法性校验
        $container = strtolower($container);
        if ($container && (!in_array($container, ['confluence', 'business', 'transfer']))) {
            Log::write('Container must is confluence | business | transfer', 'FATAL');
        }
        // 如果是启动Transfer或者全部启动时, 需要判断参数
        $apiName = $apiName ? '\App\Api\\' . ucfirst($apiName) : '';
        if ($container != 'confluence' && $container != 'business') {
            if (!$apiName || !$host || !$port) {
                Log::write($apiName . ' and ' . $host . ' and ' . $port . ' can not be empty.', 'FATAL');
            }
            // 接口是否存在
            if (!class_exists($apiName)) {
                Log::write('Api class not exists. api=' . $apiName, 'FATAL');
            }
        }
        $this->_transferApiName   = $apiName;
        $this->_transferHost      = $host;
        $this->_transferPort      = $port;
        $this->_container         = strtolower($container);
        $this->_contextOptionList = $contextOptionList;
    }

    /**
     * 启动三层模型
     */
    public function run()
    {
        // 根据容器选项启动, 如果为空, 则全部启动
        switch ($this->_container) {
            case 'confluence':
                $this->_initConfluence();
                break;
            case 'transfer':
                $this->_initTransfer();
                break;
            case 'business':
                $this->_initBusiness();
                break;
            default:
                $this->_initConfluence();
                echo "YP_Socket Confluence Start: \033[40G[\033[49;32;5mOK\033[0m]\n";
                $this->_initTransfer();
                echo "YP_Socket Transfer Start: \033[40G[\033[49;32;5mOK\033[0m]\n";
                $this->_initBusiness();
                echo "YP_Socket Business Start: \033[40G[\033[49;32;5mOK\033[0m]\n";
                break;
        }
    }

    /**
     * 魔术方法。所有不可访问的、不存在的属性, 统统赋值给Transfer所使用的API类
     * __set
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        // 四个回调函数需要单独收集, 其他的和普通属性一样, 直接赋值给API类
        if (in_array($name, ['callbackStartInstance', 'callbackConnect', 'callbackNewData', 'callbackConnectClose'])) {
            self::$callbackList[$name] = $value;
        } else {
            $this->_transferApiPropertyAndMethod['property'][$name] = $value;
        }
    }

    /**
     * 魔术调用方法
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        $this->_transferApiPropertyAndMethod['method'][$name] = $arguments;
    }

    /**
     *
     */
    private function _initConfluence()
    {
        $confluence                    = new Confluence(self::$innerProtocol, $this->confluenceIp,
            $this->confluencePort);
        $confluence->childProcessCount = $this->_confluenceChildProcessCount;
        $confluence->instanceName      = $this->confluenceName;
    }

    /**
     *
     */
    private function _initTransfer()
    {
        $transfer                 = new Transfer($this->_transferApiName, $this->_transferHost, $this->_transferPort,
            $this->_contextOptionList);
        $transfer->innerIp        = $this->transferInnerIp;
        $transfer->innerPort      = $this->transferInnerPort;
        $transfer->encodeFunction = $this->transferEncodeFunction;
        $transfer->confluenceIp   = $this->confluenceInnerIp;
        $transfer->confluencePort = $this->confluencePort;
        //设置API接口的属性
        if ($this->_transferApiPropertyAndMethod['property']) {
            foreach ($this->_transferApiPropertyAndMethod['property'] as $methodName => $arguments) {
                $transfer->setApiClassProperty($methodName, $arguments);
            }
        }
        //调用API接口的方法
        if (!empty($this->_transferApiPropertyAndMethod['method'])) {
            foreach ($this->_transferApiPropertyAndMethod['method'] as $methodName => $arguments) {
                $transfer->callApiClassMethod($methodName, $arguments);
            }
        }
    }

    /**
     * 
     */
    private function _initBusiness()
    {
        $business                    = new Business();
        $business->childProcessCount = $this->businessChildProcessCount;
        $business->instanceName      = $this->businessName;
        $business->confluenceIp      = $this->confluenceInnerIp;
        $business->confluencePort    = $this->confluencePort;
    }
}