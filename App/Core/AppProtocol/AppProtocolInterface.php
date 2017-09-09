<?php
/**
 * User: yongli
 * Date: 17/9/9
 * Time: 23:02
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core\AppProtocol;

use App\Core\TransportProtocol\TransportProtocolInterface;

interface AppProtocolInterface
{

    /**
     * 检测数据, 返回数据包的长度.
     * 没有数据包或者数据包未结束,则返回0
     *
     * @param string                     $data    数据包
     * @param TransportProtocolInterface $connect 基于传输层协议的链接
     *
     * @return mixed
     */
    public static function input($data, TransportProtocolInterface $connect);

    /**
     * 对发送的数据进行encode. 例如将数据整理为符合Http/WebSocket/stream(json/text等)等协议的规定
     *
     * @param                            $data
     * @param TransportProtocolInterface $connect 基于传输层协议的链接
     *
     * @return mixed
     */
    public static function encode($data, TransportProtocolInterface $connect);

    /**
     * 对接收到的数据进行decode. 例如将数据按照客户端约定的协议如Http/WebSocket/stream(json/text等)等进行解析
     * 本方法将会触发MeepoPS::$callbackNewData的回调函数
     *
     * @param string                     $data    待解码的数据
     * @param TransportProtocolInterface $connect 基于传输层协议的链接
     *
     * @return mixed
     */
    public static function decode($data, TransportProtocolInterface $connect);
}