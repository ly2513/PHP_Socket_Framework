<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 23:47
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

// YP_SOCKET 错误码 - 发送失败
define('ERROR_CODE_SEND_FAILED', 1);

// YP_SOCKET 错误码 - 待发送缓冲区已满
define('ERROR_CODE_SEND_BUFFER_FULL', 2);

// YP_SOCKET 错误码 - 发送链接的socket资源无效
define('ERROR_CODE_SEND_SOCKET_INVALID', 3);