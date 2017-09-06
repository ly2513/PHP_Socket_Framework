<?php
/**
 * User: yongli
 * Date: 17/9/6
 * Time: 22:28
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App;

use App\Core\Socket;

// 定义根目录
define('ROOT_PATH', dirname(__FILE__) . '/');


// 载入配置文件
require_once ROOT_PATH . '/Core/Config.php';

//环境检测
require_once ROOT_PATH . '/Core/CheckEnv.php';

// 载入核心文件
require_once ROOT_PATH . '/Core/Init.php';

// 启动框架
function run()
{
    Socket::runMeepoPS();
}
