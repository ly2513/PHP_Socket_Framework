<?php
/**
 * User: yongli
 * Date: 17/9/12
 * Time: 22:30
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Api;

use App\Core\YP_Socket;

/**
 * Telnet协议
 * Class WebSocket
 *
 * @package App\Api
 */
class WebSocket extends YP_Socket
{
    /**
     * 回调函数 - WebSocket专用 - 接收到PING的时候
     *
     * @var
     */
    public $callbackWSPing;

    /**
     * 回调函数 - WebSocket专用 - 接收到PONG的时候
     *
     * @var
     */
    public $callbackWSPong;

    /**
     * 回调函数 - WebSocket专用 - 断开连接时
     *
     * @var
     */
    public $callbackWSDisconnect;

    /**
     * WebSocket constructor.
     *
     * @param string $host 需要监听的地址
     * @param string $port 需要监听的端口
     * @param array  $contextOptionList
     */
    public function __construct($host, $port, $contextOptionList = array())
    {
        if (!$host || !$port) {
            return;
        }
        parent::__construct('websocket', $host, $port, $contextOptionList);
    }

    /**
     * 运行一个Telnet实例
     */
    public function execute()
    {
        parent::execute();
    }
}