<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 00:47
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core\TransportProtocol;

use App\Core\Event\EventInterface;
use App\Core\YP_Socket;
use App\Core\Log;

class Tcp extends TransportProtocolInterface
{
    // 一次最多读取多少个字节
    const READ_SIZE = 65535;
    // 状态 - 链接中
    const CONNECT_STATUS_CONNECTING = 1;
    // 状态 - 链接已经建立
    const CONNECT_STATUS_ESTABLISH = 2;
    // 状态 - 链接关闭中
    const CONNECT_STATUS_CLOSING = 4;
    // 状态 - 链接已经关闭
    const CONNECT_STATUS_CLOSED = 8;

    /**
     * 应用层协议
     *
     * @var string
     */
    protected $_applicationProtocolClassName;

    /**
     * 属于哪个实例
     *
     * @var
     */
    public $instance;

    /**
     * 链接ID
     *
     * @var int
     */
    public $id = 0;

    /**
     * 记录
     *
     * @var int
     */
    protected static $_recorderId = 1;

    /**
     * 本次链接,是一个Socket资源
     *
     * @var resource
     */
    protected $_connect;

    /**
     * 待发送的缓冲区
     *
     * @var string
     */
    protected $_sendBuffer = '';

    /**
     * 已接收到的数据
     *
     * @var string
     */
    private $_readData = '';

    /**
     * 当前包长
     *
     * @var int
     */
    private $_currentPackageSize = 0;

    /**
     * 当前链接状态
     *
     * @var int
     */
    protected $_currentStatus = self::CONNECT_STATUS_ESTABLISH;

    /**
     * 客户端地址
     *
     * @var string
     */
    protected $_clientAddress = '';

    /**
     * 是否暂停读取
     *
     * @var bool
     */
    private $_isPauseRead = false;

    /**
     * 构造函数
     * Tcp constructor.
     *
     * @param $socket                       resource 由stream_socket_accept()返回
     * @param $clientAddress                string 由stream_socket_accept()的第三个参数$peerName
     * @param $applicationProtocolClassname string 应用层协议所使用的类, 默认为空
     */
    public function __construct($socket, $clientAddress, $applicationProtocolClassname = '')
    {
        // 更改统计信息
        self::$statistics['total_connect_count']++;
        self::$statistics['current_connect_count']++;
        // 属性赋值
        $this->id = self::$_recorderId++;
        if (!is_resource($socket)) {
            Log::write('Construct Tcp failed. Possible socket resource has disabled', 'WARNING');

            return;
        }
        $this->_connect       = $socket;
        $this->_clientAddress = $clientAddress;
        if ($applicationProtocolClassname) {
            if (!class_exists($applicationProtocolClassname)) {
                Log::write('Application protocol class: ' . $applicationProtocolClassname . ' not exists', 'FATAL');
            }
            $this->_applicationProtocolClassName = $applicationProtocolClassname;
        }
        stream_set_blocking($this->_connect, 0);
        // 监听此链接
        YP_Socket::$globalEvent->add([$this, 'read'], [], $this->_connect, EventInterface::EVENT_TYPE_READ);
    }

    /**
     * 读取数据
     *
     * @param $connect   resource 是一个Socket的资源
     * @param $isDestroy bool 如果fread()读取到的是空数据或者false的话,是否销毁链接.默认为true
     */
    public function read($connect, $isDestroy = true)
    {
        // 是否读取到了数据
        $isAlreadyReaded = false;
        while (true) {
            self::$statistics['total_read_count']++;
            $buffer = fread($connect, self::READ_SIZE);
            $buffer === false ? self::$statistics['total_read_failed_count']++ : null;
            if ($buffer === '' || $buffer === false || feof($connect) === true) {
                break;
            }
            $isAlreadyReaded = true;
            $this->_readData .= $buffer;
        }
        // 检测连接是否关闭
        if ($isAlreadyReaded === false && $isDestroy) {
            $this->destroy();

            return;
        }
        //处理应用层协议
        $this->_applicationProtocolClassName ? $this->_readByApplicationProtocol() : $this->_readNoApplicationProtocol();
    }

    /**
     * 读取数据包. 通过应用层协议
     */
    private function _readByApplicationProtocol()
    {
        // 如果接收到的数据不为空, 并且没有被暂停
        while (!empty($this->_readData) && $this->_isPauseRead === false) {
            $applicationProtocolClassName = $this->_applicationProtocolClassName;
            $this->_currentPackageSize    = intval($applicationProtocolClassName::input($this->_readData, $this));
            // 如果数据包未完, 则不处理
            if ($this->_currentPackageSize === 0) {
                break;
                // 数据包长度不正确,销毁链接
            } else if ($this->_currentPackageSize < 0) {
                self::$statistics['total_read_package_failed_count']++;
                Log::write('data packet size incorrect. size=' . $this->_currentPackageSize, 'WARNING');
                // 强制销毁链接, 该链接尚未发送的数据也不发了.
                $this->destroy();

                return;
            }
            // 如果数据包超过配置的最大TCP链接所接收的数据量, 则抛弃本数据包, 写日志. 此方式模仿PHP的POST请求过大会直接放弃, 所以$_FILE有时会为空
            if ($this->_currentPackageSize > TCP_CONNECT_READ_MAX_PACKET_SIZE) {
                //放弃该数据包
                $this->_readData           = substr($this->_readData, $this->_currentPackageSize);
                $this->_currentPackageSize = 0;
                Log::write('data packet size exceeds the maximum limit. size=' . $this->_currentPackageSize . '. limit=' . TCP_CONNECT_READ_MAX_PACKET_SIZE,
                    'WARNING');
                //强制销毁链接, 该链接尚未发送的数据也不发了.
                $this->destroy();

                return;
            }
            // 读取完整数据包的个数
            self::$statistics['total_read_package_count']++;
            // 如果缓冲区的所有数据是一个完整的包
            if ($this->_currentPackageSize == strlen($this->_readData)) {
                $requestBuffer   = $this->_readData;
                $this->_readData = '';
            } else {
                // 从读取缓冲区中获取一个完整的包
                $requestBuffer = substr($this->_readData, 0, $this->_currentPackageSize);
                // 从读取缓冲区删除获取到的包
                $this->_readData = substr($this->_readData, $this->_currentPackageSize);
            }
            $this->_currentPackageSize = 0;
            if (!empty($this->instance->callbackNewData)) {
                try {
                    call_user_func_array($this->instance->callbackNewData,
                        [$this, $applicationProtocolClassName::decode($requestBuffer, $this)]);
                } catch (\Exception $e) {
                    self::$statistics['exception_count']++;
                    Log::write('YP_Socket: execution callback function callbackNewData-' . json_encode($this->instance->callbackNewData) . ' throw exception' . json_encode($e),
                        'ERROR');
                }
            }
        }
    }

    /**
     * 读取数据包. 如果没有应用层协议
     */
    private function _readNoApplicationProtocol()
    {
        // 如果读取到的数据是空,或者链接已经被暂停
        if ($this->_readData === '' || $this->_isPauseRead === true) {
            return;
        }
        self::$statistics['total_read_package_count']++;
        // 触发接收到新数据的回调函数
        if (!empty($this->instance->callbackNewData)) {
            try {
                call_user_func_array($this->instance->callbackNewData, [$this, $this->_readData]);
            } catch (\Exception $e) {
                self::$statistics['exception_count']++;
                Log::write('MeepoPS: execution callback function callbackNewData-' . json_encode($this->instance->callbackNewData) . ' throw exception' . json_encode($e),
                    'ERROR');
            }
        }
        $this->_readData = '';
    }

    /**
     * 发送数据
     *
     * @param mixed     string 待发送的数据
     * @param $isEncode bool 发送前是否根据应用层协议转码
     *
     * @return int|bool 拒绝发送为0, 发送成功为发送成功的数据长度. 部分成功则是成功发送的长度, 加入待发送缓冲区延迟发送为-1 发送失败为false.
     */
    public function send($data, $isEncode = true)
    {
        // 如果需要根据协议转码, 并且应用层协议类存在
        if ($isEncode === true && $this->_applicationProtocolClassName) {
            $applicationProtocolClassname = $this->_applicationProtocolClassName;
            $data                         = $applicationProtocolClassname::encode($data, $this);
            if (!$data) {
                return 0;
            }
        }
        // 如果状态是链接中.
        if ($this->_currentStatus === self::CONNECT_STATUS_CONNECTING) {
            $this->_sendBuffer .= $data;

            return -1;
            // 如果状态是正在关闭或者和已经关闭
        } else if ($this->_currentStatus === self::CONNECT_STATUS_CLOSING || $this->_currentStatus === self::CONNECT_STATUS_CLOSED) {
            return 0;
        }
        // 如果待发送队列有值.
        if (!empty($this->_sendBuffer)) {
            $this->_sendBuffer .= $data;
            $this->_sendBufferIsFull();

            return -1;
        }
        // 如果待发送的缓冲区为空,直接发送本次需要发送的数据
        $length = $this->_sendAction($this->_connect, $data);
        // 全部发送成功
        if ($length > 0 && $length === strlen($data)) {
            return $length;
            // 部分发送成功
        } else if ($length > 0 && $length !== strlen($data)) {
            $this->_sendBuffer = substr($data, $length);
            // 因为没有全部发送成功,则将发送事件加入到事件监听列表中
            YP_Socket::$globalEvent->add([$this, 'sendEvent'], [], $this->_connect, EventInterface::EVENT_TYPE_WRITE);
            //检测队列是否为空
            $this->_sendBufferIsFull();

            return $length;
            // 发送失败
        } else {
            return false;
        }
    }

    /**
     * 给链接中写入数据.为轮询事件用的
     * @return void
     */
    public function sendEvent()
    {
        // 给socket资源中写入数据
        $length = $this->_sendAction($this->_connect, $this->_sendBuffer);
        // 写入失败
        if (!is_int($length) || intval($length) <= 0) {
            return;
        }
        // 全部发送成功
        if ($length === strlen($this->_sendBuffer)) {
            // 全部发送成功后不再轮询这个事件
            YP_Socket::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_WRITE);
            $this->_sendBuffer = '';
            //触发待发送缓冲区为空的队列
            if (!empty($this->instance->callbackSendBufferEmpty)) {
                try {
                    call_user_func($this->instance->callbackSendBufferEmpty, $this);
                } catch (\Exception $e) {
                    self::$statistics['exception_count']++;
                    Log::write('YP_Socket: execution callback function callbackSendBufferEmpty-' . json_encode($this->instance->callbackSendBufferEmpty) . ' throw exception' . json_encode($e),
                        'ERROR');
                }
            }
            //如果是正在关闭中的状态(平滑断开链接会发送完待发送缓冲区的所有数据后再销毁资源)
            if ($this->_currentStatus === self::CONNECT_STATUS_CLOSING) {
                $this->destroy();
            }
            //部分发送成功
        } else {
            $this->_sendBuffer = substr($this->_sendBuffer, $length);
        }
    }

    /**
     * 执行发送的动作
     *
     * @param $data   string 发送内容
     * @param $socket resource Socket资源
     *
     * @return int|bool
     */
    private function _sendAction($socket, $data)
    {
        self::$statistics['total_send_count']++;
        $length = @fwrite($socket, $data);
        if (!is_int($length) || intval($length) <= 0) {
            Log::write('Write data failed. Possible socket resource has disabled or network problems', 'WARNING');
            self::$statistics['total_send_failed_count']++;
            // 触发错误的回调函数
            if (!empty($this->instance->callbackError)) {
                try {
                    call_user_func_array($this->instance->callbackError, [
                        $this,
                        ERROR_CODE_SEND_SOCKET_INVALID,
                        'Send data failed. Possible socket resource has disabled'
                    ]);
                } catch (\Exception $e) {
                    self::$statistics['exception_count']++;
                    Log::write('MeepoPS: execution callback function callbackError-' . json_encode($this->instance->callbackError) . ' throw exception' . json_encode($e),
                        'ERROR');
                }
            }
            // 强制销毁
            $this->destroy();
        }

        return $length;
    }

    /**
     * 关闭客户端链接
     *
     * @param string|null $data 关闭前需要发送的数据
     */
    public function close($data = null)
    {
        if ($this->_currentStatus === self::CONNECT_STATUS_CLOSING || $this->_currentStatus === self::CONNECT_STATUS_CLOSED) {
            return;
        } else {
            if (!is_null($data)) {
                $this->send($data);
            }
            $this->_currentStatus = self::CONNECT_STATUS_CLOSING;
        }
        if ($this->_sendBuffer === '') {
            $this->destroy();
        }
    }

    /**
     * 销毁链接
     */
    public function destroy()
    {
        // 如果当前状态是已经关闭的,则不处理
        if ($this->_currentStatus === self::CONNECT_STATUS_CLOSED) {
            return;
        }
        // 从事件中移除对链接的读写监听
        YP_Socket::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_READ);
        YP_Socket::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_WRITE);
        @fclose($this->_connect);
        //从实例的客户端列表中移除
        if (!empty($this->instance->clientList)) {
            unset($this->instance->clientList[$this->id]);
        }
        //变更状态为已经关闭
        $this->_currentStatus = self::CONNECT_STATUS_CLOSED;
        //变更统计信息
        self::$statistics['current_connect_count']--;
        //执行链接断开时的回调函数
        if (!empty($this->instance->callbackConnectClose)) {
            try {
                call_user_func($this->instance->callbackConnectClose, $this);
            } catch (\Exception $e) {
                self::$statistics['exception_count']++;
                Log::write('YP_Socket: execution callback function callbackConnectClose-' . json_encode($this->instance->callbackConnectClose) . ' throw exception' . json_encode($e),
                    'ERROR');
            }
        }
        unset($this);
    }

    /**
     * 暂停读取消息
     */
    public function pauseRead()
    {
        YP_Socket::$globalEvent->delOne($this->_connect, EventInterface::EVENT_TYPE_READ);
        $this->_isPauseRead = true;
    }

    /**
     * 继续读取消息
     */
    public function resumeRead()
    {
        if ($this->_isPauseRead !== true) {
            return;
        }
        YP_Socket::$globalEvent->add([$this, 'read'], [], $this->_connect, EventInterface::EVENT_TYPE_READ);
        $this->_isPauseRead = false;
        $this->read($this->_connect);
    }
    
    /**
     * 当前是否被暂停读取
     *
     * @return bool 暂停返回true, 没暂停返回false
     */
    public function isPauseRead()
    {
        return $this->_isPauseRead;
    }

    /**
     * 获取客户端地址
     * @return array|false 成功返回array[0]是ip,array[1]是端口. 失败返回false
     */
    public function getClientAddress()
    {
        if ($this->_clientAddress) {
            $position = strrpos($this->_clientAddress, ':');
            if (is_int($position)) {
                $ret[0] = substr($this->_clientAddress, 0, $position);
                $ret[1] = substr($this->_clientAddress, $position + 1);

                return $ret;
            }
        }

        return false;
    }

    /**
     * 截取消息的后部分. 扔掉前部分
     *
     * @param      $start
     * @param null $length
     */
    public function substrReadData($start, $length = null)
    {
        if (is_null($length)) {
            $this->_readData = substr($this->_readData, $start);
        } else {
            $this->_readData = substr($this->_readData, $start, $length);
        }

    }

    /**
     * 待发送的缓冲区是否已经超过最大限度.
     * 本函数会出发待发送缓冲区已满的回调函数
     * @return bool 大于或等于待发送缓冲区的最大限度
     */
    private function _sendBufferIsFull()
    {
        if (strlen($this->_sendBuffer) >= TCP_CONNECT_SEND_MAX_BUFFER_SIZE) {
            Log::write('Send data failed. The send buffer is full. Data is discarded', 'WARNING');
            if ($this->instance->callbackSendBufferFull) {
                try {
                    call_user_func($this->instance->callbackSendBufferFull, $this);
                } catch (\Exception $e) {
                    self::$statistics['exception_count']++;
                    Log::write('MeepoPS: execution callback function callbackSendBufferFull-' . json_encode($this->instance->callbackSendBufferFull) . ' throw exception' . json_encode($e),
                        'ERROR');
                }

                return true;
            }
        }

        return false;
    }
}