<?php
/**
 * User: yongli
 * Date: 17/9/6
 * Time: 23:21
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

/************* 核心入口文件 ******************/

// 自动载入函数
require 'Autoload.php';

// 载入常量定义
require 'Constant.php';

// 定义版本号
define('YP_VERSION', '0.0.5');

// 错误报告是否开启
if (YP_DEBUG) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

// 开启立即刷新输出
if (YP_IMPLICIT_FLUSH) {
    ob_implicit_flush();
} else {
    ob_implicit_flush(false);
}

// 设置脚本执行时间为永不超时
set_time_limit(0);