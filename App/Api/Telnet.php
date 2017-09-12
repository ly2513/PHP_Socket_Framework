<?php
/**
 * User: yongli
 * Date: 17/9/12
 * Time: 22:26
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */

namespace App\Api;

use App\Core\MeepoPS;
use App\Core\YP_Socket;

/**
 * Telnet协议
 * Class Telnet
 *
 * @package App\Api
 */
class Telnet extends YP_Socket
{

    /**
     * Telnet constructor.
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
        parent::__construct('telnet', $host, $port, $contextOptionList);
    }

    /**
     * 运行一个Telnet实例
     */
    public function execute()
    {
        parent::execute();
    }
}