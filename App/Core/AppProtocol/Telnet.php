<?php
/**
 * User: yongli
 * Date: 17/9/9
 * Time: 23:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core\ApplicationProtocol;

use App\Core\TransportProtocol\TransportProtocolInterface;

class Telnet implements AppProtocolInterface
{
    /**
     * 检测数据, 返回数据包的长度.
     * 没有数据包或者数据包未结束,则返回0
     *
     * @param string                     $data    数据包
     * @param TransportProtocolInterface $connect 基于传输层协议的链接
     *
     * @return int
     */
    public static function input($data, TransportProtocolInterface $connect)
    {
        // 获取首个数据包的大小(结尾的位置)
        $position = strpos($data, "\n");
        // 如果没有, 说明接收到的不是一个完整的数据包, 则暂时不处理本次请求, 等待下次接收后再一起处理
        if ($position === false) {
            return 0;
        }

        // 返回数据包的长度. 因为计数从0开始,所以返回时+1
        return $position + 1;
    }

    /**
     * 数据编码. 默认在发送数据前自动调用此方法. 不用您手动调用.
     *
     * @param string                     $data    给数据流中发送的数据
     * @param TransportProtocolInterface $connect 基于传输层协议的链接
     *
     * @return string
     */
    public static function encode($data, TransportProtocolInterface $connect)
    {
        return $data . "\n";
    }

    /**
     * 数据解码. 默认在接收数据时自动调用此方法. 不用您手动调用.
     *
     * @param string                     $data    从数据流中接收到的数据
     * @param TransportProtocolInterface $connect 基于传输层协议的链接
     *
     * @return string
     */
    public static function decode($data, TransportProtocolInterface $connect)
    {
        return trim($data);
    }
}